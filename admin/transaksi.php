<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Cek tab aktif (default 'peminjaman')
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'peminjaman';

// Query Data Peminjaman
$query_peminjaman = mysqli_query($koneksi, "
    SELECT peminjaman.*, user.username AS nama_peminjam, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    JOIN user ON peminjaman.id_user = user.id_user 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    ORDER BY peminjaman.id_peminjaman DESC
");

// Query Data Pengembalian (Join ke tabel pengembalian)
$query_pengembalian = mysqli_query($koneksi, "
    SELECT pengembalian.*, peminjaman.tanggal_pinjam, peminjaman.tanggal_kembali_rencana, user.username AS nama_peminjam, alat.nama_alat 
    FROM pengembalian 
    JOIN peminjaman ON pengembalian.id_peminjaman = peminjaman.id_peminjaman 
    JOIN user ON peminjaman.id_user = user.id_user 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    ORDER BY pengembalian.id_pengembalian DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi - Panel Admin</title>
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

    <!-- Konten Utama -->
    <div class="container mt-4">
        <h3 class="mb-4 fw-bold">Data Transaksi Peminjaman & Pengembalian</h3>

        <!-- Tab Navigasi seperti di referensi -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?php echo ($tab == 'peminjaman') ? 'active fw-bold' : ''; ?>" href="transaksi.php?tab=peminjaman">Data Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($tab == 'pengembalian') ? 'active fw-bold' : ''; ?>" href="transaksi.php?tab=pengembalian">Data Pengembalian</a>
            </li>
        </ul>

        <!-- Konten Berdasarkan Tab -->
        <div class="card shadow border-0">
            <div class="card-body">
                <?php if ($tab == 'peminjaman') : ?>
                    <!-- TAB DATA PEMINJAMAN -->
                    <h5 class="card-title fw-bold text-secondary mb-3">Daftar Transaksi Peminjaman</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="15%">Peminjam</th>
                                    <th width="25%">Detail Alat</th>
                                    <th width="15%">Tgl Pinjam</th>
                                    <th width="15%">Tgl Rencana Kembali</th>
                                    <th width="15%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_peminjaman && mysqli_num_rows($query_peminjaman) > 0) : ?>
                                    <?php while ($row = mysqli_fetch_assoc($query_peminjaman)) : ?>
                                        <tr>
                                            <td>#<?php echo $row['id_peminjaman']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_peminjam']); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?><br>
                                                <small class="text-muted">Jumlah: <?php echo $row['jumlah'] ?? 0; ?> unit</small>
                                            </td>
                                            <td><?php echo $row['tanggal_pinjam']; ?></td>
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
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada data peminjaman.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else : ?>
                    <!-- TAB DATA PENGEMBALIAN -->
                    <h5 class="card-title fw-bold text-secondary mb-3">Daftar Transaksi Pengembalian</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="15%">Peminjam</th>
                                    <th width="25%">Detail Alat</th>
                                    <th width="15%">Tgl Dikembalikan</th>
                                    <th width="15%">Denda</th>
                                    <th width="15%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_pengembalian && mysqli_num_rows($query_pengembalian) > 0) : ?>
                                    <?php while ($row = mysqli_fetch_assoc($query_pengembalian)) : ?>
                                        <tr>
                                            <td>#<?php echo $row['id_pengembalian']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_peminjam']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?></td>
                                            <td><?php echo $row['tanggal_dikembalikan']; ?></td>
                                            <td>Rp <?php echo number_format($row['denda'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-success text-uppercase"><?php echo $row['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada data pengembalian.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>