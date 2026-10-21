<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: ../index.php");
    exit;
}

$id_user =$_SESSION['id_user'];

// Inisialisasi Keranjang Peminjaman di Session jika belum ada
if (!isset($_SESSION['keranjang'])) {$_SESSION['keranjang'] = [];
}

// Aksi Tambah ke Keranjang Sementara
if (isset($_POST['tambah_keranjang'])) {
    $id_alat =$_POST['id_alat'];
    $jumlah =$_POST['jumlah'];

    // Cek Stok Alat di Database
    $qStok = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat = '$id_alat'");
    $dStok = mysqli_fetch_assoc($qStok);

    if ($dStok) {
        $nama_alat =$dStok['nama_alat'];
        $stok_tersedia =$dStok['stok'];

        // Cek apakah jumlah melebihi stok
        if ($jumlah > $stok_tersedia) {$error = "Jumlah pinjam untuk alat '$nama_alat' melebihi sisa stok ($stok_tersedia unit)!";
        } else {
            // Cek apakah alat sudah ada di keranjang, jika ya update jumlahnya atau tambahkan baru
            $found = false;
            foreach ($_SESSION['keranjang'] as $key =>$item) {
                if ($item['id_alat'] ==$id_alat) {
                    // Cek total jika ditambahkan
                    if (($item['jumlah'] +$jumlah) > $stok_tersedia) {$error = "Total jumlah di keranjang melebihi sisa stok yang tersedia!";
                        $found = true;
                        break;
                    }
                    $_SESSION['keranjang'][$key]['jumlah'] +=$jumlah;
                    $found = true;
                    break;
                }
            }

            if (!$found) {$_SESSION['keranjang'][] = [
                    'id_alat' => $id_alat,
                    'nama_alat' => $nama_alat,
                    'jumlah' => $jumlah
                ];
            }
        }
    }
}

// Aksi Hapus Item dari Keranjang
if (isset($_GET['hapus'])) {
    $index =$_GET['hapus'];
    unset($_SESSION['keranjang'][$index]);
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']); // Re-index array
    header("Location: peminjaman.php");
    exit;
}

// Aksi Final Kirim Pengajuan Peminjaman Semua Alat di Keranjang
if (isset($_POST['kirim_pengajuan'])) {
    if (empty($_SESSION['keranjang'])) {$error = "Keranjang peminjaman masih kosong!";
    } else {
        $tgl_pinjam = date('Y-m-d');
        $tgl_rencana =$_POST['tanggal_kembali_rencana'];

        // 1. Simpan ke tabel peminjaman (status awal pending)
        mysqli_query($koneksi, "INSERT INTO peminjaman (id_user, tanggal_pinjam, tanggal_kembali_rencana, status) VALUES ('$id_user', '$tgl_pinjam', '$tgl_rencana', 'pending')");
        $id_peminjaman_baru = mysqli_insert_id($koneksi);

        // 2. Masukkan semua item dari keranjang ke tabel detail_peminjaman
        foreach ($_SESSION['keranjang'] as$item) {
            $id_alat =$item['id_alat'];
            $jumlah =$item['jumlah'];
            mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah) VALUES ('$id_peminjaman_baru', '$id_alat', '$jumlah')");
        }

        // 3. Catat Log Aktivitas
        $akt = "Peminjam mengajukan peminjaman multi-alat (ID Peminjaman: $id_peminjaman_baru)";
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas, waktu) VALUES ('$id_user', '$akt', NOW())");

        // 4. Kosongkan keranjang
        unset($_SESSION['keranjang']);

        header("Location: index.php");
        exit;
    }
}

