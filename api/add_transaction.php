<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$user_id = get_active_user_id();

$title = trim($input['title'] ?? '');
$amount = (float)($input['amount'] ?? 0);
$type = trim($input['type'] ?? '');
$category_id = (int)($input['category_id'] ?? 0);
$transaction_date = trim($input['transaction_date'] ?? date('Y-m-d'));
$notes = trim($input['notes'] ?? '');

if (empty($title) || $amount <= 0 || !in_array($type, ['income', 'expense']) || $category_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide valid title, amount (>0), transaction type, and category.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, title, amount, type, category_id, transaction_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $amount, $type, $category_id, $transaction_date, $notes]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Transaction recorded successfully!',
        'id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save transaction: ' . $e->getMessage()]);
}
?>
