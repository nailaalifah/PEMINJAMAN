<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Proses Tambah Kategori
if (isset($_POST['tambah'])) {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);

    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')");
    
    // Catat log aktivitas
    $id_user = $_SESSION['id_user'];
    $akt = "Menambahkan kategori baru: $nama_kategori";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: kategori.php");
    exit;
}

// Proses Edit / Update Kategori
if (isset($_POST['edit'])) {
    $id_kategori   = $_POST['id_kategori'];
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);

    mysqli_query($koneksi, "UPDATE kategori SET nama_kategori = '$nama_kategori' WHERE id_kategori = '$id_kategori'");

    // Catat log aktivitas
    $id_user = $_SESSION['id_user'];
    $akt = "Mengubah kategori menjadi: $nama_kategori";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: kategori.php");
    exit;
}

// Proses Hapus Kategori
if (isset($_GET['hapus'])) {
    $id_target = $_GET['hapus'];

    $getData = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori WHERE id_kategori = '$id_target'");
    $rowTarget = mysqli_fetch_assoc($getData);
    $targetName = $rowTarget['nama_kategori'] ?? 'Unknown';

    mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori = '$id_target'");

    // Catat log aktivitas
    $id_user = $_SESSION['id_user'];
    $akt = "Menghapus kategori: $targetName";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: kategori.php");
    exit;
}

// Ambil data untuk mode edit jika tombol edit diklik
$editData = null;
if (isset($_GET['ubah'])) {
    $id_ubah = $_GET['ubah'];
    $query_edit = mysqli_query($koneksi, "SELECT * FROM kategori WHERE id_kategori = '$id_ubah'");
    $editData = mysqli_fetch_assoc($query_edit);
}

// Ambil semua data kategori
$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Kategori - Panel Admin</title>
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
        <h3 class="mb-4 fw-bold">Kelola Data Kategori Alat</h3>

        <div class="row">
            <!-- Form Tambah / Edit Kategori -->
            <div class="col-md-4 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">
                            <?php echo isset($editData) ? 'Edit Kategori Alat' : 'Tambah Kategori Baru'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php if (isset($editData)) : ?>
                                <input type="hidden" name="id_kategori" value="<?php echo $editData['id_kategori']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="nama_kategori" class="form-label">Nama Kategori</label>
                                <input type="text" name="nama_kategori" id="nama_kategori" class="form-control" value="<?php echo isset($editData) ? htmlspecialchars($editData['nama_kategori']) : ''; ?>" required autocomplete="off">
                            </div>

                            <?php if (isset($editData)) : ?>
                                <button type="submit" name="edit" class="btn btn-success w-100 mb-2">Perbarui Kategori</button>
                                <a href="kategori.php" class="btn btn-secondary w-100">Batal</a>
                            <?php else : ?>
                                <button type="submit" name="tambah" class="btn btn-primary w-100">Simpan Kategori</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Kategori -->
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">Daftar Kategori Alat</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="10%">No</th>
                                        <th width="60%">Nama Kategori</th>
                                        <th width="30%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($query_kategori) > 0) : ?>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_kategori)) : ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['nama_kategori']); ?></strong></td>
                                                <td class="text-center">
                                                    <a href="kategori.php?ubah=<?php echo $row['id_kategori']; ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                                    <a href="kategori.php?hapus=<?php echo $row['id_kategori']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Belum ada data kategori.</td>
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