// Query Daftar Alat & Kategori
$query_alat = mysqli_query($koneksi, "SELECT alat.*, kategori.nama_kategori FROM alat LEFT JOIN kategori ON alat.id_kategori = kategori.id_kategori ORDER BY alat.nama_alat ASC");
$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Peminjaman - Peminjam</title>
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
                    <li class="nav-item"><a class="nav-link active" href="peminjaman.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="pengembalian.php">Pengembalian</a></li>
                </ul>
                <span class="navbar-text text-white me-3">
                    Login: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                </span>
                <a href="../index.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Daftar Alat & Keranjang Peminjaman</h3>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahAlat">
                + Pilih & Tambah Alat
            </button>
        </div>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Daftar Alat & Stok Terbaru -->
        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-secondary">Katalog Alat Tersedia</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Nama Alat</th>
                                <th width="25%">Kategori</th>
                                <th width="20%">Stok Terbaru</th>
                                <th width="20%">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_alat && mysqli_num_rows($query_alat) > 0) : ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($query_alat)) : ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_alat']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['nama_kategori'] ?? 'Umum'); ?></td>
                                        <td>
                                            <?php if($row['stok'] > 0): ?>
                                                <span class="badge bg-success fs-6"><?php echo $row['stok']; ?> unit</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger fs-6">Habis</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['kondisi'] ?? 'Baik'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data alat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Keranjang / List Alat Yang Dipilih -->
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="mb-0 fw-bold">Daftar Alat yang Akan Dipinjam (Keranjang)</h5>
            </div>
            <div class="card-body">
                <form action="peminjaman.php" method="POST">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-secondary">
                                >
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="50%">Nama Alat</th>
                                    <th width="25%">Jumlah</th>
                                    <th width="20%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['keranjang'])) : ?>
                                    <?php $no = 1; foreach ($_SESSION['keranjang'] as $idx =>$item) : ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($item['nama_alat']); ?></strong></td>
                                            <td><?php echo $item['jumlah']; ?> unit</td>
                                            <td class="text-center">
                                                <a href="peminjaman.php?hapus=<?php echo $idx; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus alat ini dari daftar pinjam?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Keranjang peminjaman masih kosong. Silakan klik tombol "+ Pilih & Tambah Alat".</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($_SESSION['keranjang'])) : ?>
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Tanggal Rencana Kembali</label>
                                <input type="date" name="tanggal_kembali_rencana" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="submit" name="kirim_pengajuan" class="btn btn-success w-100 fw-bold">Kirim Pengajuan Peminjaman</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Alat ke Keranjang dengan Filter Kategori -->
    <div class="modal fade" id="modalTambahAlat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="peminjaman.php" method="POST">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold">Pilih Kategori & Alat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Pilih Kategori -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Kategori</label>
                            <select id="pilih_kategori" class="form-select" onchange="filterAlat()" required>
                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                <?php 
                                mysqli_data_seek($query_kategori, 0);
                                while($kat = mysqli_fetch_assoc($query_kategori)) : 
                                ?>
                                    <option value="<?php echo $kat['id_kategori']; ?>"><?php echo htmlspecialchars($kat['nama_kategori']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Pilih Alat -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Alat</label>
                            <select name="id_alat" id="pilih_alat" class="form-select" onchange="updateMaxStok()" required>
                                <option value="" disabled selected>-- Pilih Kategori Terlebih Dahulu --</option>
                                <?php 
                                mysqli_data_seek($query_alat, 0); 
                                while($a = mysqli_fetch_assoc($query_alat)) : 
                                    if($a['stok'] > 0) :
                                ?>
                                    <option value="<?php echo $a['id_alat']; ?>" data-kategori="<?php echo $a['id_kategori']; ?>" data-stok="<?php echo $a['stok']; ?>">
                                        <?php echo $a['nama_alat']; ?> (Sisa Stok: <?php echo$a['stok']; ?>)
                                    </option>
                                <?php 
                                    endif;
                                endwhile; 
                                ?>
                            </select>
                        </div>

                        <!-- Jumlah Pinjam -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jumlah Pinjam</label>
                            <input type="number" name="jumlah" id="input_jumlah" class="form-control" min="1" value="1" required>
                            <small id="info_stok" class="text-muted"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_keranjang" class="btn btn-primary">Masukkan ke Daftar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script JavaScript Filter Kategori & Stok -->
    <script>
    const selectAlat = document.getElementById('pilih_alat');
    const semuaOpsiAlat = Array.from(selectAlat.options);
    const inputJumlah = document.getElementById('input_jumlah');
    const infoStok = document.getElementById('info_stok');

    function filterAlat() {
        const idKategoriDipilih = document.getElementById('pilih_kategori').value;
        
        selectAlat.innerHTML = '<option value="" disabled selected>-- Pilih Alat --</option>';
        inputJumlah.value = 1;
        inputJumlah.removeAttribute('max');
        infoStok.textContent = '';

        semuaOpsiAlat.forEach(option => {
            if (option.getAttribute('data-kategori') === idKategoriDipilih) {
                selectAlat.appendChild(option);
            }
        });
    }

    function updateMaxStok() {
        const selectedOption = selectAlat.options[selectAlat.selectedIndex];
        const stokTersedia = parseInt(selectedOption.getAttribute('data-stok')) || 0;
        
        inputJumlah.max = stokTersedia;
        infoStok.textContent = `Maksimal pinjam: ${stokTersedia} unit`;

        if (parseInt(inputJumlah.value) > stokTersedia) {
            inputJumlah.value = stokTersedia;
        }
    }
    </script>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>