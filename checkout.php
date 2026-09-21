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

$level_map = [];
foreach ($pdo->query("SELECT level, surcharge FROM level_pedas ORDER BY level") as $lr) {
    $level_map[(int) $lr['level']] = (float) $lr['surcharge'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seblak</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="kepala">
        <div>
            <h1>Checkout Pesanan</h1>
            <p>
                <?= htmlspecialchars($nama) ?>
                <?= $tipe === 'takeaway' ? '&middot; Takeaway' : '&middot; Meja ' . (int) $meja ?>
            </p>
        </div>
        <a class="tautan" href="menu.php">&larr; Kembali</a>
    </header>

    <main class="halaman">
        <div class="kartu">
            <h2 class="sub-judul">Ringkasan Pesanan</h2>
            <div id="ringkasan">
                <p class="kosong">Memuat keranjang…</p>
            </div>
        </div>

        <div class="kartu">
            <div class="grup">
                <label for="catatan">Catatan (opsional)</label>
                <input type="text" id="catatan" name="catatan" maxlength="255"
                       placeholder="Contoh: jangan pakai bawang">
            </div>
            <div class="pesan-error tersembunyi" id="pesan-error"></div>
            <button type="button" class="tombol" id="tombol-pesan">Pesan Sekarang</button>
        </div>
    </main>

    <script>
        const KUNCI_CART = 'seblak_cart';
        const DATA_LEVEL = <?= json_encode($level_map, JSON_NUMERIC_CHECK) ?>;

        function formatRp(n) {
            return 'Rp' + (Math.round(Number(n) || 0)).toLocaleString('id-ID');
        }

        const muatKart = (function () {
            try {
                const raw = localStorage.getItem(KUNCI_CART);
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        })();

        function htmlBaris(label, nilai, kuat) {
            return '<div class="baris-opsi"><span>' + label + '</span><strong>' + nilai + '</strong></div>';
        }

        const ringkasan = document.getElementById('ringkasan');

        if (!muatKart) {
            ringkasan.innerHTML = '<p class="kosong">Keranjang kosong atau sudah dikirim.</p>';
        } else if (!muatKart.kuahId) {
            ringkasan.innerHTML = '<p class="kosong">Kuah belum dipilih. Pilih kuah di menu.</p>';
        } else {
            let html = '<div class="daftar-cart">';
            let subtotal = 0;
            for (const id in muatKart.items) {
                const it = muatKart.items[id];
                const sub = Number(it.harga) * Number(it.qty);
                subtotal += sub;
                html +=
                    '<div class="baris-cart">' +
                    '<div class="ket"><span class="nama">' + it.nama + '</span>' +
                    '<span class="sub">' + formatRp(it.harga) + ' &times; ' + it.qty + '</span></div>' +
                    '<strong>' + formatRp(sub) + '</strong></div>';
            }
            html += '</div>';
            html += htmlBaris('Subtotal Bahan', formatRp(subtotal));
            html += htmlBaris('Kuah ' + muatKart.kuahNama, muatKart.kuahHarga > 0 ? formatRp(muatKart.kuahHarga) : 'Gratis');
            const surcharge = Number(DATA_LEVEL[muatKart.level]) || 0;
            if (surcharge > 0) {
                html += htmlBaris('Surcharge Level ' + muatKart.level, formatRp(surcharge));
            }
            const total = subtotal + Number(muatKart.kuahHarga) + surcharge;
            html += '<div class="kaki-panel"><span>Total</span><strong>' + formatRp(total) + '</strong></div>';
            ringkasan.innerHTML = html;
        }

        const tombolPesan = document.getElementById('tombol-pesan');
        const pesanError = document.getElementById('pesan-error');

        tombolPesan.addEventListener('click', async function () {
            if (!muatKart || !muatKart.kuahId || !Object.keys(muatKart.items).length) {
                pesanError.textContent = 'Keranjang belum lengkap. Kembali ke menu.';
                pesanError.classList.remove('tersembunyi');
                return;
            }
            const catatan = document.getElementById('catatan').value.trim();
            const items = Object.keys(muatKart.items).map(function (id) {
                return { id: Number(id), qty: Number(muatKart.items[id].qty) };
            });

            tombolPesan.disabled = true;
            tombolPesan.textContent = 'Mengirim…';
            pesanError.classList.add('tersembunyi');

            try {
                const res = await fetch('api/pesan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: items,
                        kuah_id: muatKart.kuahId,
                        level: muatKart.level,
                        catatan: catatan,
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    localStorage.removeItem(KUNCI_CART);
                    window.location.href = 'sukses.php?kode=' + encodeURIComponent(data.kode);
                } else {
                    pesanError.textContent = data.message || 'Gagal mengirim pesanan.';
                    pesanError.classList.remove('tersembunyi');
                    tombolPesan.disabled = false;
                    tombolPesan.textContent = 'Pesan Sekarang';
                }
            } catch (e) {
                pesanError.textContent = 'Terjadi kesalahan koneksi. Coba lagi.';
                pesanError.classList.remove('tersembunyi');
                tombolPesan.disabled = false;
                tombolPesan.textContent = 'Pesan Sekarang';
            }
        });
    </script>
</body>
</html>