<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../index.php");
    exit;
}

// Proses Aksi Validasi (Setujui / Tolak)
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_peminjaman = $_GET['id'];
    $aksi = $_GET['aksi']; // 'disetujui' atau 'ditolak'

    if ($aksi == 'disetujui') {
        // Ambil data detail peminjaman untuk cek stok
        $qDetail = mysqli_query($koneksi, "SELECT * FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
        $dDetail = mysqli_fetch_assoc($qDetail);
        $id_alat = $dDetail['id_alat'];
        $jumlah_pinjam = $dDetail['jumlah'];

        // Cek stok alat saat ini
        $qAlat = mysqli_query($koneksi, "SELECT stok, nama_alat FROM alat WHERE id_alat = '$id_alat'");
        $dAlat = mysqli_fetch_assoc($qAlat);

        if ($dAlat['stok'] < $jumlah_pinjam) {
            $error = "Stok alat '" . $dAlat['nama_alat'] . "' tidak mencukupi untuk disetujui! Sisa stok: " . $dAlat['stok'];
        } else {
            // Kurangi stok alat
            mysqli_query($koneksi, "UPDATE alat SET stok = stok - $jumlah_pinjam WHERE id_alat = '$id_alat'");
            // Update status peminjaman jadi disetujui
            mysqli_query($koneksi, "UPDATE peminjaman SET status = 'disetujui' WHERE id_peminjaman = '$id_peminjaman'");

            // Catat Log Aktivitas
            $id_petugas = $_SESSION['id_user'];
            $akt = "Petugas menyetujui peminjaman ID: $id_peminjaman";
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_petugas', '$akt', NOW())");

            header("Location: peminjaman.php");
            exit;
        }
    } elseif ($aksi == 'ditolak') {
        // Update status peminjaman jadi ditolak
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'ditolak' WHERE id_peminjaman = '$id_peminjaman'");

        // Catat Log Aktivitas
        $id_petugas = $_SESSION['id_user'];
        $akt = "Petugas menolak peminjaman ID: $id_peminjaman";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_petugas', '$akt', NOW())");

        header("Location: peminjaman.php");
        exit;
    }
}

// Query mengambil semua data peminjaman
$query_peminjaman = mysqli_query($koneksi, "
    SELECT peminjaman.*, user.username AS nama_peminjam, alat.nama_alat, detail_peminjaman.jumlah 
    FROM peminjaman 
    JOIN user ON peminjaman.id_user = user.id_user 
    LEFT JOIN detail_peminjaman ON peminjaman.id_peminjaman = detail_peminjaman.id_peminjaman 
    LEFT JOIN alat ON detail_peminjaman.id_alat = alat.id_alat 
    ORDER BY peminjaman.id_peminjaman DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Peminjaman - Panel Petugas</title>
    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar Petugas Warna Hitam -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">PANEL PETUGAS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="peminjaman.php">Validasi Peminjaman</a></li>
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
        <h3 class="mb-4 fw-bold">Validasi Peminjaman Alat</h3>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-secondary">Daftar Pengajuan Peminjaman</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Peminjam</th>
                                <th width="25%">Detail Alat & Jumlah</th>
                                <th width="20%">Tgl Pinjam / Rencana</th>
                                <th width="15%">Status</th>
                                <th width="20%" class="text-center">Aksi / Validasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_peminjaman && mysqli_num_rows($query_peminjaman) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_peminjaman)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_peminjam']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['nama_alat'] ?? '-'); ?><br>
                                            <span class="badge bg-secondary">Qty: <?php echo $row['jumlah'] ?? 0; ?> unit</span>
                                        </td>
                                        <td>
                                            <small>Pinjam: <?php echo $row['tanggal_pinjam']; ?></small><br>
                                            <small>Rencana: <?php echo $row['tanggal_kembali_rencana']; ?></small>
                                        </td>
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
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'pending') : ?>
                                                <a href="peminjaman.php?aksi=disetujui&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-success btn-sm mb-1" onclick="return confirm('Setujui peminjaman ini?')">Setujui</a>
                                                <a href="peminjaman.php?aksi=ditolak&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Tolak peminjaman ini?')">Tolak</a>
                                            <?php else : ?>
                                                <span class="text-muted small">Telah diproses</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada pengajuan peminjaman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap 5 Offline -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>