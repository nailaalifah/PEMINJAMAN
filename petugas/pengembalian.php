<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../index.php");
    exit;
}

$id_user_petugas =$_SESSION['id_user'];

// Proses Pengembalian oleh Petugas
if (isset($_POST['proses_kembali'])) {
    $id_peminjaman =$_POST['id_peminjaman'];
    $tgl_kembali_real =$_POST['tanggal_kembali_real'];
    $kondisi_alat =$_POST['kondisi_alat'];

    // 1. Ambil data peminjaman untuk cek tanggal rencana dan detail alat
    $qData = mysqli_query($koneksi, "
        SELECT peminjaman.*, detail_peminjaman.id_alat, detail_peminjaman.jumlah 
        FROM peminjaman 
        LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
        WHERE peminjaman.id_peminjaman = '$id_peminjaman'
    ");
    $dData = mysqli_fetch_assoc($qData);

    if ($dData) {
        $tgl_rencana =$dData['tanggal_kembali_rencana'];
        $id_alat =$dData['id_alat'];
        $jumlah_pinjam =$dData['jumlah'];

        // 2. Hitung keterlambatan dan denda (Rp 10.000 / hari)
        $denda = 0;
        $tgl_rencana_dt = new DateTime($tgl_rencana);
        $tgl_real_dt = new DateTime($tgl_kembali_real);

        if ($tgl_real_dt > $tgl_rencana_dt) {$selisih = $tgl_rencana_dt->diff($tgl_real_dt);
            $hari_terlambat =$selisih->days;
            $denda =$hari_terlambat * 10000;
        }

        // 3. Pengecekan Kondisi Alat
        if ($kondisi_alat == 'rusak') {
            $status_akhir = 'ditolak_rusak';$status_pengembalian = 'rusak';

            // Update status peminjaman
            mysqli_query($koneksi, "UPDATE peminjaman SET status = '$status_akhir' WHERE id_peminjaman = '$id_peminjaman'");

            // Insert ke tabel pengembalian
            mysqli_query($koneksi, "INSERT INTO pengembalian (id_peminjaman, tanggal_dikembalikan, denda, status) VALUES ('$id_peminjaman', '$tgl_kembali_real', '$denda', '$status_pengembalian')");

            // Update kondisi alat di tabel alat jadi rusak
            mysqli_query($koneksi, "UPDATE alat SET kondisi = 'Rusak' WHERE id_alat = '$id_alat'");

            $akt = "Petugas menolak pengembalian ID Peminjaman $id_peminjaman (Alat Rusak, Denda: Rp " . number_format($denda, 0, ',', '.') . ")";
        } else {
            $status_akhir = 'dikembalikan';
            $status_pengembalian = ($denda > 0) ? 'terlambat' : 'baik';

            // Update status peminjaman
            mysqli_query($koneksi, "UPDATE peminjaman SET status = '$status_akhir' WHERE id_peminjaman = '$id_peminjaman'");

            // Insert ke tabel pengembalian
            mysqli_query($koneksi, "INSERT INTO pengembalian (id_peminjaman, tanggal_dikembalikan, denda, status) VALUES ('$id_peminjaman', '$tgl_kembali_real', '$denda', '$status_pengembalian')");

            // Kembalikan Stok Alat ke database
            mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jumlah_pinjam WHERE id_alat = '$id_alat'");

            $akt = "Petugas mengonfirmasi pengembalian ID Peminjaman $id_peminjaman (Denda: Rp " . number_format($denda, 0, ',', '.') . ")";
        }

        // Catat Log
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user_petugas', '$akt', NOW())");

        header("Location: pengembalian.php");
        exit;
    }
}

// Query mengambil semua transaksi yang aktif atau perlu diproses (menunggu pengecekan & disetujui)
$query_aktif = mysqli_query($koneksi, "
    SELECT peminjaman.*, user.username AS username, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    LEFT JOIN user ON peminjaman.id_user = user.id_user 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    WHERE peminjaman.status = 'menunggu_pengecekan' OR peminjaman.status = 'disetujui'
    ORDER BY peminjaman.id_peminjaman DESC
");

// Query riwayat pengembalian yang sudah selesai (agar masuk rekam jejak/laporan)
$query_riwayat = mysqli_query($koneksi, "
    SELECT p.*, u.username AS username, al.nama_alat, dt.jumlah, 
            pg.tanggal_dikembalikan AS tanggal_kembali_real, pg.denda AS denda, pg.status AS status_kembali
    FROM pengembalian pg
    LEFT JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman
    LEFT JOIN user u ON p.id_user = u.id_user 
    LEFT JOIN detail_peminjaman dt ON p.id_peminjaman = dt.id_peminjaman 
    LEFT JOIN alat al ON dt.id_alat = al.id_alat 
    ORDER BY pg.id_pengembalian DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pengembalian - Petugas</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Petugas Warna Hitam -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">PETUGAS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman.php">Validasi Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pengembalian.php">Proses Pengembalian</a></li>
                     <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Login Petugas: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="fw-bold mb-4">Proses Pengembalian Alat</h3>

        <!-- Tabel 1: Alat yang Sedang Dipinjam / Menunggu Pengecekan -->
        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-bold">Daftar Pengajuan & Peminjaman Aktif</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Peminjam</th>
                                <th width="25%">Detail Alat & Jumlah</th>
                                <th width="15%">Tgl Rencana Kembali</th>
                                <th width="15%">Status</th>
                                <th width="20%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_aktif && mysqli_num_rows($query_aktif) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_aktif)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['username'] ?? '-'); ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?></strong><br>
                                            <span class="badge bg-secondary">Qty: <?php echo $row['jumlah'] ?? 0; ?> unit</span>
                                        </td>
                                        <td><strong class="text-danger"><?php echo $row['tanggal_kembali_rencana']; ?></strong></td>
                                        <td>
                                            <?php if ($row['status'] == 'menunggu_pengecekan') : ?>
                                                <span class="badge bg-warning text-dark">Menunggu Pengecekan</span>
                                            <?php else : ?>
                                                <span class="badge bg-primary">Sedang Dipinjam</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'menunggu_pengecekan') : ?>
                                                <!-- Tombol Hijau dan Bisa Diklik jika sudah diajukan oleh peminjam -->
                                                <button type="button" class="btn btn-success btn-sm fw-bold" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalProses<?php echo $row['id_peminjaman']; ?>">
                                                    Proses Pengembalian
                                                </button>
                                            <?php else : ?>
                                                <!-- Tombol Abu-abu dan Belum Bisa Diklik jika peminjam belum klik ajukan pengembalian -->
                                                <button type="button" class="btn btn-secondary btn-sm fw-bold" disabled>
                                                    Belum Diajukan
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal Pengecekan Pengembalian (Hanya muncul jika berstatus menunggu_pengecekan) -->
                                    <?php if ($row['status'] == 'menunggu_pengecekan') : ?>
                                    <div class="modal fade" id="modalProses<?php echo $row['id_peminjaman']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content text-start">
                                                <form action="pengembalian.php" method="POST">
                                                    <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman']; ?>">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title fw-bold">Form Pengecekan Pengembalian</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-1"><strong>Peminjam:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                                                        <p class="mb-1"><strong>Alat:</strong> <?php echo htmlspecialchars($row['nama_alat']); ?> (<?php echo$row['jumlah']; ?> unit)</p>
                                                        <p class="mb-3"><strong>Tgl Rencana Kembali:</strong> <?php echo $row['tanggal_kembali_rencana']; ?></p>
                                                        <hr>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Tanggal Dikembalikan Real</label>
                                                            <input type="date" name="tanggal_kembali_real" id="tgl_real_<?php echo $row['id_peminjaman']; ?>" 
                                                                   class="form-control" 
                                                                   value="<?php echo date('Y-m-d'); ?>" 
                                                                   onchange="hitungDenda('<?php echo $row['id_peminjaman']; ?>', '<?php echo$row['tanggal_kembali_rencana']; ?>')" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Kondisi Alat Saat Dikembalikan</label>
                                                            <select name="kondisi_alat" class="form-select" required>
                                                                <option value="baik" selected>Baik / Normal (Stok Bertambah)</option>
                                                                <option value="rusak">Rusak / Bermasalah (Ditolak & Stok Tidak Bertambah)</option>
                                                            </select>
                                                        </div>

                                                        <div class="alert alert-info py-2 mb-0">
                                                            <span id="info_denda_<?php echo $row['id_peminjaman']; ?>">
                                                                Pilih tanggal dikembalikan untuk menghitung keterlambatan.
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="proses_kembali" class="btn btn-primary">Konfirmasi Pengembalian</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan pengembalian alat aktif.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel 2: Riwayat Pengembalian yang Telah Diproses (Masuk Laporan) -->
        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-secondary text-white py-3">
                <h5 class="mb-0 fw-bold">Riwayat Pengembalian Selesai</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th width="5%">No</th>
                                <th width="20%">Peminjam</th>
                                <th width="25%">Detail Alat & Jumlah</th>
                                <th width="15%">Tgl Dikembalikan</th>
                                <th width="15%">Denda</th>
                                <th width="20%">Status Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_riwayat)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['username'] ?? '-'); ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?></strong><br>
                                            <span class="badge bg-secondary">Qty: <?php echo $row['jumlah'] ?? 0; ?> unit</span>
                                        </td>
                                        <td><?php echo $row['tanggal_kembali_real'] ?? '-'; ?></td>
                                        <td>
                                            <?php if (!empty($row['denda']) &&$row['denda'] > 0) : ?>
                                                <span class="text-danger fw-bold">Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?></span>
                                            <?php else : ?>
                                                <span class="text-success">Rp 0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'dikembalikan') : ?>
                                                <span class="badge bg-success">SELESAI (DIKEMBALIKAN)</span>
                                            <?php elseif ($row['status'] == 'ditolak_rusak') : ?>
                                                <span class="badge bg-danger">DITOLAK (RUSAK)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pengembalian alat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript Hitung Denda Otomatis -->
    <script>
    function hitungDenda(idPeminjaman, tglRencana) {
        const inputReal = document.getElementById('tgl_real_' + idPeminjaman);
        if (!inputReal) return;

        const valReal = inputReal.value;
        const infoDenda = document.getElementById('info_denda_' + idPeminjaman);

        if (!valReal) return;

        const dateRencana = new Date(tglRencana);
        const dateReal = new Date(valReal);

        const diffTime = dateReal - dateRencana;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const totalDenda = diffDays * 10000;
            infoDenda.innerHTML = `<strong class="text-danger">Terlambat ${diffDays} hari!</strong><br>Denda: <strong>Rp ${totalDenda.toLocaleString('id-ID')}</strong> (Rp 10.000 / hari)`;
        } else {
            infoDenda.innerHTML = `<strong class="text-success">Tepat Waktu.</strong> Tidak ada denda keterlambatan.`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        <?php 
        if ($query_aktif && mysqli_num_rows($query_aktif) > 0) {
            mysqli_data_seek($query_aktif, 0);
            while ($r = mysqli_fetch_assoc($query_aktif)) {
                if ($r['status'] == 'menunggu_pengecekan') {
                    echo "hitungDenda('" . $r['id_peminjaman'] . "', '" . $r['tanggal_kembali_rencana'] . "');\n";
                }
            }
        }
        ?>
    });
    </script>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>