<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Logika filter berdasarkan kode referensi yang kamu berikan
$where = "";
if (isset($_GET['filter'])) {
    $tipe = $_GET['tipe'];
    if ($tipe == 'hari' && !empty($_GET['tgl'])) {
        $tgl = $_GET['tgl'];
        $where = "WHERE DATE(log_aktivitas.waktu) = '$tgl'";
    } elseif ($tipe == 'bulan' && !empty($_GET['bulan']) && !empty($_GET['tahun'])) {
        $bulan = $_GET['bulan'];
        $tahun = $_GET['tahun'];
        $where = "WHERE MONTH(log_aktivitas.waktu) = '$bulan' AND YEAR(log_aktivitas.waktu) = '$tahun'";
    }
}

// Query mengambil data log aktivitas digabung dengan tabel user (menggunakan id_user / id_uesr sesuai databasemu)
$query = mysqli_query($koneksi, "
    SELECT log_aktivitas.*, user.username 
    FROM log_aktivitas 
    LEFT JOIN user ON log_aktivitas.id_user = user.id_user 
    $where 
    ORDER BY log_aktivitas.id_log DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Log Aktivitas - Panel Admin</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Admin -->
   <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">PANEL ADMIN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user.php">User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kategori.php">Kategori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="alat.php">Alat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transaksi.php">Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="log_aktivitas.php">Log Aktivitas</a>
                    </li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Login: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <h3 class="mb-3 fw-bold">Laporan Log Aktivitas Sistem</h3>

    <!-- Form Filter ala Sistem Penjualan -->
    <form method="GET" class="row g-2 mb-4 d-print-none">
        <div class="col-md-2">
            <select name="tipe" id="tipe_filter" class="form-control" onchange="toggleFilterInput()">
                <option value="hari" <?= ($_GET['tipe']??'') == 'hari' ? 'selected':'' ?>>Harian</option>
                <option value="bulan" <?= ($_GET['tipe']??'') == 'bulan' ? 'selected':'' ?>>Bulanan</option>
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

    <!-- Tabel Data Log Aktivitas -->
    <div class="card shadow border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Username</th>
                            <th width="45%">Aktivitas</th>
                            <th width="30%">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        if (mysqli_num_rows($query) > 0):
                            while($d = mysqli_fetch_assoc($query)): 
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($d['username'] ?? 'System/Unknown') ?></span></td>
                                <td><?= htmlspecialchars($d['aktivitas']) ?></td>
                                <td><?= $d['waktu'] ?></td>
                            </tr>
                        <?php endwhile;
                        else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data aktivitas yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<script>
function toggleFilterInput() {
    var tipe = document.getElementById('tipe_filter').value;
    document.getElementById('box_tgl').style.display = (tipe === 'bulan') ? 'none' : 'block';
    document.getElementById('box_bulan').style.display = (tipe === 'bulan') ? 'block' : 'none';
    document.getElementById('box_tahun').style.display = (tipe === 'bulan') ? 'block' : 'none';
}
toggleFilterInput();
</script>
<script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>