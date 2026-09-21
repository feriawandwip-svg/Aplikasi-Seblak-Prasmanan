<?php
session_start();

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['sbl_nama']) || !isset($_SESSION['sbl_tipe'])) {
    header('Location: index.php');
    exit;
}

$nama = trim((string) $_SESSION['sbl_nama']);
$tipe = $_SESSION['sbl_tipe'] === 'takeaway' ? 'takeaway' : 'dine-in';
$meja = $tipe === 'dine-in' ? (int) ($_SESSION['sbl_meja'] ?? 0) : null;

$kategori = $pdo->query("SELECT id, nama, urutan FROM kategori ORDER BY urutan, id")->fetchAll();
$kuah_list = $pdo->query("SELECT id, nama, harga FROM kuah WHERE aktif = 1 ORDER BY id")->fetchAll();
$level_rows = $pdo->query("SELECT level, surcharge FROM level_pedas ORDER BY level")->fetchAll();

$menu_flat = [];
$menu_per_kategori = [];
foreach ($kategori as $k) {
    $id_k = (int) $k['id'];
    $stmt = $pdo->prepare(
        "SELECT id, id_kategori, nama, harga_satuan
         FROM menu_item
         WHERE id_kategori = :kid AND aktif = 1
         ORDER BY id"
    );
    $stmt->execute([':kid' => $id_k]);
    $menu_per_kategori[$id_k] = [];
    foreach ($stmt->fetchAll() as $m) {
        $id_m = (int) $m['id'];
        $menu_per_kategori[$id_k][] = [
            'id' => $id_m,
            'nama' => $m['nama'],
            'harga' => (float) $m['harga_satuan'],
        ];
        $menu_flat[$id_m] = [
            'id' => $id_m,
            'nama' => $m['nama'],
            'harga' => (float) $m['harga_satuan'],
        ];
    }
}

$level_map = [];
foreach ($level_rows as $lr) {
    $level_map[(int) $lr['level']] = (float) $lr['surcharge'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Bahan Seblak</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="kepala">
        <div>
            <h1>Pilih Bahan Seblak</h1>
            <p>
                <?= htmlspecialchars($nama) ?>
                <?= $tipe === 'takeaway' ? '&middot; Takeaway' : '&middot; Meja ' . (int) $meja ?>
            </p>
        </div>
        <a class="tautan" href="index.php">Ubah</a>
    </header>

    <main class="halaman">
        <section class="kartu panel-pilihan">
            <div class="grup">
                <label>Jenis Kuah</label>
                <div class="pilihan-kuah" id="daftar-kuah">
                    <?php foreach ($kuah_list as $k): ?>
                        <button type="button" class="chip-kuah" data-kuah-id="<?= (int) $k['id'] ?>">
                            <span class="nama"><?= htmlspecialchars($k['nama']) ?></span>
                            <span class="harga"><?= $k['harga'] > 0 ? rupiah($k['harga']) : 'Gratis' ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grup">
                <label for="slider-level">Tingkat Pedas: <strong id="label-level">Level 0</strong></label>
                <input type="range" id="slider-level" min="0" max="5" step="1" value="0">
                <div class="skala-level">
                    <span>Level 0</span><span>Level 5</span>
                </div>
            </div>
        </section>

        <?php foreach ($kategori as $k): ?>
            <?php $id_k = (int) $k['id']; ?>
            <?php $items = $menu_per_kategori[$id_k] ?? []; ?>
            <?php if (count($items) === 0) continue; ?>
            <section class="kategori" id="kategori-<?= $id_k ?>">
                <h2><?= htmlspecialchars($k['nama']) ?></h2>
                <div class="daftar-menu">
                    <?php foreach ($items as $m): ?>
                        <div class="menu-item">
                            <div class="info">
                                <span class="nama"><?= htmlspecialchars($m['nama']) ?></span>
                                <span class="harga"><?= rupiah($m['harga']) ?></span>
                            </div>
                            <div class="stepper" data-mid="<?= $m['id'] ?>">
                                <button type="button" class="minus" data-mid="<?= $m['id'] ?>" aria-label="Kurangi">&#8722;</button>
                                <span class="qty" data-mid="<?= $m['id'] ?>">0</span>
                                <button type="button" class="plus" data-mid="<?= $m['id'] ?>" aria-label="Tambah">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <div class="bar-bawah">
        <button type="button" class="buka-keranjang" id="tombol-buka">
            <span>Keranjang <strong id="jml-item">0</strong> item</span>
            <span class="total" id="total-baris">Rp0</span>
        </button>
        <button type="button" class="tombol tombol-checkout" id="tombol-checkout">Lanjut ke Checkout</button>
    </div>

    <div class="lapisan" id="lapisan" hidden></div>
    <div class="panel-keranjang" id="panel-keranjang" hidden>
        <div class="kepala-panel">
            <strong>Rincian Keranjang</strong>
            <button type="button" id="tombol-tutup">&times;</button>
        </div>
        <div id="isi-keranjang"></div>
        <div class="kaki-panel">
            <span>Total</span>
            <strong id="total-panel">Rp0</strong>
        </div>
        <button type="button" class="tombol tombol-checkout" id="tombol-checkout-panel">Lanjut ke Checkout</button>
    </div>

    <script>
        const DATA_MENU = <?= json_encode($menu_flat, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) ?>;
        const DATA_KUAH = <?= json_encode($kuah_list, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) ?>;
        const DATA_LEVEL = <?= json_encode($level_map, JSON_NUMERIC_CHECK) ?>;
    </script>
    <script src="assets/script.js"></script>
</body>
</html>