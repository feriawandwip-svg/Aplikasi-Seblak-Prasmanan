# Seblak Prasmanan — Aplikasi Pemesanan

Sistem pemesanan seblak prasmanan berbasis web: pelanggan memilih bahan secara *custom* dari HP (scan QR di meja), kasir mengonfirmasi pembayaran tunai, dan dapur melihat pesanan lewat *Kitchen Display*.

- **Teknologi:** HTML, CSS, JavaScript (vanilla), PHP 8, MySQL/MariaDB (XAMPP)
- **Tanpa framework**, tanpa autentikasi pengguna — cocok untuk demo kampus / kedai skala kecil
- **Multi-perangkat:** satu server di jaringan LAN; pelanggan buka lewat QR, kasir & dapur buka di browser komputer mereka

---

## Alur Penggunaan

1. Pelanggan scan QR di meja (atau buka `index.php?meja=N`) → isi **Nama** + pilih **Nomor Meja** atau **Takeaway**.
2. Pilih bahan per kategori (Karbo, Protein & Topping, Sayur & Pelengkap), pilih **Kuah** dan **Level** pedas 0–5.
3. Klik **"Lanjut ke Checkout"** → lihat ringkasan & total → **"Pesan Sekarang"**.
4. Pelanggan membayar tunai di kasir; kasir mengklik **"Konfirmasi Lunas"**.
5. Pesanan muncul di **Kitchen Display**; koki ubah status: *Mulai Masak → Selesai Masak/Siap → Selesai/Disajikan*.
6. Pelayan menyajikan sesuai nomor meja; status *Selesai* menghilang dari layar dapur.

Status pembayaran: `pending` → `lunas`. Status pesanan: `baru` → `dimasak` → `siap` → `selesai` (transisi maju saja, lompatan ditolak).

---

## Struktur File

```
/
├── index.php        Halaman awal pelanggan (nama + meja/takeaway)
├── menu.php         Katalog menu, kuah, level, keranjang (localStorage)
├── checkout.php     Ringkasan pesanan + kirim pesanan
├── sukses.php       Konfirmasi kode pesanan & total (bayar di kasir)
├── kasir.php        Dashboard kasir (konfirmasi pembayaran)
├── dapur.php        Kitchen display (status memasak)
├── qr.php           Cetak QR per meja (qr.php?meja=N)
├── api/
│   ├── pesan.php    Simpan pesanan + detail (hitung harga dari DB, transaksi)
│   └── status.php   Update pembayaran & status pesanan
├── includes/
│   ├── db.php       Koneksi PDO (atur kredensial di sini)
│   └── functions.php Helper: rupiah, hitung harga, kode pesanan
├── assets/
│   ├── style.css    Gaya aplikasi
│   ├── script.js    Logika keranjang
│   └── vendor/qrcode.js  Library QR (self-host, offline)
└── sql/
    ├── seblak_db.sql  Skema database
    └── seed.sql       Data contoh (menu, kuah, level, meja)
```

---

## Cara Setup (XAMPP)

1. **Salin folder** proyek ini ke `C:\xampp\htdocs\seblak-app` (atau nama lain).

2. **Nyalakan MySQL** di XAMPP Control Panel (Apache tidak wajib — cukup jalankan:
   ```
   php -S 0.0.0.0:8080 -t C:\xampp\htdocs\seblak-app
   ```
   dari terminal. `0.0.0.0` agar bisa diakses perangkat lain di LAN.)

3. **Buat database** — buka phpMyAdmin (`http://localhost/phpmyadmin`) lalu import:
   - `sql/seblak_db.sql` (membuat DB `seblak_db` + tabel)
   - `sql/seed.sql` (mengisi data contoh)

   Atau via terminal:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < sql\seblak_db.sql
   C:\xampp\mysql\bin\mysql.exe -u root < sql\seed.sql
   ```

4. **Sesuaikan kredensial DB** bila bukan default (`root` tanpa password) di `includes/db.php`.

5. **Cari IP server** di jaringan (mis. `ipconfig` → `IPv4: 192.168.x.x`).

6. **Cetak QR meja** — buka `http://localhost:8080/qr.php?meja=1` (ganti nomor meja), tempel di meja masing-masing. URL QR otomatis memakai host/IP yang membuka halaman tersebut.

---

## Uji Cepat

- **Pelanggan:** buka `http://<ip>:8080/index.php?meja=3` → pilih bahan → pesan → catat kode.
- **Kasir:** buka `http://<ip>:8080/kasir.php` → order muncul → **Konfirmasi Lunas**.
- **Dapur:** buka `http://<ip>:8080/dapur.php` → geser status sampai **Selesai**.

Halaman kasir & dapur auto-refresh (5–8 detik). Tidak ada login; halaman staf cukup diakses lewat URL-nya (sesuai cakupan MVP tanpa autentikasi).

---

## Catatan Desain

- Harga dihitung ulang **di server dari database** saat pesan (`api/pesan.php`), tidak dipercaya dari client — aman dari manipulasi harga.
- `pesanan_detail` menyimpan **snapshot** harga (`nama_barang`, `harga_satuan`) agar perubahan harga menu tidak mengubah pesanan lama.
- Keranjang disimpan di `localStorage` — tetap ada saat refresh/pindah halaman.
- Level 0–5 mendukung *surcharge* opsional di tabel `level_pedas` (default 0).
- Tambah menu/kuah/meja cukup dengan menambah baris di database (data aktif otomatis muncul).

## Reset Data

Untuk kembali ke kondisi awal (hapus semua pesanan, isi ulang data contoh):

```
C:\xampp\mysql\bin\mysql.exe -u root < sql\seblak_db.sql
C:\xampp\mysql\bin\mysql.exe -u root < sql\seed.sql
```