<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../index.php");
    exit;
}

// Query mengambil data peminjaman HARI INI saja
$query_peminjaman_hari_ini = mysqli_query($koneksi, "
    SELECT peminjaman.*, user.username AS nama_peminjam, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    JOIN user ON peminjaman.id_user = user.id_user 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    WHERE DATE(peminjaman.tanggal_pinjam) = CURDATE()
    ORDER BY peminjaman.id_peminjaman DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Sistem Peminjaman Alat</title>
    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Petugas dengan tambahan menu Laporan -->
   <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
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
                <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
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
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success shadow-sm" role="alert">
                    <h4 class="alert-heading">Selamat Datang, Petugas!</h4>
                    <p class="mb-0">Berikut adalah daftar transaksi peminjaman alat yang tercatat pada hari ini (<?php echo date('d-m-Y'); ?>).</p>
                </div>
            </div>
        </div>

        <!-- Tabel Peminjaman Hari Ini -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-success">Data Peminjaman Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%">Peminjam</th>
                                        <th width="30%">Detail Alat & Jumlah</th>
                                        <th width="25%">Tanggal Rencana Kembali</th>
                                        <th width="20%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($query_peminjaman_hari_ini && mysqli_num_rows($query_peminjaman_hari_ini) > 0) : ?>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_peminjaman_hari_ini)) : ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['nama_peminjam']); ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?><br>
                                                    <span class="badge bg-secondary">Qty: <?php echo $row['jumlah'] ?? 0; ?> unit</span>
                                                </td>
                                                <td><?php echo $row['tanggal_kembali_rencana']; ?></td>
                                                <td>
                                                    <?php 
                                                        $st = $row['status'];
                                                        $bg = 'bg-secondary';
                                                        if($st == 'pending') $bg = 'bg-warning text-dark';
                                                        elseif($st == 'disetujui') $bg = 'bg-primary';
                                                        elseif($st == 'ditolak') $bg = 'bg-danger';
                                                        elseif($st == 'selesai') $bg = 'bg-success';
                                                    ?>
                                                    <span class="badge <?php echo $bg; ?> text-uppercase"><?php echo $st; ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada data peminjaman yang tercatat hari ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap 5 Offline -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>