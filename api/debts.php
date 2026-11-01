<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. LIST DEBTS & LOANS WITH EMI CALCULATIONS
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM debts_loans WHERE user_id = ? ORDER BY current_balance DESC");
        $stmt->execute([$user_id]);
        $debts = $stmt->fetchAll();

        $total_principal = 0;
        $total_balance = 0;
        $total_emi = 0;

        foreach ($debts as &$d) {
            $principal = (float)$d['principal_amount'];
            $balance = (float)$d['current_balance'];
            $emi = (float)$d['monthly_emi'];

            $paid = max(0, $principal - $balance);
            $pctPaid = $principal > 0 ? min(100, round(($paid / $principal) * 100)) : 0;
            $d['pct_paid'] = $pctPaid;

            // Estimated months to payoff
            $monthsLeft = $emi > 0 ? ceil($balance / $emi) : 0;
            $d['months_left'] = $monthsLeft;

            $total_principal += $principal;
            $total_balance += $balance;
            $total_emi += $emi;
        }

        echo json_encode([
            'status' => 'success',
            'debts' => $debts,
            'summary' => [
                'total_principal' => $total_principal,
                'total_balance' => $total_balance,
                'total_paid' => max(0, $total_principal - $total_balance),
                'total_monthly_emi' => $total_emi
            ]
        ]);
        exit;
    }

    // 2. ADD LOAN / DEBT
    if ($action === 'add') {
        $loan_name = trim($input['loan_name'] ?? '');
        $lender = trim($input['lender'] ?? '');
        $loan_type = trim($input['loan_type'] ?? 'Personal Loan');
        $principal_amount = (float)($input['principal_amount'] ?? 0);
        $current_balance = (float)($input['current_balance'] ?? $principal_amount);
        $interest_rate = (float)($input['interest_rate'] ?? 10.00);
        $monthly_emi = (float)($input['monthly_emi'] ?? 0);
        $tenure_months = (int)($input['tenure_months'] ?? 36);
        $start_date = trim($input['start_date'] ?? date('Y-m-d'));

        if (empty($loan_name) || $principal_amount <= 0 || $monthly_emi <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide valid loan name, principal amount, and monthly EMI.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO debts_loans 
            (user_id, loan_name, lender, loan_type, principal_amount, current_balance, interest_rate, monthly_emi, tenure_months, start_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $loan_name, $lender, $loan_type, $principal_amount, $current_balance, $interest_rate, $monthly_emi, $tenure_months, $start_date]);

        echo json_encode(['status' => 'success', 'message' => 'Loan record added successfully!']);
        exit;
    }

    // 3. PAY EMI (REDUCE BALANCE & LOG EXPENSE TRANSACTION)
    if ($action === 'pay_emi') {
        $id = (int)($input['id'] ?? 0);
        $payment_amount = (float)($input['payment_amount'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0) ?: null;

        if ($id <= 0 || $payment_amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid loan ID or payment amount.']);
            exit;
        }

        $stmtLoan = $pdo->prepare("SELECT * FROM debts_loans WHERE id = ? AND user_id = ?");
        $stmtLoan->execute([$id, $user_id]);
        $loan = $stmtLoan->fetch();

        if (!$loan) {
            echo json_encode(['status' => 'error', 'message' => 'Loan record not found.']);
            exit;
        }

        // Reduce balance
        $newBalance = max(0, (float)$loan['current_balance'] - $payment_amount);
        $pdo->prepare("UPDATE debts_loans SET current_balance = ? WHERE id = ?")->execute([$newBalance, $id]);

        // Insert expense transaction under Category 17 (Debt & Loan EMI)
        $txTitle = "EMI Payment: " . $loan['loan_name'];
        $stmtTx = $pdo->prepare("INSERT INTO transactions 
            (user_id, account_id, title, amount, type, category_id, payment_method, transaction_date, notes) 
            VALUES (?, ?, ?, ?, 'expense', 17, 'EMI Auto-Debit', CURRENT_DATE(), ?)");
        $stmtTx->execute([$user_id, $account_id, $txTitle, $payment_amount, "Recorded EMI payment. Remaining balance: {$newBalance}"]);

        echo json_encode([
            'status' => 'success',
            'message' => "EMI of {$payment_amount} paid successfully! Remaining balance updated to {$newBalance}.",
            'new_balance' => $newBalance
        ]);
        exit;
    }

    // 4. DELETE LOAN
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM debts_loans WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Loan record removed.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Debt error: ' . $e->getMessage()]);
}
?>
