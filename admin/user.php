<?php
session_start();
include "../koneksi.php";

// Cek apakah user sudah login dan benar-benar admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Proses Tambah User
if (isset($_POST['tambah'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']); // Enkripsi MD5
    $role     = $_POST['role'];

    mysqli_query($koneksi, "INSERT INTO user (username, password, role) VALUES ('$username', '$password', '$role')");
    
    // Catat log
    $id_user = $_SESSION['id_user'];
    $akt = "Menambahkan user baru: $username (Role: $role)";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: user.php");
    exit;
}

// Proses Edit / Update User
if (isset($_POST['edit'])) {
    $id_user_target = $_POST['id_user'];
    $username       = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role           = $_POST['role'];

    // Cek apakah password diisi atau tidak (jika kosong, password tidak diubah)
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        mysqli_query($koneksi, "UPDATE user SET username = '$username', password = '$password', role = '$role' WHERE id_user = '$id_user_target'");
    } else {
        mysqli_query($koneksi, "UPDATE user SET username = '$username', role = '$role' WHERE id_user = '$id_user_target'");
    }

    // Catat log
    $id_admin = $_SESSION['id_user'];
    $akt = "Mengubah data user: $username";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_admin', '$akt', NOW())");

    header("Location: user.php");
    exit;
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id_target = $_GET['hapus'];
    
    $getData = mysqli_query($koneksi, "SELECT username FROM user WHERE id_user = '$id_target'");
    $rowTarget = mysqli_fetch_assoc($getData);
    $targetName = $rowTarget['username'] ?? 'Unknown';

    mysqli_query($koneksi, "DELETE FROM user WHERE id_user = '$id_target'");
    
    // Catat log
    $id_user = $_SESSION['id_user'];
    $akt = "Menghapus user: $targetName";
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

    header("Location: user.php");
    exit;
}

// Ambil data untuk mode edit jika tombol edit diklik
$editData = null;
if (isset($_GET['ubah'])) {
    $id_ubah = $_GET['ubah'];
    $query_edit = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user = '$id_ubah'");
    $editData = mysqli_fetch_assoc($query_edit);
}

// Ambil semua data user
$query_user = mysqli_query($koneksi, "SELECT * FROM user ORDER BY id_user DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD User - Panel Admin</title>
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
        <h3 class="mb-4 fw-bold">Kelola Data User</h3>

        <div class="row">
            <!-- Form Tambah / Edit User -->
            <div class="col-md-4 mb-4">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">
                            <?php echo isset($editData) ? 'Edit Data User' : 'Tambah User Baru'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <!-- Input hidden untuk ID saat mode edit -->
                            <?php if (isset($editData)) : ?>
                                <input type="hidden" name="id_user" value="<?php echo $editData['id_user']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" value="<?php echo isset($editData) ? htmlspecialchars($editData['username']) : ''; ?>" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" <?php echo isset($editData) ? '' : 'required'; ?>>
                                <div class="form-text">
                                    <?php echo isset($editData) ? 'Kosongkan jika tidak ingin mengubah password.' : 'Otomatis dienkripsi dengan MD5.'; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role Akses</label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="" disabled>Pilih Role...</option>
                                    <option value="admin" <?php echo (isset($editData) && $editData['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="petugas" <?php echo (isset($editData) && $editData['role'] == 'petugas') ? 'selected' : ''; ?>>Petugas</option>
                                    <option value="peminjam" <?php echo (isset($editData) && $editData['role'] == 'peminjam') ? 'selected' : ''; ?>>Peminjam</option>
                                </select>
                            </div>

                            <?php if (isset($editData)) : ?>
                                <button type="submit" name="edit" class="btn btn-success w-100 mb-2">Perbarui User</button>
                                <a href="user.php" class="btn btn-secondary w-100">Batal</a>
                            <?php else : ?>
                                <button type="submit" name="tambah" class="btn btn-primary w-100">Simpan User</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar User -->
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-secondary">Daftar Pengguna Sistem</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="30%">Username</th>
                                        <th width="25%">Role</th>
                                        <th width="40%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($query_user) > 0) : ?>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query_user)) : ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                                <td>
                                                    <?php 
                                                        $badgeBg = 'bg-secondary';
                                                        if($row['role'] == 'admin') $badgeBg = 'bg-danger';
                                                        elseif($row['role'] == 'petugas') $badgeBg = 'bg-warning text-dark';
                                                        elseif($row['role'] == 'peminjam') $badgeBg = 'bg-success';
                                                    ?>
                                                    <span class="badge <?php echo $badgeBg; ?> text-uppercase"><?php echo $row['role']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="user.php?ubah=<?php echo $row['id_user']; ?>" class="btn btn-warning btn-sm text-white">Edit</a>
                                                    <a href="user.php?hapus=<?php echo $row['id_user']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada data user.</td>
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