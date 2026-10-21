<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Proses Pengajuan Pengembalian oleh Peminjam
if (isset($_GET['aksi']) && $_GET['aksi'] == 'ajukan_kembali' && isset($_GET['id'])) {
    $id_peminjaman = $_GET['id'];

    // Update status menjadi 'menunggu_pengecekan'
    mysqli_query($koneksi, "UPDATE peminjaman SET status = 'menunggu_pengecekan' WHERE id_peminjaman = '$id_peminjaman' AND id_user = '$id_user'");

    // Catat Log Aktivitas
    $akt = "Peminjam mengajukan pengembalian alat untuk ID Peminjaman: $id_peminjaman";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: pengembalian.php");
    exit;
}

// Query mengambil data peminjaman yang sedang aktif (disetujui), menunggu pengecekan, atau ditolak karena rusak agar bisa diajukan kembali
$query_pengembalian = mysqli_query($koneksi, "
    SELECT peminjaman.*, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    WHERE peminjaman.id_user = '$id_user' AND (peminjaman.status = 'disetujui' OR peminjaman.status = 'menunggu_pengecekan' OR peminjaman.status = 'ditolak_rusak')
    ORDER BY peminjaman.id_peminjaman DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian - Peminjam</title>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pengembalian.php">Pengembalian</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Login: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="mb-4 fw-bold">Daftar Alat yang Sedang Dipinjam</h3>

        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-secondary">Pengajuan Pengembalian Alat</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Nama Alat & Jumlah</th>
                                <th width="20%">Tgl Pinjam</th>
                                <th width="20%">Rencana Kembali</th>
                                <th width="15%">Status</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_pengembalian && mysqli_num_rows($query_pengembalian) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_pengembalian)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?></strong><br>
                                            <span class="badge bg-secondary">Qty: <?php echo $row['jumlah'] ?? 0; ?> unit</span>
                                        </td>
                                        <td><?php echo $row['tanggal_pinjam']; ?></td>
                                        <td><strong class="text-danger"><?php echo $row['tanggal_kembali_rencana']; ?></strong></td>
                                        <td>
                                            <?php if ($row['status'] == 'disetujui') : ?>
                                                <span class="badge bg-primary">DIPINJAM</span>
                                            <?php elseif ($row['status'] == 'ditolak_rusak') : ?>
                                                <span class="badge bg-danger">DITOLAK (RUSAK)</span>
                                            <?php else : ?>
                                                <span class="badge bg-warning text-dark">MENUNGGU PENGECEKAN</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'disetujui' || $row['status'] == 'ditolak_rusak') : ?>
                                                <a href="pengembalian.php?aksi=ajukan_kembali&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-warning btn-sm fw-bold" onclick="return confirm('Ajukan pengembalian alat ini ke petugas?')">Ajukan Kembali</a>
                                            <?php else : ?>
                                                <span class="text-muted small">Menunggu Petugas</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada alat yang sedang dipinjam saat ini.</td>
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