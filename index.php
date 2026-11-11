<?php
require_once 'config.php';

$message = '';
$statusType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);

    if (!empty($judul) && !empty($deskripsi)) {
        // Enkripsi teks dengan fungsi simetris AES
        $judulEnc = encryptData($judul);
        $deskripsiEnc = encryptData($deskripsi);
        
        $namaFileAsli = null;
        $fileEnc = null;

        // Proses File Upload (AES langsung mengenkripsi file dengan mudah karena tidak ada batasan ukuran RSA)
        if (isset($_FILES['dokumen_file']) && $_FILES['dokumen_file']['error'] === UPLOAD_ERR_OK) {
            $namaFileAsli = $_FILES['dokumen_file']['name'];
            $fileTmp = $_FILES['dokumen_file']['tmp_name'];
            
            // Enkripsi file langsung menggunakan AES
            $fileData = file_get_contents($fileTmp);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_METHOD));
            $encryptedFile = openssl_encrypt($fileData, AES_METHOD, SECRET_KEY, 0, $iv);
            $fileEnc = base64_encode($iv . $encryptedFile);
        }

        $stmt = $pdo->prepare("INSERT INTO dokumen (judul_terenkripsi, deskripsi_terenkripsi, nama_file_asli, file_terenkripsi) VALUES (?, ?, ?, ?)");
        $stmt->execute([$judulEnc, $deskripsiEnc, $namaFileAsli, $fileEnc]);
        
        $message = "Dokumen rahasia dan file berhasil dienkripsi secara simetris dan diarsipkan!";
        $statusType = "success";
    } else {
        $message = "Judul dan deskripsi wajib diisi!";
        $statusType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VaultDoc - Enkripsi Simetris (AES)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #030712; color: #f3f4f6; }
        .glass-card { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(236, 72, 153, 0.25); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
        .form-label { color: #f472b6 !important; font-weight: 600; }
        .form-control { background-color: #020617 !important; border-color: rgba(236, 72, 153, 0.3) !important; color: #ffffff !important; }
        .form-control:focus { border-color: #ec4899 !important; box-shadow: 0 0 0 0.25rem rgba(236, 72, 153, 0.25) !important; }
        .form-control::placeholder { color: #64748b !important; }
        .btn-pink-gradient { background: linear-gradient(135deg, #db2777 0%, #be185d 100%); border: none; color: #ffffff; transition: all 0.2s ease-in-out; }
        .btn-pink-gradient:hover { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: #ffffff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4); }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary px-4 py-3" style="border-color: rgba(236, 72, 153, 0.2) !important;">
        <div class="container-fluid px-3">
            <a class="navbar-brand fw-bold text-pink-400 d-flex align-items-center gap-2" href="#" style="color: #f472b6 !important;">
                <span>🔒</span> VaultDoc Simetris (AES-256) <span class="fw-light text-light" style="font-size: 0.85rem; opacity: 0.8;"></span>
            </a>
            <a href="admin.php" class="btn btn-outline-light btn-sm px-3 rounded-pill border-opacity-50 text-light" style="border-color: #f472b6; color: #f472b6;">Halaman Admin</a>
        </div>
    </nav>

    <!-- Main Section -->
    <main class="container-fluid px-4 my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 col-xl-6">
                <div class="glass-card p-4 p-md-5 rounded-4">
                    <div class="mb-4">
                        <span class="badge mb-2 px-3 py-1.5 rounded-pill" style="background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.3); font-size: 11px; font-weight: 600;">SIMMMETRIC ENCRYPTION PORTAL</span>
                        <h1 class="h3 fw-bold text-white mb-1">Arsipkan Dokumen & File</h1>
                        <p class="small mb-0 text-muted">Sistem menggunakan Kunci Rahasia tunggal.</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <?php if ($statusType === 'success'): ?>
                            <div class="alert py-2.5 small rounded-3 mb-4" role="alert" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399;">
                                ✨ <?= htmlspecialchars($message) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert py-2.5 small rounded-3 mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171;">
                                ⚠️ <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small">Judul Dokumen</label>
                            <input type="text" name="judul" required class="form-control py-2.5 rounded-3" placeholder="Masukkan judul dokumen rahasia...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Deskripsi / Catatan Rahasia</label>
                            <textarea name="deskripsi" rows="4" required class="form-control py-2.5 rounded-3" placeholder="Tulis catatan atau deskripsi dokumen di sini..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small">Unggah File (PDF, DOCX, TXT, Gambar, dll)</label>
                            <input type="file" name="dokumen_file" class="form-control py-2.5 rounded-3 text-light">
                        </div>
                        <button type="submit" class="btn btn-pink-gradient w-100 fw-semibold py-3 rounded-3 shadow">Enkripsi & Simpan Arsip</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-4 small border-top border-secondary bg-black" style="border-color: rgba(236, 72, 153, 0.2) !important; color: #94a3b8;">
        VaultDoc Secure Archiving System • Symmetric AES-256 Encryption
    </footer>
</body>
</html>