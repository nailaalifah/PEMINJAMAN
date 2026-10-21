<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../index.php");
    exit;
}

// Logika filter Harian / Bulanan (Memasukkan 'disetujui', 'dikembalikan', 'ditolak_rusak', dan 'selesai')
$where = "WHERE p.status IN ('disetujui', 'dikembalikan', 'ditolak_rusak', 'selesai')";
$jenis_filter = isset($_GET['tipe']) ? $_GET['tipe'] : 'hari';

if (isset($_GET['filter'])) {
    if ($jenis_filter == 'hari' && !empty($_GET['tgl'])) {
        $tgl = $_GET['tgl'];
        $where .= " AND DATE(p.tanggal_pinjam) = '$tgl'";
    } elseif ($jenis_filter == 'bulan' && !empty($_GET['bulan']) && !empty($_GET['tahun'])) {
        $bulan = $_GET['bulan'];
        $tahun = $_GET['tahun'];
        $where .= " AND MONTH(p.tanggal_pinjam) = '$bulan' AND YEAR(p.tanggal_pinjam) = '$tahun'";
    }
}

// Query data laporan peminjaman yang sudah diproses petugas (mengambil langsung dari tabel peminjaman)
$query_laporan = mysqli_query($koneksi, "
    SELECT p.*, u.username AS nama_peminjam, al.nama_alat, dt.jumlah, 
           COALESCE(pg.tanggal_dikembalikan, '-') AS tgl_kembali_real,
           COALESCE(pg.denda, 0) AS total_denda
    FROM peminjaman p 
    LEFT JOIN user u ON p.id_user = u.id_user 
    LEFT JOIN detail_peminjaman dt ON p.id_peminjaman = dt.id_peminjaman 
    LEFT JOIN alat al ON dt.id_alat = al.id_alat 
    LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman 
    $where 
    ORDER BY p.id_peminjaman DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Panel Petugas</title>
    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Petugas Warna Hitam -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm d-print-none">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">PANEL PETUGAS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman.php">Validasi Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="pengembalian.php">Proses Pengembalian</a></li>
                    <li class="nav-item"><a class="nav-link active" href="laporan.php">Laporan</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Login Petugas: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container mt-4">
        <h3 class="mb-3 fw-bold">Laporan Peminjaman & Pengembalian (Selesai)</h3>

        <!-- Form Filter Harian / Bulanan -->
        <form method="GET" class="row g-2 mb-4 d-print-none">
            <div class="col-md-2">
                <select name="tipe" id="tipe_filter" class="form-control" onchange="toggleFilterInput()">
                    <option value="hari" <?= ($jenis_filter == 'hari') ? 'selected':'' ?>>Harian</option>
                    <option value="bulan" <?= ($jenis_filter == 'bulan') ? 'selected':'' ?>>Bulanan</option>
                </select>
            </div>

            <div class="col-md-3" id="box_tgl">
                <input type="date" name="tgl" class="form-control" value="<?= $_GET['tgl'] ?? date('Y-m-d') ?>">
            </div>

            <div class="col-md-2" id="box_bulan" style="display: none;">
                <select name="bulan" class="form-control">
                    <?php
                    $nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    $bln_pilih = $_GET['bulan'] ?? date('m');
                    foreach ($nama_bulan as $k => $v) { echo "<option value='$k' ".($bln_pilih == $k ? 'selected':'').">$v</option>"; }
                    ?>
                </select>
            </div>

            <div class="col-md-2" id="box_tahun" style="display: none;">
                <select name="tahun" class="form-control">
                    <?php
                    $thn_sekarang = date('Y');
                    $thn_pilih = $_GET['tahun'] ?? $thn_sekarang;
                    for ($t = $thn_sekarang; $t >= $thn_sekarang - 5; $t--) { echo "<option value='$t' ".($thn_pilih == $t ? 'selected':'').">$t</option>"; }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <button type="submit" name="filter" class="btn btn-primary">Filter</button>
                <button type="button" onclick="window.print()" class="btn btn-success">Print</button>
            </div>
        </form>

        <!-- Tabel Hasil Laporan -->
        <div class="card shadow border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Peminjam</th>
                                <th width="25%">Nama Alat & Jumlah</th>
                                <th width="15%">Tgl Pinjam</th>
                                <th width="15%">Tgl Dikembalikan</th>
                                <th width="15%">Denda</th>
                                <th width="10%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $total_denda_keseluruhan = 0;
                            if ($query_laporan && mysqli_num_rows($query_laporan) > 0):
                                while($d = mysqli_fetch_assoc($query_laporan)): 
                                    $total_denda_keseluruhan += $d['total_denda'];
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($d['nama_peminjam'] ?? '-') ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($d['nama_alat'] ?? '-') ?><br>
                                        <small class="text-muted">Qty: <?= $d['jumlah'] ?? 0 ?> unit</small>
                                    </td>
                                    <td><?= $d['tanggal_pinjam'] ?></td>
                                    <td><?= $d['tgl_kembali_real'] ?></td>
                                    <td>Rp <?= number_format($d['total_denda'], 0, ',', '.') ?></td>
                                    <td><span class="badge bg-success text-uppercase"><?= $d['status'] ?></span></td>
                                </tr>
                            <?php endwhile;
                            else: ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data laporan transaksi yang ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-secondary">
                                <td colspan="5" class="text-end">Total Akumulasi Denda:</td>
                                <td colspan="2">Rp <?= number_format($total_denda_keseluruhan, 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk Toggle Filter Harian/Bulanan -->
    <script>
    function toggleFilterInput() {
        var tipe = document.getElementById('tipe_filter').value;
        document.getElementById('box_tgl').style.display = (tipe === 'bulan') ? 'none' : 'block';
        document.getElementById('box_bulan').style.display = (tipe === 'bulan') ? 'block' : 'none';
        document.getElementById('box_tahun').style.display = (tipe === 'bulan') ? 'block' : 'none';
    }
    // Jalankan saat halaman dimuat
    toggleFilterInput();
    </script>
    <!-- Bootstrap JS Offline -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>