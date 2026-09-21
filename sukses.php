<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$kode = isset($_GET['kode']) ? trim((string) $_GET['kode']) : '';

if ($kode === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE kode = :kode LIMIT 1");
$stmt->execute([':kode' => $kode]);
$pesanan = $stmt->fetch();

if ($pesanan === false) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pesanan_detail WHERE id_pesanan = :idp ORDER BY id");
$stmt->execute([':idp' => (int) $pesanan['id']]);
$detail = $stmt->fetchAll();

$kuah_nama = '-';
if ($pesanan['id_kuah']) {
    $stmt = $pdo->prepare("SELECT nama FROM kuah WHERE id = :id");
    $stmt->execute([':id' => (int) $pesanan['id_kuah']]);
    $row = $stmt->fetch();
    if ($row) $kuah_nama = $row['nama'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Diterima</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="halaman">
        <div class="judul">
            <div class="logo">S</div>
            <h1>Pesanan Diterima!</h1>
            <p>Simpan kode di bawah untuk mengecek pesanan.</p>
        </div>

        <div class="kartu">
            <div class="blok-kode">
                <span class="label">Kode Pesanan</span>
                <strong class="kode"><?= htmlspecialchars($pesanan['kode']) ?></strong>
                <span class="label">Total Bayar</span>
                <strong class="total"><?= rupiah($pesanan['tot_harga']) ?></strong>
            </div>

            <div class="baris-opsi">
                <span>Pelanggan</span>
                <strong><?= htmlspecialchars($pesanan['nama_pelanggan']) ?></strong>
            </div>
            <div class="baris-opsi">
                <span>Tipe</span>
                <strong>
                    <?= $pesanan['tipe'] === 'takeaway' ? 'Takeaway' : 'Meja ' . (int) $pesanan['no_meja'] ?>
                </strong>
            </div>
            <div class="baris-opsi">
                <span>Kuah / Level</span>
                <strong><?= htmlspecialchars($kuah_nama) ?> &middot; Level <?= (int) $pesanan['level'] ?></strong>
            </div>

            <div class="daftar-cart" style="margin-top:12px">
                <?php foreach ($detail as $d): ?>
                    <div class="baris-cart">
                        <div class="ket">
                            <span class="nama"><?= htmlspecialchars($d['nama_barang']) ?></span>
                            <span class="sub"><?= rupiah($d['harga_satuan']) ?> &times; <?= (int) $d['qty'] ?></span>
                        </div>
                        <strong><?= rupiah($d['subtotal']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="kaki-panel">
                <span>Status Pembayaran</span>
                <strong class="pil-status pil-pending">Pending &middot; Bayar di Kasir</strong>
            </div>
        </div>

        <div class="kartu" style="text-align:center">
            <p class="kosong" style="padding:0 0 10px">
                Silakan bayar tunai di kasir dengan menyebutkan kode atau nama Anda.
            </p>
            <a class="tombol" href="index.php" style="display:inline-block;text-decoration:none;text-align:center">
                Pesan Lagi
            </a>
        </div>
    </main>
</body>
</html>