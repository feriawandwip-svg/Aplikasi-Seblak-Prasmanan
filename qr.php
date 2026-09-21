<?php
require __DIR__ . '/includes/db.php';

$meja = isset($_GET['meja']) && ctype_digit((string) $_GET['meja']) ? (int) $_GET['meja'] : 0;

$stmt = $pdo->prepare("SELECT id FROM meja WHERE nomor_meja = :nomor AND aktif = 1 LIMIT 1");
$stmt->execute([':nomor' => $meja]);
$ada = $stmt->fetch() !== false;

if (!$ada) {
    http_response_code(404);
    echo 'Meja tidak ditemukan.';
    exit;
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$dir = rtrim($dir, '/');
$url_target = $https . '://' . $host . $dir . '/index.php?meja=' . $meja;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Meja <?= $meja ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="halaman">
        <div class="judul">
            <div class="logo">S</div>
            <h1>QR Code Meja <?= $meja ?></h1>
            <p>Cetak dan tempel QR ini di meja <?= $meja ?>.</p>
        </div>

        <div class="kartu" style="text-align:center">
            <div id="kotak-qr"></div>
            <p class="kosong" style="padding:10px 0 0">
                Pelanggan scan QR untuk membuka halaman pesanan.<br>
                <?= htmlspecialchars($url_target) ?>
            </p>
        </div>
    </main>

    <script src="assets/vendor/qrcode.js"></script>
    <script>
        const url = <?= json_encode($url_target) ?>;
        const tipe = qrcode(0, 'M');
        tipe.addData(url);
        tipe.make();
        document.getElementById('kotak-qr').innerHTML = tipe.createImgTag(6, 6);
    </script>
</body>
</html>