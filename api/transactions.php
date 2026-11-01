<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // ----------------------------------------------------
    // 1. LIST TRANSACTIONS WITH FILTERS, SEARCH & PAGINATION
    // ----------------------------------------------------
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['action'])) {
        $type = $_GET['type'] ?? 'all';
        $category_id = (int)($_GET['category_id'] ?? 0);
        $account_id = (int)($_GET['account_id'] ?? 0);
        $month = $_GET['month'] ?? ''; // Format: YYYY-MM
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $sort_by = $_GET['sort_by'] ?? 'date'; // 'date', 'amount', 'title'
        $sort_order = strtoupper($_GET['sort_order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = isset($_GET['limit']) && $_GET['limit'] === 'all' ? 1000 : max(5, min(100, (int)($_GET['limit'] ?? 10)));

        $where = ["t.user_id = ?"];
        $params = [$user_id];

        if ($type !== 'all' && in_array($type, ['income', 'expense', 'transfer'])) {
            $where[] = "t.type = ?";
            $params[] = $type;
        }

        if ($category_id > 0) {
            $where[] = "t.category_id = ?";
            $params[] = $category_id;
        }

        if ($account_id > 0) {
            $where[] = "(t.account_id = ? OR t.transfer_to_account_id = ?)";
            $params[] = $account_id;
            $params[] = $account_id;
        }

        if (!empty($month)) {
            $where[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
            $params[] = $month;
        }

        if (!empty($start_date)) {
            $where[] = "t.transaction_date >= ?";
            $params[] = $start_date;
        }

        if (!empty($end_date)) {
            $where[] = "t.transaction_date <= ?";
            $params[] = $end_date;
        }

        if (!empty($search)) {
            $where[] = "(t.title LIKE ? OR t.notes LIKE ? OR t.payment_method LIKE ? OR t.payment_source LIKE ? OR c.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(" AND ", $where);

        // Sorting field
        $orderField = "t.transaction_date {$sort_order}, t.id {$sort_order}";
        if ($sort_by === 'amount') {
            $orderField = "t.amount {$sort_order}, t.transaction_date DESC";
        } elseif ($sort_by === 'title') {
            $orderField = "t.title {$sort_order}, t.transaction_date DESC";
        }

        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE {$whereClause}");
        $countStmt->execute($params);
        $total_count = (int)$countStmt->fetch()['total'];

        // Get filtered summary (total income, total expense)
        $summaryStmt = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS filtered_income,
            COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS filtered_expense
            FROM transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE {$whereClause}");
        $summaryStmt->execute($params);
        $sumRow = $summaryStmt->fetch();

        // Pagination offset
        $offset = ($page - 1) * $limit;
        $total_pages = ceil($total_count / $limit) ?: 1;

        // Fetch records
        $query = "SELECT t.*, 
            c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
            a.account_name, a.account_type, a.color AS account_color,
            a2.account_name AS transfer_to_account_name
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            LEFT JOIN accounts a2 ON t.transfer_to_account_id = a2.id
            WHERE {$whereClause}
            ORDER BY {$orderField}
            LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'transactions' => $transactions,
            'pagination' => [
                'total_count' => $total_count,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'limit' => $limit
            ],
            'summary' => [
                'income' => (float)$sumRow['filtered_income'],
                'expense' => (float)$sumRow['filtered_expense'],
                'net' => (float)$sumRow['filtered_income'] - (float)$sumRow['filtered_expense']
            ]
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 2. ADD TRANSACTION (INCOME / EXPENSE)
    // ----------------------------------------------------
    if ($action === 'add') {
        $title = trim($input['title'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $type = trim($input['type'] ?? 'expense');
        $category_id = (int)($input['category_id'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0) ?: null;
        $payment_method = trim($input['payment_method'] ?? 'UPI');
        $payment_source = trim($input['payment_source'] ?? '');
        $transaction_date = trim($input['transaction_date'] ?? date('Y-m-d'));
        $notes = trim($input['notes'] ?? '');
        $is_recurring = !empty($input['is_recurring']) ? 1 : 0;

        if (empty($title) || $amount <= 0 || !in_array($type, ['income', 'expense']) || $category_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide valid title, amount (>0), type, and category.']);
            exit;
        }

        // If no account selected, pick user's first account
        if (!$account_id) {
            $stmtDefAcc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? ORDER BY id ASC LIMIT 1");
            $stmtDefAcc->execute([$user_id]);
            $accRow = $stmtDefAcc->fetch();
            if ($accRow) {
                $account_id = $accRow['id'];
            }
        }

        $stmt = $pdo->prepare("INSERT INTO transactions 
            (user_id, account_id, title, amount, type, category_id, payment_method, payment_source, transaction_date, notes, is_recurring) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $account_id, $title, $amount, $type, $category_id, $payment_method, $payment_source, $transaction_date, $notes, $is_recurring]);
        $tx_id = $pdo->lastInsertId();

        // Check Budget Warnings if this was an expense
        if ($type === 'expense') {
            $monthYear = substr($transaction_date, 0, 7); // 'YYYY-MM'
            $stmtBudget = $pdo->prepare("SELECT b.monthly_limit, c.name as category_name FROM budgets b
                JOIN categories c ON b.category_id = c.id
                WHERE b.user_id = ? AND b.category_id = ? AND b.month_year = ?");
            $stmtBudget->execute([$user_id, $category_id, $monthYear]);
            $budget = $stmtBudget->fetch();

            if ($budget) {
                $limit = (float)$budget['monthly_limit'];
                // Calculate total spent in this category this month
                $stmtSpent = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_spent FROM transactions 
                    WHERE user_id = ? AND category_id = ? AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
                $stmtSpent->execute([$user_id, $category_id, $monthYear]);
                $spent = (float)$stmtSpent->fetch()['total_spent'];

                $pct = round(($spent / $limit) * 100);
                if ($spent >= $limit) {
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                    $stmtNotif->execute([
                        $user_id, 
                        'Budget Exceeded Alert', 
                        "You have exceeded your {$budget['category_name']} budget for this month ({$pct}% used: ₹{$spent} / ₹{$limit}).", 
                        'danger'
                    ]);
                } elseif ($pct >= 80) {
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                    $stmtNotif->execute([
                        $user_id, 
                        'Budget Warning', 
                        "You have reached {$pct}% of your {$budget['category_name']} budget for this month.", 
                        'warning'
                    ]);
                }
            }

            // Unusual High Spending alert (e.g. single transaction > 25,000)
            if ($amount >= 25000) {
                $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
                $stmtNotif->execute([
                    $user_id, 
                    'Unusual High Spending Detected', 
                    "An expense of ₹{$amount} for '{$title}' was recorded on {$transaction_date}.", 
                    'info'
                ]);
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => ucfirst($type) . ' transaction recorded successfully!',
            'id' => $tx_id
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. EDIT TRANSACTION
    // ----------------------------------------------------
    if ($action === 'edit') {
        $id = (int)($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $type = trim($input['type'] ?? 'expense');
        $category_id = (int)($input['category_id'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0) ?: null;
        $payment_method = trim($input['payment_method'] ?? 'UPI');
        $payment_source = trim($input['payment_source'] ?? '');
        $transaction_date = trim($input['transaction_date'] ?? date('Y-m-d'));
        $notes = trim($input['notes'] ?? '');

        if ($id <= 0 || empty($title) || $amount <= 0 || $category_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid transaction data provided.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE transactions SET 
            title = ?, amount = ?, type = ?, category_id = ?, account_id = ?, payment_method = ?, payment_source = ?, transaction_date = ?, notes = ? 
            WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $amount, $type, $category_id, $account_id, $payment_method, $payment_source, $transaction_date, $notes, $id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Transaction updated successfully!']);
        exit;
    }

    // ----------------------------------------------------
    // 4. DELETE TRANSACTION
    // ----------------------------------------------------
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid transaction ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Transaction deleted successfully!']);
        exit;
    }

    // ----------------------------------------------------
    // 5. IMPORT TRANSACTIONS FROM CSV (WITH AUTO-CATEGORIZATION)
    // ----------------------------------------------------
    if ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Please upload a valid CSV file.']);
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to open uploaded CSV file.']);
            exit;
        }

        // Fetch categories for keyword matching
        $cats = $pdo->query("SELECT id, name, type FROM categories")->fetchAll();
        $catMap = [];
        foreach ($cats as $c) {
            $catMap[strtolower($c['name'])] = $c['id'];
        }

        // Get user default account
        $stmtDefAcc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmtDefAcc->execute([$user_id]);
        $defaultAccountId = $stmtDefAcc->fetch()['id'] ?? null;

        $insertedCount = 0;
        $rowNumber = 0;
        $stmtInsert = $pdo->prepare("INSERT INTO transactions 
            (user_id, account_id, title, amount, type, category_id, payment_method, transaction_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;
            if ($rowNumber === 1 && (strtolower($data[0]) === 'date' || strtolower($data[0]) === 'title')) {
                continue; // Skip header
            }

            if (count($data) < 3) continue;

            // Format expected: Date, Title, Amount, [Type], [Category], [Payment Method], [Notes]
            $date = trim($data[0]);
            $title = trim($data[1]);
            $amount = abs((float)preg_replace('/[^0-9.]/', '', $data[2]));
            $type = isset($data[3]) && strtolower($data[3]) === 'income' ? 'income' : 'expense';
            $catName = isset($data[4]) ? trim($data[4]) : '';
            $method = isset($data[5]) ? trim($data[5]) : 'UPI';
            $notes = isset($data[6]) ? trim($data[6]) : 'Imported via CSV';

            // Validate date format
            $parsedDate = date('Y-m-d', strtotime($date));
            if ($parsedDate === '1970-01-01' || empty($parsedDate)) {
                $parsedDate = date('Y-m-d');
            }

            if (empty($title) || $amount <= 0) continue;

            // Auto-categorization logic based on title & keywords
            $categoryId = 18; // Default 'Other Expense'
            if ($type === 'income') {
                $categoryId = 1; // Default 'Salary'
            }

            $titleLower = strtolower($title);
            if (preg_match('/zomato|swiggy|restaurant|mcdonald|burger|pizza|starbucks|domino|grocery|supermarket|bigbasket|blinkit|zepto|food|dining|cafe|lunch|dinner/i', $titleLower)) {
                $categoryId = 8; // Food & Dining
                $type = 'expense';
            } elseif (preg_match('/uber|ola|rapido|metro|irctc|train|petrol|diesel|fuel|auto|flight|bus|transport|cab/i', $titleLower)) {
                $categoryId = 9; // Transportation
                $type = 'expense';
            } elseif (preg_match('/amazon|flipkart|myntra|ajio|zara|clothes|shoes|electronics|shopping|mall/i', $titleLower)) {
                $categoryId = 10; // Shopping
                $type = 'expense';
            } elseif (preg_match('/electricity|bescom|water|gas|lpg|wifi|broadband|airtel|jio|vi|recharge|mobile|bill|utility/i', $titleLower)) {
                $categoryId = 11; // Bills & Utilities
                $type = 'expense';
            } elseif (preg_match('/netflix|spotify|hotstar|prime|movie|pvr|inox|bookmyshow|gaming|youtube|entertainment/i', $titleLower)) {
                $categoryId = 12; // Entertainment
                $type = 'expense';
            } elseif (preg_match('/apollo|pharmacy|medplus|doctor|hospital|medicine|clinic|health/i', $titleLower)) {
                $categoryId = 13; // Healthcare
                $type = 'expense';
            } elseif (preg_match('/udemy|coursera|books|tuition|college|school|course|fee/i', $titleLower)) {
                $categoryId = 14; // Education
                $type = 'expense';
            } elseif (preg_match('/rent|housing|landlord|maintenance/i', $titleLower)) {
                $categoryId = 15; // Housing & Rent
                $type = 'expense';
            } elseif (preg_match('/salary|payroll|stipend|wages/i', $titleLower)) {
                $categoryId = 1; // Salary
                $type = 'income';
            } elseif (preg_match('/freelance|consulting|client|upwork|fiverr/i', $titleLower)) {
                $categoryId = 2; // Freelance
                $type = 'income';
            } elseif (preg_match('/dividend|interest|zerodha|groww|stocks/i', $titleLower)) {
                $categoryId = 4; // Investments
                $type = 'income';
            }

            $stmtInsert->execute([$user_id, $defaultAccountId, $title, $amount, $type, $categoryId, $method, $parsedDate, $notes]);
            $insertedCount++;
        }
        fclose($handle);

        echo json_encode([
            'status' => 'success',
            'message' => "Successfully imported {$insertedCount} transactions with automatic categorization!",
            'count' => $insertedCount
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 6. EXPORT TRANSACTIONS TO CSV
    // ----------------------------------------------------
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=financial_hub_transactions_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date', 'Title', 'Type', 'Category', 'Account', 'Amount', 'Payment Method', 'Notes']);

        $stmt = $pdo->prepare("SELECT t.id, t.transaction_date, t.title, t.type, c.name as category_name, a.account_name, t.amount, t.payment_method, t.notes
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN accounts a ON t.account_id = a.id
            WHERE t.user_id = ?
            ORDER BY t.transaction_date DESC, t.id DESC");
        $stmt->execute([$user_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['transaction_date'],
                $row['title'],
                ucfirst($row['type']),
                $row['category_name'] ?? 'Other',
                $row['account_name'] ?? 'Default Account',
                $row['amount'],
                $row['payment_method'],
                $row['notes']
            ]);
        }
        fclose($output);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction error: ' . $e->getMessage()]);
}
?>
