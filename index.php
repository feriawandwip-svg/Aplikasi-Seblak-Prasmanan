<?php
session_start();

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$daftar_meja = [];
foreach ($pdo->query("SELECT nomor_meja FROM meja WHERE aktif = 1 ORDER BY nomor_meja") as $row) {
    $daftar_meja[] = (int) $row['nomor_meja'];
}

$error = '';
$nama = '';
$tipe = 'dine-in';
$meja = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $tipe = ($_POST['tipe'] ?? 'dine-in') === 'takeaway' ? 'takeaway' : 'dine-in';
    $meja = trim((string) ($_POST['meja'] ?? ''));

    if ($nama === '') {
        $error = 'Nama wajib diisi.';
    } elseif ($tipe === 'dine-in' && $meja === '') {
        $error = 'Nomor meja wajib dipilih.';
    } elseif ($tipe === 'dine-in' && !in_array((int) $meja, $daftar_meja, true)) {
        $error = 'Nomor meja tidak terdaftar.';
    }

    if ($error === '') {
        $_SESSION['sbl_nama'] = $nama;
        $_SESSION['sbl_tipe'] = $tipe;
        $_SESSION['sbl_meja'] = $tipe === 'dine-in' ? (int) $meja : null;
        header('Location: menu.php');
        exit;
    }
} else {
    $qr_meja = isset($_GET['meja']) && ctype_digit((string) $_GET['meja']) ? (int) $_GET['meja'] : 0;
    if (in_array($qr_meja, $daftar_meja, true)) {
        $meja = (string) $qr_meja;
        $tipe = 'dine-in';
    } elseif (isset($_SESSION['sbl_tipe'])) {
        $nama = (string) $_SESSION['sbl_nama'];
        $tipe = $_SESSION['sbl_tipe'] === 'takeaway' ? 'takeaway' : 'dine-in';
        $meja = $tipe === 'dine-in' && isset($_SESSION['sbl_meja']) ? (string) $_SESSION['sbl_meja'] : '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seblak Prasmanan — Mulai Pesan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="halaman">
        <div class="judul">
            <div class="logo">S</div>
            <h1>Seblak Prasmanan</h1>
            <p>Pilih bahan sesuai selera, masak di kedai</p>
        </div>

        <div class="kartu">
            <?php if ($error !== ''): ?>
                <div class="pesan-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php" id="form-mulai">
                <div class="grup">
                    <label for="nama">Nama Anda</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>"
                           placeholder="Contoh: Budi" required maxlength="80" autocomplete="name">
                </div>

                <div class="grup">
                    <label>Pilih Tipe Pesanan</label>
                    <div class="pilihan-tipe">
                        <label class="pilih" id="kartu-dinein">
                            <input type="radio" name="tipe" value="dine-in"
                                   <?= $tipe === 'dine-in' ? 'checked' : '' ?>>
                            <span>&#127869;</span>
                            Makan di Tempat
                            <span class="kecil">Duduk di meja kedai</span>
                        </label>
                        <label id="kartu-takeaway">
                            <input type="radio" name="tipe" value="takeaway"
                                   <?= $tipe === 'takeaway' ? 'checked' : '' ?>>
                            <span>&#127857;</span>
                            Takeaway
                            <span class="kecil">Di bungkus untuk dibawa</span>
                        </label>
                    </div>
                </div>

                <div class="grup" id="grup-meja">
                    <label for="meja">Nomor Meja</label>
                    <select id="meja" name="meja">
                        <option value="">Pilih nomor meja…</option>
                        <?php foreach ($daftar_meja as $n): ?>
                            <option value="<?= $n ?>" <?= (string) $n === $meja ? 'selected' : '' ?>>
                                Meja <?= $n ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="tombol">Lanjut ke Menu</button>
            </form>
        </div>
    </main>

    <script>
        const tipeDineIn = document.querySelector('input[value="dine-in"]');
        const tipeTakeaway = document.querySelector('input[value="takeaway"]');
        const kartuDineIn = document.getElementById('kartu-dinein');
        const kartuTakeaway = document.getElementById('kartu-takeaway');
        const grupMeja = document.getElementById('grup-meja');
        const selectMeja = document.getElementById('meja');

        function perbaruiTipe() {
            const takeaway = tipeTakeaway.checked;
            kartuDineIn.classList.toggle('pilih', !takeaway);
            kartuTakeaway.classList.toggle('pilih', takeaway);
            grupMeja.classList.toggle('tersembunyi', takeaway);
            if (takeaway) {
                selectMeja.value = '';
                selectMeja.removeAttribute('required');
            } else {
                selectMeja.setAttribute('required', 'required');
            }
        }

        tipeDineIn.addEventListener('change', perbaruiTipe);
        tipeTakeaway.addEventListener('change', perbaruiTipe);
        perbaruiTipe();
    </script>
</body>
</html>