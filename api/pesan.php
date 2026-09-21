<?php
session_start();

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function kirim_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kirim_json(405, ['ok' => false, 'message' => 'Metode tidak diizinkan.']);
}

if (!isset($_SESSION['sbl_nama']) || !isset($_SESSION['sbl_tipe'])) {
    kirim_json(401, ['ok' => false, 'message' => 'Sesi pelanggan tidak ditemukan.']);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    kirim_json(400, ['ok' => false, 'message' => 'Data pesanan tidak valid.']);
}

$nama = trim((string) $_SESSION['sbl_nama']);
$tipe = $_SESSION['sbl_tipe'] === 'takeaway' ? 'takeaway' : 'dine-in';
$no_meja = $tipe === 'dine-in' ? (int) ($_SESSION['sbl_meja'] ?? 0) : null;

$items_raw = $body['items'] ?? [];
$kuah_id = (int) ($body['kuah_id'] ?? 0);
$level = (int) ($body['level'] ?? 0);
$catatan = trim((string) ($body['catatan'] ?? ''));

if (!is_array($items_raw) || count($items_raw) === 0) {
    kirim_json(400, ['ok' => false, 'message' => 'Minimal pilih satu bahan.']);
}
if ($tipe === 'dine-in' && $no_meja <= 0) {
    kirim_json(400, ['ok' => false, 'message' => 'Nomor meja tidak valid.']);
}
if ($level < 0 || $level > 5) {
    kirim_json(400, ['ok' => false, 'message' => 'Level pedas harus 0 sampai 5.']);
}

$kuah = ambil_kuah($pdo, $kuah_id);
if ($kuah === null) {
    kirim_json(400, ['ok' => false, 'message' => 'Jenis kuah tidak tersedia.']);
}
if ($catatan !== '' && mb_strlen($catatan) > 255) {
    $catatan = mb_substr($catatan, 0, 255);
}

$detail = [];
foreach ($items_raw as $row) {
    $id = (int) ($row['id'] ?? 0);
    $qty = (int) ($row['qty'] ?? 0);
    if ($id <= 0 || $qty <= 0) continue;
    $menu = ambil_menu_item($pdo, $id);
    if ($menu === null) continue;
    $detail[] = ['nama_barang' => $menu['nama'], 'harga_satuan' => (float) $menu['harga_satuan'], 'qty' => $qty];
}
if (count($detail) === 0) {
    kirim_json(400, ['ok' => false, 'message' => 'Item pesanan tidak valid.']);
}

$surcharge = ambil_surcharge_level($pdo, $level);
$h = hitung_harga($detail, (float) $kuah['harga'], (float) $surcharge);

try {
    $pdo->beginTransaction();

    $kode = generate_kode_pesanan($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO pesanan (kode, nama_pelanggan, no_meja, tipe, id_kuah, level,
             harga_subtotal, harga_kuah, surcharge_level, tot_harga, status_pembayaran, status_pesanan, catatan)
         VALUES (:kode, :nama, :meja, :tipe, :kuah, :level,
             :subtotal, :kuahh, :sur, :total, 'pending', 'baru', :catatan)"
    );
    $stmt->execute([
        ':kode' => $kode,
        ':nama' => $nama,
        ':meja' => $no_meja,
        ':tipe' => $tipe,
        ':kuah' => (int) $kuah['id'],
        ':level' => $level,
        ':subtotal' => $h['subtotal'],
        ':kuahh' => $h['harga_kuah'],
        ':sur' => $h['surcharge_level'],
        ':total' => $h['total'],
        ':catatan' => $catatan === '' ? null : $catatan,
    ]);
    $id_pesanan = (int) $pdo->lastInsertId();

    $stmt_detail = $pdo->prepare(
        "INSERT INTO pesanan_detail (id_pesanan, id_menu_item, nama_barang, harga_satuan, qty, subtotal)
         VALUES (:idp, :idm, :nama, :harga, :qty, :sub)"
    );
    foreach ($detail as $d) {
        $sub = round((float) $d['harga_satuan'] * $d['qty'], 2);
        $stmt_detail->execute([
            ':idp' => $id_pesanan,
            ':idm' => null,
            ':nama' => $d['nama_barang'],
            ':harga' => $d['harga_satuan'],
            ':qty' => $d['qty'],
            ':sub' => $sub,
        ]);
    }

    $pdo->commit();

    kirim_json(201, [
        'ok' => true,
        'kode' => $kode,
        'total' => $h['total'],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    kirim_json(500, ['ok' => false, 'message' => 'Gagal menyimpan pesanan.']);
}