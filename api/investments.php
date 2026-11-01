<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. LIST INVESTMENTS & PORTFOLIO METRICS
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY current_value DESC");
        $stmt->execute([$user_id]);
        $investments = $stmt->fetchAll();

        $total_invested = 0;
        $total_current = 0;
        $total_dividends = 0;

        foreach ($investments as &$inv) {
            $invested = (float)$inv['amount_invested'];
            $current = (float)$inv['current_value'];
            $div = (float)($inv['dividends'] ?? 0);
            $profit = ($current + $div) - $invested;
            $roi = $invested > 0 ? round(($profit / $invested) * 100, 2) : 0;

            $inv['profit_loss'] = round($profit, 2);
            $inv['roi_percentage'] = $roi;

            $total_invested += $invested;
            $total_current += $current;
            $total_dividends += $div;
        }

        $overall_profit = ($total_current + $total_dividends) - $total_invested;
        $overall_roi = $total_invested > 0 ? round(($overall_profit / $total_invested) * 100, 2) : 0;

        // Asset allocation breakdown
        $stmtBreakdown = $pdo->prepare("SELECT asset_type, SUM(current_value) as total_val 
            FROM investments WHERE user_id = ? GROUP BY asset_type ORDER BY total_val DESC");
        $stmtBreakdown->execute([$user_id]);
        $asset_breakdown = $stmtBreakdown->fetchAll();

        echo json_encode([
            'status' => 'success',
            'investments' => $investments,
            'asset_breakdown' => $asset_breakdown,
            'summary' => [
                'total_invested' => $total_invested,
                'total_current_value' => $total_current,
                'total_dividends' => $total_dividends,
                'total_profit_loss' => round($overall_profit, 2),
                'total_roi_percentage' => $overall_roi
            ]
        ]);
        exit;
    }

    // 2. ADD INVESTMENT ASSET
    if ($action === 'add') {
        $asset_name = trim($input['asset_name'] ?? '');
        $asset_type = trim($input['asset_type'] ?? 'Stocks');
        $amount_invested = (float)($input['amount_invested'] ?? 0);
        $current_value = (float)($input['current_value'] ?? $amount_invested);
        $dividends = (float)($input['dividends'] ?? 0.00);
        $notes = trim($input['notes'] ?? '');

        if (empty($asset_name) || $amount_invested <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide asset name and invested amount (> 0).']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO investments 
            (user_id, asset_name, asset_type, amount_invested, current_value, dividends, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $asset_name, $asset_type, $amount_invested, $current_value, $dividends, $notes]);

        echo json_encode(['status' => 'success', 'message' => 'Investment asset added to portfolio!']);
        exit;
    }

    // 3. UPDATE CURRENT VALUATION
    if ($action === 'update_valuation') {
        $id = (int)($input['id'] ?? 0);
        $current_value = (float)($input['current_value'] ?? 0);
        $dividends = (float)($input['dividends'] ?? 0);

        if ($id <= 0 || $current_value < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid asset ID or valuation.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE investments SET current_value = ?, dividends = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$current_value, $dividends, $id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Asset valuation updated!']);
        exit;
    }

    // 4. DELETE INVESTMENT
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM investments WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Investment asset removed.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Investments error: ' . $e->getMessage()]);
}
?>
