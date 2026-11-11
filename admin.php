<?php
require_once 'config.php';
session_start();

$error = '';

// Jika tombol logout/ganti mode ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        unset($_SESSION['unlocked']);
        unset($_SESSION['guest_mode']);
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['login_key'])) {
        $inputPassword = trim($_POST['password']);
        
        // Jika dikosongkan, masuk sebagai guest (terenkripsi)
        if (empty($inputPassword)) {
            $_SESSION['guest_mode'] = true;
            unset($_SESSION['unlocked']);
            header("Location: admin.php");
            exit;
        }

        // Cek apakah password/kunci rahasia cocok
        if ($inputPassword === SECRET_KEY) {
            $_SESSION['unlocked'] = true;
            unset($_SESSION['guest_mode']);
            header("Location: admin.php");
            exit;
        } else {
            $error = "Kunci Rahasia / Password Salah!";
        }
    }
}

$isUnlocked = isset($_SESSION['unlocked']);
$isGuest = isset($_SESSION['guest_mode']) || !$isUnlocked;

// Handle Download File Asli (Hanya jika sudah membuka kunci)
if (isset($_GET['download']) && $isUnlocked) {
    $idFile = intval($_GET['download']);
    $stmt = $pdo->prepare("SELECT * FROM dokumen WHERE id = ?");
    $stmt->execute([$idFile]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doc && !empty($doc['file_terenkripsi'])) {
        $decodedFilePayload = base64_decode($doc['file_terenkripsi']);
        $ivLength = openssl_cipher_iv_length(AES_METHOD);
        $iv = substr($decodedFilePayload, 0, $ivLength);
        $encryptedData = substr($decodedFilePayload, $ivLength);
        
        $decryptedFileBinary = openssl_decrypt($encryptedData, AES_METHOD, SECRET_KEY, 0, $iv);

        if ($decryptedFileBinary !== false) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['nama_file_asli']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            echo $decryptedFileBinary;
            exit;
        }
    }
    die("Gagal mendekripsi file atau file tidak ditemukan.");
}

