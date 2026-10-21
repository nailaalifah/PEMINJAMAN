<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Pengaturan Pagination (10 data per halaman)
$batas = 10;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

// Hitung total data log aktivitas hari ini untuk pagination
$query_jumlah = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM log_aktivitas WHERE DATE(waktu) = CURDATE()");
$row_jumlah = mysqli_fetch_assoc($query_jumlah);
$total_data = $row_jumlah['total'];
$total_halaman = ceil($total_data / $batas);

// Query log aktivitas hari ini dengan LIMIT dan OFFSET
$query_log = mysqli_query($koneksi, "SELECT log_aktivitas.*, user.username FROM log_aktivitas JOIN user ON log_aktivitas.id_user = user.id_user WHERE DATE(log_aktivitas.waktu) = CURDATE() ORDER BY log_aktivitas.waktu DESC LIMIT $halaman_awal, $batas");
$nomor = $halaman_awal + 1;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Peminjaman Alat</title>
    <!-- Bootstrap 5 Offline -->
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
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-primary shadow-sm" role="alert">
                    <h4 class="alert-heading">Selamat Datang, Admin!</h4>
                    <p class="mb-0">Halaman ini menampilkan rekapitulasi aktivitas sistem yang tercatat pada hari ini.</p>
                </div>
            </div>
        </div>

        <!-- Tabel Log Aktivitas Hari Ini -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-secondary">Log Aktivitas Hari Ini (<?php echo date('d-m-Y'); ?>)</h5>
                        <span class="badge bg-secondary">Total: <?php echo $total_data; ?> Aktivitas</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="20%">Username</th>
                                        <th width="45%">Aktivitas</th>
                                        <th width="30%">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($query_log && mysqli_num_rows($query_log) > 0) : ?>
                                        <?php while ($row = mysqli_fetch_assoc($query_log)) : ?>
                                            <tr>
                                                <td><?php echo $nomor++; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['username']); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['aktivitas']); ?></td>
                                                <td><?php echo $row['waktu']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas yang tercatat hari ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Navigasi Pagination (Tombol Panah / Halaman) -->
                        <?php if ($total_halaman > 1) : ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <!-- Tombol Sebelumnya (Previous) -->
                                    <li class="page-item <?php echo ($halaman <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="index.php?halaman=<?php echo $halaman - 1; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Sebelumnya</span>
                                        </a>
                                    </li>

                                    <!-- Angka Halaman -->
                                    <?php for ($x = 1; $x <= $total_halaman; $x++) : ?>
                                        <li class="page-item <?php echo ($halaman == $x) ? 'active' : ''; ?>">
                                            <a class="page-link" href="index.php?halaman=<?php echo $x; ?>"><?php echo $x; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Tombol Berikutnya (Next) -->
                                    <li class="page-item <?php echo ($halaman >= $total_halaman) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="index.php?halaman=<?php echo $halaman + 1; ?>" aria-label="Next">
                                            <span aria-hidden="true">Berikutnya &raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap 5 Offline -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>