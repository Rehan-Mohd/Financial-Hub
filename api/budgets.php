<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    $month_year = trim($_GET['month_year'] ?? $input['month_year'] ?? date('Y-m'));

    // 1. LIST BUDGETS WITH ACTUAL SPENDING & UTILIZATION
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT b.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
            COALESCE((
                SELECT SUM(amount) FROM transactions 
                WHERE user_id = b.user_id 
                AND category_id = b.category_id 
                AND type = 'expense' 
                AND DATE_FORMAT(transaction_date, '%Y-%m') = b.month_year
            ), 0) as spent
            FROM budgets b
            JOIN categories c ON b.category_id = c.id
            WHERE b.user_id = ? AND b.month_year = ?
            ORDER BY spent DESC, b.monthly_limit DESC");
        $stmt->execute([$user_id, $month_year]);
        $budgets = $stmt->fetchAll();

        $total_budget = 0;
        $total_spent = 0;

        foreach ($budgets as &$b) {
            $limit = (float)$b['monthly_limit'];
            $spent = (float)$b['spent'];
            $remaining = max(0, $limit - $spent);
            $pct = $limit > 0 ? round(($spent / $limit) * 100) : 0;

            $status = 'normal';
            if ($spent >= $limit) {
                $status = 'exceeded';
            } elseif ($pct >= 80) {
                $status = 'warning';
            }

            $b['spent'] = $spent;
            $b['remaining'] = $remaining;
            $b['percentage'] = $pct;
            $b['status'] = $status;

            $total_budget += $limit;
            $total_spent += $spent;
        }

        echo json_encode([
            'status' => 'success',
            'month_year' => $month_year,
            'budgets' => $budgets,
            'summary' => [
                'total_budget' => $total_budget,
                'total_spent' => $total_spent,
                'total_remaining' => max(0, $total_budget - $total_spent),
                'overall_percentage' => $total_budget > 0 ? round(($total_spent / $total_budget) * 100) : 0
            ]
        ]);
        exit;
    }

    // 2. CREATE OR UPDATE A CATEGORY BUDGET
    if ($action === 'save') {
        $category_id = (int)($input['category_id'] ?? 0);
        $monthly_limit = (float)($input['monthly_limit'] ?? 0);
        $month_year = trim($input['month_year'] ?? date('Y-m'));

        if ($category_id <= 0 || $monthly_limit <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a valid category and budget limit (> 0).']);
            exit;
        }

        // Check if budget already exists for this category and month
        $stmtCheck = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month_year = ?");
        $stmtCheck->execute([$user_id, $category_id, $month_year]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $stmtUpdate = $pdo->prepare("UPDATE budgets SET monthly_limit = ? WHERE id = ? AND user_id = ?");
            $stmtUpdate->execute([$monthly_limit, $existing['id'], $user_id]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO budgets (user_id, category_id, monthly_limit, month_year) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$user_id, $category_id, $monthly_limit, $month_year]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Budget limit saved successfully!']);
        exit;
    }

    // 3. DELETE A BUDGET
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid budget ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Budget limit removed.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Budget error: ' . $e->getMessage()]);
}
?>
