<?php
session_start();

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../kasir.php');
    exit;
}

$kembali = ($_POST['kembali'] ?? 'kasir.php') === 'dapur.php' ? 'dapur.php' : 'kasir.php';
$target = '../' . $kembali;

$aksi = (string) ($_POST['aksi'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

if ($aksi === 'lunas' && $id > 0) {
    $stmt = $pdo->prepare("UPDATE pesanan SET status_pembayaran = 'lunas' WHERE id = :id");
    $stmt->execute([':id' => $id]);
} elseif ($aksi === 'status_pesanan' && $id > 0) {
    $status = (string) ($_POST['status'] ?? '');
    $stmt = $pdo->prepare("SELECT status_pesanan FROM pesanan WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row !== false) {
        $lanjut = ['baru' => 'dimasak', 'dimasak' => 'siap', 'siap' => 'selesai'];
        if (isset($lanjut[$row['status_pesanan']]) && $lanjut[$row['status_pesanan']] === $status) {
            $stmt = $pdo->prepare("UPDATE pesanan SET status_pesanan = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
        }
    }
}

header('Location: ' . $target);
exit;