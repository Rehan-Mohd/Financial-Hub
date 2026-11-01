<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. List All Accounts with Calculated Live Balances
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT a.*,
            (
                a.opening_balance 
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'income' AND user_id = ?), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'expense' AND user_id = ?), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'transfer' AND user_id = ?), 0)
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE transfer_to_account_id = a.id AND type = 'transfer' AND user_id = ?), 0)
            ) AS live_balance
            FROM accounts a
            WHERE a.user_id = ?
            ORDER BY a.account_type ASC, a.account_name ASC");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $accounts = $stmt->fetchAll();

        // Update balance column in database for consistency
        $updateStmt = $pdo->prepare("UPDATE accounts SET balance = ? WHERE id = ? AND user_id = ?");
        $total_liquid_balance = 0;
        $total_credit_debt = 0;

        foreach ($accounts as &$acc) {
            $live = (float)$acc['live_balance'];
            $updateStmt->execute([$live, $acc['id'], $user_id]);
            $acc['balance'] = $live;

            if ($acc['account_type'] === 'Credit Card') {
                $total_credit_debt += abs(min(0, $live));
            } else {
                $total_liquid_balance += max(0, $live);
            }
        }

        echo json_encode([
            'status' => 'success',
            'accounts' => $accounts,
            'summary' => [
                'total_liquid_balance' => $total_liquid_balance,
                'total_credit_debt' => $total_credit_debt,
                'net_worth' => $total_liquid_balance - $total_credit_debt
            ]
        ]);
        exit;
    }

    // 2. Add Account
    if ($action === 'add') {
        $account_name = trim($input['account_name'] ?? '');
        $account_type = trim($input['account_type'] ?? 'Bank Account');
        $opening_balance = (float)($input['opening_balance'] ?? 0.00);
        $account_number = trim($input['account_number'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');

        if (empty($account_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Account name is required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_name, account_type, opening_balance, balance, account_number, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $account_name, $account_type, $opening_balance, $opening_balance, $account_number, $color]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Account added successfully!',
            'id' => $pdo->lastInsertId()
        ]);
        exit;
    }

    // 3. Update Account
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $account_name = trim($input['account_name'] ?? '');
        $account_type = trim($input['account_type'] ?? 'Bank Account');
        $opening_balance = (float)($input['opening_balance'] ?? 0.00);
        $account_number = trim($input['account_number'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');

        if ($id <= 0 || empty($account_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Valid account ID and name required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE accounts SET account_name = ?, account_type = ?, opening_balance = ?, account_number = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$account_name, $account_type, $opening_balance, $account_number, $color, $id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Account updated successfully!']);
        exit;
    }

    // 4. Delete Account
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid account ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Account removed successfully.']);
        exit;
    }

    // 5. Transfer Money Between Accounts (Internal Transfer)
    if ($action === 'transfer') {
        $from_account_id = (int)($input['from_account_id'] ?? 0);
        $to_account_id = (int)($input['to_account_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $transfer_date = trim($input['transfer_date'] ?? date('Y-m-d'));
        $notes = trim($input['notes'] ?? 'Internal Transfer');

        if ($from_account_id <= 0 || $to_account_id <= 0 || $from_account_id === $to_account_id) {
            echo json_encode(['status' => 'error', 'message' => 'Please select two different accounts for transfer.']);
            exit;
        }

        if ($amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Transfer amount must be greater than zero.']);
            exit;
        }

        // Verify ownership of both accounts
        $stmtCheck = $pdo->prepare("SELECT id, account_name, balance FROM accounts WHERE id IN (?, ?) AND user_id = ?");
        $stmtCheck->execute([$from_account_id, $to_account_id, $user_id]);
        $rows = $stmtCheck->fetchAll();
        if (count($rows) < 2) {
            echo json_encode(['status' => 'error', 'message' => 'Accounts not found.']);
            exit;
        }

        $fromAccName = '';
        $toAccName = '';
        foreach ($rows as $r) {
            if ($r['id'] == $from_account_id) $fromAccName = $r['account_name'];
            if ($r['id'] == $to_account_id) $toAccName = $r['account_name'];
        }

        // Record transfer transaction
        // Category 18 = 'Other Expense' or internal
        $stmtTx = $pdo->prepare("INSERT INTO transactions 
            (user_id, account_id, transfer_to_account_id, title, amount, type, category_id, payment_method, transaction_date, notes) 
            VALUES (?, ?, ?, ?, 'transfer', 18, 'Internal Transfer', ?, ?)");
        $title = "Transfer: {$fromAccName} → {$toAccName}";
        $stmtTx->execute([$user_id, $from_account_id, $to_account_id, $title, $amount, $transfer_date, $notes]);

        echo json_encode([
            'status' => 'success',
            'message' => "Successfully transferred {$amount} from {$fromAccName} to {$toAccName}!"
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
