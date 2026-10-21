<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Proses Tambah Alat
if (isset($_POST['tambah'])) {
    $id_kategori = $_POST['id_kategori'];
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $stok        = (int)$_POST['stok'];
    $kondisi     = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    mysqli_query($koneksi, "INSERT INTO alat (id_kategori, nama_alat, stok, kondisi) VALUES ('$id_kategori', '$nama_alat', '$stok', '$kondisi')");
    
    // Catat log aktivitas (menggunakan kolom 'aktivitas')
    $id_user = $_SESSION['id_user'];
    $akt = "Menambahkan alat baru: $nama_alat (Stok: $stok)";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: alat.php");
    exit;
}

// Proses Edit / Update Alat
if (isset($_POST['edit'])) {
    $id_alat     = $_POST['id_alat'];
    $id_kategori = $_POST['id_kategori'];
    $nama_alat   = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $stok        = (int)$_POST['stok'];
    $kondisi     = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    mysqli_query($koneksi, "UPDATE alat SET id_kategori = '$id_kategori', nama_alat = '$nama_alat', stok = '$stok', kondisi = '$kondisi' WHERE id_alat = '$id_alat'");

    // Catat log aktivitas
    $id_user = $_SESSION['id_user'];
    $akt = "Mengubah data alat: $nama_alat";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: alat.php");
    exit;
}

// Proses Hapus Alat
if (isset($_GET['hapus'])) {
    $id_target = $_GET['hapus'];

    $getData = mysqli_query($koneksi, "SELECT nama_alat FROM alat WHERE id_alat = '$id_target'");
    $rowTarget = mysqli_fetch_assoc($getData);
    $targetName = $rowTarget['nama_alat'] ?? 'Unknown';

    mysqli_query($koneksi, "DELETE FROM alat WHERE id_alat = '$id_target'");

    // Catat log aktivitas
    $id_user = $_SESSION['id_user'];
    $akt = "Menghapus alat: $targetName";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: alat.php");
    exit;
}

// Ambil data alat untuk mode edit jika tombol edit diklik
$editData = null;
if (isset($_GET['ubah'])) {
    $id_ubah = $_GET['ubah'];
    $query_edit = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id_ubah'");
    $editData = mysqli_fetch_assoc($query_edit);
}

// Ambil data opsi kategori untuk dropdown form
$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Ambil semua data alat digabung dengan nama kategori
$query_alat = mysqli_query($koneksi, "SELECT alat.*, kategori.nama_kategori FROM alat LEFT JOIN kategori ON alat.id_kategori = kategori.id_kategori ORDER BY alat.id_alat DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Alat - Panel Admin</title>
    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
</head>
<body class="bg-light">

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
        <h3 class="mb-4 fw-bold">Kelola Data Alat</h3>

        <div class="row">
            <!-- Form Tambah / Edit Alat -->
            <div class="col-md-4 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">
                            <?php echo isset($editData) ? 'Edit Data Alat' : 'Tambah Alat Baru'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php if (isset($editData)) : ?>
                                <input type="hidden" name="id_alat" value="<?php echo $editData['id_alat']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="id_kategori" class="form-label">Kategori Alat</label>
                                <select name="id_kategori" id="id_kategori" class="form-select" required>
                                    <option value="" disabled selected>Pilih Kategori...</option>
                                    <?php 
                                    mysqli_data_seek($query_kategori, 0); // Reset pointer query
                                    while($kat = mysqli_fetch_assoc($query_kategori)) : 
                                        $selected = (isset($editData) && $editData['id_kategori'] == $kat['id_kategori']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $kat['id_kategori']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nama_alat" class="form-label">Nama Alat</label>
                                <input type="text" name="nama_alat" id="nama_alat" class="form-control" value="<?php echo isset($editData) ? htmlspecialchars($editData['nama_alat']) : ''; ?>" required autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="stok" class="form-label">Jumlah Stok</label>
                                <input type="number" name="stok" id="stok" class="form-control" min="0" value="<?php echo isset($editData) ? $editData['stok'] : '1'; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="kondisi" class="form-label">Kondisi Alat</label>
                                <input type="text" name="kondisi" id="kondisi" class="form-control" placeholder="Contoh: Baik, Perlu Perbaikan" value="<?php echo isset($editData) ? htmlspecialchars($editData['kondisi']) : 'Baik'; ?>" required>
                            </div>

                            <?php if (isset($editData)) : ?>
                                <button type="submit" name="edit" class="btn btn-success w-100 mb-2">Perbarui Alat</button>
                                <a href="alat.php" class="btn btn-secondary w-100">Batal</a>
                            <?php else : ?>
                                <button type="submit" name="tambah" class="btn btn-primary w-100">Simpan Alat</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Alat -->
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">Daftar Alat</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="25%">Nama Alat</th>
                                        <th width="20%">Kategori</th>
                                        <th width="15%">Stok</th>
                                        <th width="15%">Kondisi</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($query_alat) > 0) : ?>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_alat)) : ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['nama_alat']); ?></strong></td>
                                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori'); ?></span></td>
                                                <td>
                                                    <?php if($row['stok'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $row['stok']; ?> unit</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['kondisi']); ?></td>
                                                <td class="text-center">
                                                    <a href="alat.php?ubah=<?php echo $row['id_alat']; ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                                    <a href="alat.php?hapus=<?php echo $row['id_alat']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus alat ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada data alat.</td>
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