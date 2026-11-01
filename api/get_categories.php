<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

try {
    $type = $_GET['type'] ?? null;
    if ($type && in_array($type, ['income', 'expense'])) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? ORDER BY id ASC");
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY type ASC, id ASC");
    }
    $categories = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'categories' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
