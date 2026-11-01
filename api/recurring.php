<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. LIST RECURRING TRANSACTIONS
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT r.*, c.name as category_name, c.color as category_color, c.icon as category_icon, a.account_name 
            FROM recurring_transactions r
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN accounts a ON r.account_id = a.id
            WHERE r.user_id = ?
            ORDER BY r.next_due_date ASC");
        $stmt->execute([$user_id]);
        $recurring = $stmt->fetchAll();

        $today = new DateTime();
        foreach ($recurring as &$r) {
            $dueDate = new DateTime($r['next_due_date']);
            $diff = $today->diff($dueDate);
            if ($dueDate < $today) {
                $r['due_status'] = 'overdue';
                $r['due_text'] = 'Overdue by ' . $diff->days . ' day(s)';
            } elseif ($diff->days === 0) {
                $r['due_status'] = 'due_today';
                $r['due_text'] = 'Due today!';
            } else {
                $r['due_status'] = $diff->days <= 5 ? 'due_soon' : 'upcoming';
                $r['due_text'] = 'Due in ' . $diff->days . ' day(s)';
            }
        }

        echo json_encode([
            'status' => 'success',
            'recurring' => $recurring
        ]);
        exit;
    }

    // 2. ADD RECURRING TRANSACTION
    if ($action === 'add') {
        $title = trim($input['title'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $type = trim($input['type'] ?? 'expense');
        $category_id = (int)($input['category_id'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0) ?: null;
        $frequency = trim($input['frequency'] ?? 'monthly');
        $start_date = trim($input['start_date'] ?? date('Y-m-d'));
        $next_due_date = trim($input['next_due_date'] ?? $start_date);

        if (empty($title) || $amount <= 0 || !in_array($type, ['income', 'expense']) || $category_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Valid title, amount, type, and category are required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO recurring_transactions 
            (user_id, account_id, category_id, title, amount, type, frequency, start_date, next_due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $account_id, $category_id, $title, $amount, $type, $frequency, $start_date, $next_due_date]);

        echo json_encode(['status' => 'success', 'message' => 'Recurring transaction scheduled successfully!']);
        exit;
    }

    // 3. EXECUTE / LOG NOW (CONVERT TO ACTUAL TRANSACTION & ADVANCE DUE DATE)
    if ($action === 'execute') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid recurring transaction ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM recurring_transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $rec = $stmt->fetch();

        if (!$rec) {
            echo json_encode(['status' => 'error', 'message' => 'Recurring schedule not found.']);
            exit;
        }

        // Insert into transactions
        $stmtTx = $pdo->prepare("INSERT INTO transactions 
            (user_id, account_id, title, amount, type, category_id, payment_method, transaction_date, notes, is_recurring) 
            VALUES (?, ?, ?, ?, ?, ?, 'Recurring Auto', CURRENT_DATE(), 'Generated from recurring schedule', 1)");
        $stmtTx->execute([$user_id, $rec['account_id'], $rec['title'], $rec['amount'], $rec['type'], $rec['category_id']]);

        // Advance next_due_date
        $currDue = new DateTime($rec['next_due_date']);
        if ($rec['frequency'] === 'daily') {
            $currDue->modify('+1 day');
        } elseif ($rec['frequency'] === 'weekly') {
            $currDue->modify('+1 week');
        } elseif ($rec['frequency'] === 'monthly') {
            $currDue->modify('+1 month');
        } elseif ($rec['frequency'] === 'yearly') {
            $currDue->modify('+1 year');
        }

        $newDue = $currDue->format('Y-m-d');
        $pdo->prepare("UPDATE recurring_transactions SET next_due_date = ? WHERE id = ?")->execute([$newDue, $id]);

        echo json_encode([
            'status' => 'success', 
            'message' => "Transaction recorded successfully! Next due date advanced to {$newDue}."
        ]);
        exit;
    }

    // 4. DELETE RECURRING TRANSACTION
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Recurring schedule removed.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Recurring error: ' . $e->getMessage()]);
}
?>
