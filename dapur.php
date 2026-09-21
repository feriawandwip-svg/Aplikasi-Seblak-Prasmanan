<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$orders = $pdo->query(
    "SELECT p.*, k.nama AS kuah_nama
     FROM pesanan p
     LEFT JOIN kuah k ON k.id = p.id_kuah
     WHERE p.status_pesanan != 'selesai'
     ORDER BY FIELD(p.status_pesanan, 'baru', 'dimasak', 'siap'), p.id ASC
     LIMIT 60"
)->fetchAll();

$detail_stmt = $pdo->prepare("SELECT nama_barang, qty FROM pesanan_detail WHERE id_pesanan = :idp ORDER BY id");

$grup = ['baru' => [], 'dimasak' => [], 'siap' => []];
foreach ($orders as $o) {
    $grup[$o['status_pesanan']][] = $o;
}

function label_tipe(array $p): string {
    return $p['tipe'] === 'takeaway' ? 'Takeaway' : 'Meja ' . (int) $p['no_meja'];
}

function pill_status_pesanan(string $s): string {
    return [
        'baru' => 'pil-baru',
        'dimasak' => 'pil-dimasak',
        'siap' => 'pil-siap',
    ][$s] ?? 'pil-baru';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dapur — Seblak Prasmanan</title>
    <link rel="stylesheet" href="assets/style.css">
    <meta http-equiv="refresh" content="5">
</head>
<body>
    <header class="kepala">
        <div>
            <h1>Kitchen Display</h1>
            <p>Auto-refresh tiap 5 detik</p>
        </div>
        <nav class="tautan-isi">
            <a href="kasir.php">Kasir</a>
        </nav>
    </header>

    <main class="halaman halaman-lebar">
        <?php
        $konfig = [
            'baru' => ['Judul' => 'Pesanan Baru', 'Btn' => 'Mulai Masak', 'Status' => 'dimasak', 'Warna' => 'tema-baru'],
            'dimasak' => ['Judul' => 'Sedang Dimasak', 'Btn' => 'Selesai Masak / Siap', 'Status' => 'siap', 'Warna' => 'tema-dimasak'],
            'siap' => ['Judul' => 'Siap Disajikan', 'Btn' => 'Selesai / Disajikan', 'Status' => 'selesai', 'Warna' => 'tema-siap'],
        ];
        foreach ($konfig as $key => $cfg):
            $daftar = $grup[$key] ?? [];
        ?>
        <section class="seksi">
            <h2 class="sub-judul <?= $cfg['Warna'] ?>"><?= $cfg['Judul'] ?> (<?= count($daftar) ?>)</h2>
            <?php if (count($daftar) === 0): ?>
                <div class="kartu"><p class="kosong">Tidak ada pesanan.</p></div>
            <?php else: ?>
                <div class="grid-dapur">
                    <?php foreach ($daftar as $o):
                        $detail_stmt->execute([':idp' => (int) $o['id']]);
                        $detail = $detail_stmt->fetchAll();
                    ?>
                    <div class="kartu kartu-pesanan pasangan-<?= $key ?>">
                        <div class="kepala-pesanan">
                            <div>
                                <strong class="kode-ps"><?= htmlspecialchars($o['kode']) ?></strong>
                                <span class="badge <?= pill_status_pesanan($key) ?>">
                                    <?= htmlspecialchars(label_tipe($o)) ?>
                                </span>
                            </div>
                            <span class="harga-ps kecil-abu"><?= date('H:i', strtotime($o['dibuat_pada'])) ?></span>
                        </div>
                        <div class="meta-pesanan tebal">
                            <?= htmlspecialchars($o['nama_pelanggan']) ?>
                            &middot; <?= htmlspecialchars($o['kuah_nama'] ?? '-') ?> Level <?= (int) $o['level'] ?>
                        </div>
                        <div class="daftar-item besar">
                            <?php foreach ($detail as $d): ?>
                                <span><b>&times;<?= (int) $d['qty'] ?></b> <?= htmlspecialchars($d['nama_barang']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($o['catatan'])): ?>
                            <div class="catatan">Catatan: <?= htmlspecialchars($o['catatan']) ?></div>
                        <?php endif; ?>
                        <form method="post" action="api/status.php">
                            <input type="hidden" name="aksi" value="status_pesanan">
                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                            <input type="hidden" name="status" value="<?= $cfg['Status'] ?>">
                            <input type="hidden" name="kembali" value="dapur.php">
                            <button type="submit" class="tombol tombol-tipis"><?= $cfg['Btn'] ?></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </main>
</body>
</html>