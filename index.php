<?php
session_start();
include "koneksi.php";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password']; // Password yang diinput user

    $result = mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'");
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Cek password menggunakan md5() atau sesuaikan dengan database kamu
        // (Jika di database password-nya di-md5, maka md5($password) dicocokkan dengan $row['password'])
        if (md5($password) === $row['password'] || $password === $row['password']) {
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Catat ke Log Aktivitas
            $id_usr = $row['id_user'];
            $akt = "Pengguna melakukan Login ke sistem";
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_usr', '$akt', NOW())");

            // Redirect ke folder role masing-masing
            if ($row['role'] == 'admin') {
                header("Location: admin/index.php");
                exit;
            } elseif ($row['role'] == 'petugas') {
                header("Location: petugas/index.php");
                exit;
            } elseif ($row['role'] == 'peminjam') {
                header("Location: peminjam/index.php");
                exit;
            }
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman Alat</title>
    <!-- Bootstrap 5 Offline -->
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center py-5" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4 fw-bold">Login Sistem</h4>
                        
                        <?php if (isset($error)) : ?>
                            <div class="alert alert-danger py-2 text-center" role="alert">
                                Username atau Password salah!
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 py-2">MASUK</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Script Bootstrap 5 Offline -->
    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>