(function () {
    'use strict';

    const KUNCI_CART = 'seblak_cart';
    let cart = cartBaru();

    const el = {
        stepper: document.querySelectorAll('.stepper'),
        plus: document.querySelectorAll('.stepper .plus'),
        minus: document.querySelectorAll('.stepper .minus'),
        qty: document.querySelectorAll('.stepper .qty'),
        chipsKuah: document.querySelectorAll('.chip-kuah'),
        sliderLevel: document.getElementById('slider-level'),
        labelLevel: document.getElementById('label-level'),
        jmlItem: document.getElementById('jml-item'),
        totalBaris: document.getElementById('total-baris'),
        buka: document.getElementById('tombol-buka'),
        tutup: document.getElementById('tombol-tutup'),
        lapisan: document.getElementById('lapisan'),
        panel: document.getElementById('panel-keranjang'),
        isi: document.getElementById('isi-keranjang'),
        totalPanel: document.getElementById('total-panel'),
        checkout: document.querySelectorAll('.tombol-checkout'),
    };

    if (!el.sliderLevel || !el.totalBaris) return;

    function cartBaru() {
        return { items: {}, kuahId: null, kuahNama: '', kuahHarga: 0, level: 0 };
    }

    function muat() {
        cart = cartBaru();
        try {
            const raw = localStorage.getItem(KUNCI_CART);
            if (!raw) return;
            const data = JSON.parse(raw);
            if (data && typeof data === 'object') {
                if (data.items) cart.items = data.items;
                if (data.kuahId) {
                    cart.kuahId = data.kuahId;
                    cart.kuahNama = data.kuahNama || '';
                    cart.kuahHarga = Number(data.kuahHarga) || 0;
                }
                cart.level = typeof data.level === 'number' ? Math.min(5, Math.max(0, data.level)) : 0;
                
                for (const id in cart.items) {
                    const menu = DATA_MENU[id];
                    if (menu) {
                        cart.items[id].nama = menu.nama;
                        cart.items[id].harga = menu.harga;
                    } else {
                        delete cart.items[id];
                    }
                }
            }
        } catch (e) {}
    }

    function simpan() {
        try {
            localStorage.setItem(KUNCI_CART, JSON.stringify(cart));
        } catch (e) {}
    }

    function jumlahItem() {
        return Object.keys(cart.items).reduce(function (t, id) {
            return t + (Number(cart.items[id].qty) || 0);
        }, 0);
    }

    function subtotalItem() {
        return Object.keys(cart.items).reduce(function (t, id) {
            return t + Number(cart.items[id].harga) * (Number(cart.items[id].qty) || 0);
        }, 0);
    }

    function surchargeLevel() {
        return Number(DATA_LEVEL[cart.level]) || 0;
    }

    function totalSemua() {
        const t = subtotalItem() + Number(cart.kuahHarga) + surchargeLevel();
        return t < 0 ? 0 : t;
    }

    function formatRp(n) {
        return 'Rp' + (Math.round(Number(n) || 0)).toLocaleString('id-ID');
    }

    function ubahQty(id, delta) {
        const menu = DATA_MENU[id];
        if (!menu) return;
        if (!cart.items[id] && delta <= 0) return;
        if (!cart.items[id]) {
            cart.items[id] = { id: Number(id), nama: menu.nama, harga: menu.harga, qty: 0 };
        }
        cart.items[id].qty = Math.max(0, (Number(cart.items[id].qty) || 0) + delta);
        if (cart.items[id].qty === 0) delete cart.items[id];
        simpan();
        renderSemua();
    }

    function pilihKuah(id) {
        const k = DATA_KUAH.find(function (x) {
            return Number(x.id) === Number(id);
        });
        if (!k) return;
        if (Number(cart.kuahId) === Number(k.id)) {
            cart.kuahId = null;
            cart.kuahNama = '';
            cart.kuahHarga = 0;
        } else {
            cart.kuahId = Number(k.id);
            cart.kuahNama = k.nama;
            cart.kuahHarga = Number(k.harga) || 0;
        }
        simpan();
        renderSemua();
    }

    function ubahLevel(v) {
        cart.level = Math.min(5, Math.max(0, Number(v) || 0));
        simpan();
        renderSemua();
    }

    function renderStepper() {
        el.qty.forEach(function (sp) {
            const id = sp.getAttribute('data-mid');
            const item = cart.items[id];
            sp.textContent = item ? item.qty : '0';
        });
    }

    function renderKuah() {
        el.chipsKuah.forEach(function (chip) {
            const id = chip.getAttribute('data-kuah-id');
            chip.classList.toggle('pilih', String(cart.kuahId) === String(id));
        });
    }

    function renderLevel() {
        el.sliderLevel.value = String(cart.level);
        let label = 'Level ' + cart.level;
        const s = surchargeLevel();
        if (s > 0) label += ' (+' + formatRp(s) + ')';
        el.labelLevel.textContent = label;
    }

    function renderBawah() {
        el.jmlItem.textContent = String(jumlahItem());
        el.totalBaris.textContent = formatRp(totalSemua());
    }

    function renderIsiKeranjang() {
        let html = '';
        const ids = Object.keys(cart.items);
        if (ids.length === 0) {
            html = '<p class="kosong">Belum ada bahan dipilih.</p>';
        } else {
            html += '<div class="daftar-cart">';
            ids.forEach(function (id) {
                const it = cart.items[id];
                html +=
                    '<div class="baris-cart">' +
                    '<div class="ket">' +
                    '<span class="nama">' + it.nama + '</span>' +
                    '<span class="sub">' + formatRp(it.harga) + ' &times; ' + it.qty + ' = <strong>' + formatRp(it.harga * it.qty) + '</strong></span>' +
                    '</div>' +
                    '<div class="stepper kecil" data-mid="' + id + '">' +
                    '<button type="button" class="minus" data-mid="' + id + '" aria-label="Kurangi">&#8722;</button>' +
                    '<span class="qty" data-mid="' + id + '">' + it.qty + '</span>' +
                    '<button type="button" class="plus" data-mid="' + id + '" aria-label="Tambah">+</button>' +
                    '</div>' +
                    '</div>';
            });
            html += '</div>';
        }
        html += '<div class="baris-opsi">';
        html += '<span>Kuah</span><strong>' + (cart.kuahNama ? cart.kuahNama + ' · ' + formatRp(cart.kuahHarga) : 'Belum pilih') + '</strong>';
        html += '</div>';
        html += '<div class="baris-opsi">';
        html += '<span>Level</span><strong>Level ' + cart.level + (surchargeLevel() > 0 ? ' +' + formatRp(surchargeLevel()) : '') + '</strong>';
        html += '</div>';
        el.isi.innerHTML = html;
        el.totalPanel.textContent = formatRp(totalSemua());
        ikatStepper(el.isi);
    }

    function bukaPanel(open) {
        if (el.lapisan && el.panel) {
            el.lapisan.hidden = !open;
            el.panel.hidden = !open;
        }
    }

    function ikatStepper(root) {
        if (!root) return;
        const target = root === document ? el.plus : root.querySelectorAll('.plus');
        const targetMin = root === document ? el.minus : root.querySelectorAll('.minus');
        target.forEach(function (b) {
            b.addEventListener('click', function () {
                ubahQty(b.getAttribute('data-mid'), 1);
            });
        });
        targetMin.forEach(function (b) {
            b.addEventListener('click', function () {
                ubahQty(b.getAttribute('data-mid'), -1);
            });
        });
    }

    function renderSemua() {
        renderStepper();
        renderKuah();
        renderLevel();
        renderBawah();
        renderIsiKeranjang();
    }

    function keCheckout() {
        if (jumlahItem() === 0) {
            alert('Pilih minimal satu bahan.');
            return;
        }
        if (!cart.kuahId) {
            alert('Pilih jenis kuah terlebih dahulu.');
            return;
        }
        bukaPanel(false);
        window.location.href = 'checkout.php';
    }

    function mulai() {
        muat();
        ikatStepper(document);
        el.chipsKuah.forEach(function (chip) {
            chip.addEventListener('click', function () {
                pilihKuah(chip.getAttribute('data-kuah-id'));
            });
        });
        el.sliderLevel.addEventListener('input', function () {
            ubahLevel(el.sliderLevel.value);
        });
        el.buka.addEventListener('click', function () {
            bukaPanel(true);
        });
        el.tutup.addEventListener('click', function () {
            bukaPanel(false);
        });
        el.lapisan.addEventListener('click', function () {
            bukaPanel(false);
        });
        el.checkout.forEach(function (b) {
            b.addEventListener('click', keCheckout);
        });
        renderSemua();
    }

    mulai();
})();