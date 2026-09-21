<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$orders = $pdo->query(
    "SELECT p.*, k.nama AS kuah_nama
     FROM pesanan p
     LEFT JOIN kuah k ON k.id = p.id_kuah
     ORDER BY p.id DESC
     LIMIT 50"
)->fetchAll();

$detail_stmt = $pdo->prepare("SELECT nama_barang, harga_satuan, qty, subtotal FROM pesanan_detail WHERE id_pesanan = :idp ORDER BY id");

$pending = [];
$lunas = [];
foreach ($orders as $o) {
    if ($o['status_pembayaran'] === 'lunas') {
        $lunas[] = $o;
    } else {
        $pending[] = $o;
    }
}

function label_tipe(array $p): string {
    return $p['tipe'] === 'takeaway' ? 'Takeaway' : 'Meja ' . (int) $p['no_meja'];
}

function label_status_pesanan(string $s): string {
    return [
        'baru' => 'Pesanan Masuk',
        'dimasak' => 'Sedang Dimasak',
        'siap' => 'Siap',
        'selesai' => 'Selesai',
    ][$s] ?? $s;
}

function render_kartu(array $o, array $detail, bool $tampil_lunas_btn, string $kembali): void {
    $pil = [
        'baru' => 'pil-baru',
        'dimasak' => 'pil-dimasak',
        'siap' => 'pil-siap',
        'selesai' => 'pil-selesai',
    ][$o['status_pesanan']] ?? 'pil-baru';
    ?>
    <div class="kartu kartu-pesanan">
        <div class="kepala-pesanan">
            <div>
                <strong class="kode-ps"><?= htmlspecialchars($o['kode']) ?></strong>
                <span class="badge pil-pending">Belum Bayar</span>
                <span class="badge <?= $pil ?>"><?= label_status_pesanan($o['status_pesanan']) ?></span>
            </div>
            <strong class="harga-ps"><?= rupiah($o['tot_harga']) ?></strong>
        </div>
        <div class="meta-pesanan">
            <span><?= htmlspecialchars($o['nama_pelanggan']) ?></span>
            <span><?= label_tipe($o) ?></span>
            <span><?= htmlspecialchars($o['kuah_nama'] ?? '-') ?> &middot; Level <?= (int) $o['level'] ?></span>
            <span><?= date('d/m H:i', strtotime($o['dibuat_pada'])) ?></span>
        </div>
        <?php if (!empty($o['catatan'])): ?>
            <div class="catatan">Catatan: <?= htmlspecialchars($o['catatan']) ?></div>
        <?php endif; ?>
        <div class="daftar-item">
            <?php foreach ($detail as $d): ?>
                <span><?= htmlspecialchars($d['nama_barang']) ?> <em>&times; <?= (int) $d['qty'] ?></em></span>
            <?php endforeach; ?>
        </div>
        <?php if ($tampil_lunas_btn): ?>
            <form method="post" action="api/status.php">
                <input type="hidden" name="aksi" value="lunas">
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <input type="hidden" name="kembali" value="<?= $kembali ?>">
                <button type="submit" class="tombol tombol-tipis">Konfirmasi Lunas</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir — Seblak Prasmanan</title>
    <link rel="stylesheet" href="assets/style.css">
    <meta http-equiv="refresh" content="8">
</head>
<body>
    <header class="kepala">
        <div>
            <h1>Kasir &amp; Pembayaran</h1>
            <p>Auto-refresh tiap 8 detik</p>
        </div>
        <nav class="tautan-isi">
            <a href="dapur.php">Dapur</a>
            <a href="qr.php?meja=1">QR</a>
        </nav>
    </header>

    <main class="halaman halaman-lebar">
        <section class="seksi">
            <h2 class="sub-judul">Menunggu Pembayaran (<?= count($pending) ?>)</h2>
            <?php if (count($pending) === 0): ?>
                <div class="kartu"><p class="kosong">Tidak ada pesanan yang menunggu pembayaran.</p></div>
            <?php else: ?>
                <?php foreach ($pending as $o):
                    $detail_stmt->execute([':idp' => (int) $o['id']]);
                    $d = $detail_stmt->fetchAll();
                    render_kartu($o, $d, true, 'kasir.php');
                endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="seksi">
            <h2 class="sub-judul">Sudah Lunas (<?= count($lunas) ?>)</h2>
            <?php if (count($lunas) === 0): ?>
                <div class="kartu"><p class="kosong">Belum ada pembayaran tercatat.</p></div>
            <?php else: ?>
                <?php foreach ($lunas as $o):
                    $detail_stmt->execute([':idp' => (int) $o['id']]);
                    $d = $detail_stmt->fetchAll();
                    render_kartu($o, $d, false, 'kasir.php');
                endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>