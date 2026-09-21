<?php
function rupiah($angka): string
{
    $angka = (float) $angka;
    if ($angka != floor($angka)) {
        return 'Rp' . number_format($angka, 2, ',', '.');
    }
    return 'Rp' . number_format($angka, 0, ',', '.');
}

function hitung_harga(array $detail, float $harga_kuah = 0, float $surcharge_level = 0): array
{
    $subtotal = 0.0;
    foreach ($detail as $row) {
        $harga = (float) $row['harga_satuan'];
        $qty = max(1, (int) ($row['qty'] ?? 1));
        $subtotal += round($harga * $qty, 2);
    }

    return [
        'subtotal' => round($subtotal, 2),
        'harga_kuah' => round($harga_kuah, 2),
        'surcharge_level' => round($surcharge_level, 2),
        'total' => round($subtotal + $harga_kuah + $surcharge_level, 2),
    ];
}

function generate_kode_pesanan(PDO $pdo): string
{
    $prefix = 'SBL-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE kode LIKE :prefix");
    $stmt->execute([':prefix' => $prefix . '%']);
    $nomor = (int) $stmt->fetchColumn() + 1;
    return $prefix . str_pad($nomor, 4, '0', STR_PAD_LEFT);
}

function ambil_menu_item(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM menu_item WHERE id = :id AND aktif = 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function ambil_kuah(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM kuah WHERE id = :id AND aktif = 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function ambil_surcharge_level(PDO $pdo, int $level): float
{
    $stmt = $pdo->prepare("SELECT surcharge FROM level_pedas WHERE level = :level");
    $stmt->execute([':level' => $level]);
    $row = $stmt->fetch();
    return $row === false ? 0.0 : (float) $row['surcharge'];
}