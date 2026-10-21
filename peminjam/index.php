<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Query Riwayat Peminjaman Khusus User Ini
$query_riwayat = mysqli_query($koneksi, "
    SELECT peminjaman.*, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    WHERE peminjaman.id_user = '$id_user' 
    ORDER BY peminjaman.id_peminjaman DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjam</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Peminjam Warna Hitam -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">PEMINJAM ALAT</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="pengembalian.php">Pengembalian</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Selamat Datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container mt-4">
        <div class="alert alert-secondary shadow-sm mb-4" role="alert">
            <h4 class="alert-heading fw-bold">Selamat Datang di Sistem Peminjaman Alat</h4>
            <p class="mb-0">Berikut adalah riwayat seluruh pengajuan dan peminjaman alat milik Anda.</p>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-secondary">Riwayat Peminjaman Saya</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Nama Alat</th>
                                <th width="10%">Jumlah</th>
                                <th width="20%">Tgl Pinjam</th>
                                <th width="20%">Rencana Kembali</th>
                                <th width="15%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_riwayat)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?></strong></td>
                                        <td><?php echo $row['jumlah'] ?? 0; ?> unit</td>
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
                                    <td colspan="6" class="text-center text-muted py-4">Anda belum pernah melakukan peminjaman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>