// Ambil data dari database
$documents = [];
if ($isUnlocked || $isGuest) {
    $stmt = $pdo->query("SELECT * FROM dokumen ORDER BY id DESC");
    $rawDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawDocs as $doc) {
        if ($isUnlocked) {
            $documents[] = [
                'id' => $doc['id'],
                'judul' => decryptData($doc['judul_terenkripsi']),
                'deskripsi' => decryptData($doc['deskripsi_terenkripsi']),
                'nama_file' => $doc['nama_file_asli'],
                'tanggal' => $doc['tanggal'],
                'decrypted' => true
            ];
        } else {
            $documents[] = [
                'id' => $doc['id'],
                'judul' => $doc['judul_terenkripsi'],
                'deskripsi' => $doc['deskripsi_terenkripsi'],
                'nama_file' => $doc['nama_file_asli'] ? "[File Terenkripsi]" : null,
                'tanggal' => $doc['tanggal'],
                'decrypted' => false
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VaultDoc</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #030712; color: #f3f4f6; }
        .glass-card { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(236, 72, 153, 0.25); box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5); }
        .form-label { color: #f472b6 !important; font-weight: 600; }
        .form-control { background-color: #020617 !important; border-color: rgba(236, 72, 153, 0.3) !important; color: #ffffff !important; }
        .form-control:focus { border-color: #ec4899 !important; box-shadow: 0 0 0 0.25rem rgba(236, 72, 153, 0.25) !important; }
        .btn-pink-gradient { background: linear-gradient(135deg, #db2777 0%, #be185d 100%); border: none; color: #ffffff; transition: all 0.2s; }
        .btn-pink-gradient:hover { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); color: #ffffff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4); }
        
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .custom-table th { background-color: rgba(2, 6, 23, 0.95); color: #f472b6; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 20px 24px; border-bottom: 2px solid rgba(236, 72, 153, 0.3); }
        .custom-table td { background-color: rgba(15, 23, 42, 0.7); padding: 24px; vertical-align: middle; border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: #f3f4f6; font-size: 15px; }
        .custom-table tr:hover td { background-color: rgba(15, 23, 42, 0.95); }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column justify-content-between">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary px-4 py-3 shadow-sm" style="border-color: rgba(236, 72, 153, 0.25) !important;">
        <div class="container-fluid px-3">
            <a class="navbar-brand fw-bold text-pink-400 d-flex align-items-center gap-2 fs-4" href="#" style="color: #f472b6 !important;">
                <span>🛡️</span> Portal Admin <span class="fw-light text-light" style="font-size: 1rem; opacity: 0.8;"></span>
            </a>
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="text-decoration-none small text-light opacity-75 fs-6">Kembali ke Beranda</a>
                <?php if ($isUnlocked || isset($_SESSION['guest_mode'])): ?>
                    <form action="" method="POST" class="m-0">
                        <button type="submit" name="logout" class="btn btn-outline-danger px-4 py-2 rounded-pill border-opacity-50 fs-6">Keluar / Ganti Mode</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- FORM LOGIN KUNCI RAHASIA -->
    <?php if (!$isUnlocked && !isset($_SESSION['guest_mode'])): ?>
        <main class="container-fluid px-4 my-auto py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="glass-card p-5 rounded-4 shadow-lg">
                        <div class="text-center mb-4">
                            <span class="fs-1">🔐</span>
                            <h1 class="h3 fw-bold text-white mt-2">Autentikasi Login</h1>
                            <p class="text-muted">Masukkan Kunci Rahasia.</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert py-3 rounded-3 mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171;">
                                ⚠️ <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-4">
                                <label class="form-label fs-6">Kunci Rahasia</label>
                                <input type="password" name="password" placeholder="Masukkan Kunci Rahasia..." class="form-control rounded-3 p-3">
                            </div>
                            <button type="submit" name="login_key" class="btn btn-pink-gradient w-100 fw-semibold py-3 fs-6 rounded-3 shadow mb-2">Masuk ke Dashboard</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>

    <!-- DASHBOARD ADMIN -->
    <?php else: ?>
        <main class="container-fluid px-4 px-lg-5 my-5 flex-grow-1">
            <div class="glass-card p-4 p-md-5 rounded-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 class="h2 fw-bold text-white mb-2">Dashboard Arsip Dokumen</h1>
                    <p class="fs-6 m-0 text-muted">
                        <?php if ($isUnlocked): ?>
                            Mode: <span class="text-success fw-semibold">Terdekripsi (Kunci Rahasia Aktif)</span>
                        <?php else: ?>
                            Mode: <span class="text-warning fw-semibold">Terenkripsi (Ciphertext Saja)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <?php if ($isUnlocked): ?>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success px-4 py-2.5 fs-6 fw-medium rounded-pill">🔓 Terdekripsi Aman</span>
                    <?php else: ?>
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning px-4 py-2.5 fs-6 fw-medium rounded-pill">🔒 Terenkripsi (Terkunci)</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($documents)): ?>
                <div class="text-center py-5 glass-card rounded-4">
                    <p class="fs-5 m-0 text-muted">Belum ada dokumen yang diarsipkan di dalam database.</p>
                </div>
            <?php else: ?>
                <div class="glass-card rounded-4 overflow-hidden shadow-lg">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Info Dokumen</th>
                                    <th style="width: 40%;">Deskripsi / Catatan Rahasia</th>
                                    <th style="width: 15%;">Tanggal Arsip</th>
                                    <th style="width: 15%;" class="text-end">File Terunggah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold fs-5 mb-1" style="color: <?= $doc['decrypted'] ? '#f472b6' : '#fbbf24' ?>;">
                                                <?= $doc['decrypted'] ? htmlspecialchars($doc['judul']) : '[Ciphertext] ' . htmlspecialchars(substr($doc['judul'], 0, 35)) . '...' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fs-6" style="color: <?= $doc['decrypted'] ? '#e2e8f0' : '#fbbf24' ?> !important; font-family: <?= $doc['decrypted'] ? 'inherit' : 'monospace' ?>; line-height: 1.6;">
                                                <?= htmlspecialchars($doc['deskripsi']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-black bg-opacity-70 px-3 py-2 rounded-pill fs-6 fw-medium" style="color: #cbd5e1 !important; border: 1px solid rgba(236, 72, 153, 0.35);">
                                                <?= htmlspecialchars($doc['tanggal']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!empty($doc['nama_file'])): ?>
                                                <?php if ($doc['decrypted']): ?>
                                                    <a href="admin.php?download=<?= $doc['id'] ?>" class="btn btn-outline-light px-4 py-2 rounded-pill border-opacity-50 d-inline-flex align-items-center gap-2 fw-medium text-decoration-none fs-6" style="color: #f472b6; border-color: rgba(236, 72, 153, 0.4);">
                                                        <span>📄</span> <span><?= htmlspecialchars($doc['nama_file']) ?></span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-25 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill fs-6 font-monospace">
                                                        🔒 Terenkripsi
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted fs-6 fst-italic">Tidak ada file</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="text-center py-4 fs-6 border-top border-secondary bg-black" style="border-color: rgba(236, 72, 153, 0.2) !important; color: #94a3b8;">
        VaultDoc Secure Archiving System • Symmetric AES-256 Encryption
    </footer>
</body>
</html>