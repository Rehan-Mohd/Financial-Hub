<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. LIST SAVINGS GOALS WITH MONTHLY REQUIRED CALCULATION
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY status ASC, id DESC");
        $stmt->execute([$user_id]);
        $goals = $stmt->fetchAll();

        $total_saved = 0;
        $total_target = 0;
        $active_count = 0;
        $completed_count = 0;

        foreach ($goals as &$g) {
            $current = (float)$g['current_amount'];
            $target = (float)$g['target_amount'];
            $pct = $target > 0 ? min(100, round(($current / $target) * 100)) : 0;
            $g['percentage'] = $pct;

            // Auto-update status if reached 100%
            if ($current >= $target && $g['status'] !== 'completed') {
                $pdo->prepare("UPDATE savings_goals SET status = 'completed' WHERE id = ?")->execute([$g['id']]);
                $g['status'] = 'completed';
            }

            if ($g['status'] === 'completed') {
                $completed_count++;
            } else {
                $active_count++;
            }

            // Calculate monthly required
            $monthly_needed = 0;
            if (!empty($g['target_date']) && $g['status'] !== 'completed' && $target > $current) {
                $diff = (new DateTime($g['target_date']))->diff(new DateTime());
                $monthsRemaining = ($diff->y * 12) + $diff->m;
                if ($diff->invert == 0) {
                    $monthsRemaining = 0; // Past target date
                } else {
                    $monthsRemaining = max(1, $monthsRemaining);
                }
                $remainingAmount = $target - $current;
                $monthly_needed = $monthsRemaining > 0 ? round($remainingAmount / $monthsRemaining, 2) : $remainingAmount;
            }
            $g['monthly_needed'] = $monthly_needed;

            $total_saved += $current;
            $total_target += $target;
        }

        echo json_encode([
            'status' => 'success',
            'goals' => $goals,
            'summary' => [
                'total_saved' => $total_saved,
                'total_target' => $total_target,
                'active_count' => $active_count,
                'completed_count' => $completed_count
            ]
        ]);
        exit;
    }

    // 2. CREATE SAVINGS GOAL
    if ($action === 'add') {
        $title = trim($input['title'] ?? '');
        $target_amount = (float)($input['target_amount'] ?? 0);
        $current_amount = (float)($input['current_amount'] ?? 0);
        $target_date = trim($input['target_date'] ?? '');
        $color = trim($input['color'] ?? '#10b981');
        $notes = trim($input['notes'] ?? '');

        if (empty($title) || $target_amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a valid goal title and target amount (> 0).']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, title, target_amount, current_amount, target_date, color, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $target_amount, $current_amount, $target_date ?: null, $color, $notes]);

        echo json_encode(['status' => 'success', 'message' => 'Savings goal created successfully!']);
        exit;
    }

    // 3. DEPOSIT MONEY TO GOAL (WITH OPTIONAL ACCOUNT DEDUCTION)
    if ($action === 'deposit') {
        $goal_id = (int)($input['goal_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $account_id = (int)($input['account_id'] ?? 0) ?: null;

        if ($goal_id <= 0 || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Valid goal and deposit amount required.']);
            exit;
        }

        // Verify goal
        $stmtGoal = $pdo->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
        $stmtGoal->execute([$goal_id, $user_id]);
        $goal = $stmtGoal->fetch();

        if (!$goal) {
            echo json_encode(['status' => 'error', 'message' => 'Goal not found.']);
            exit;
        }

        $newCurrent = (float)$goal['current_amount'] + $amount;
        $target = (float)$goal['target_amount'];
        $newStatus = $newCurrent >= $target ? 'completed' : 'active';

        $stmtUpdate = $pdo->prepare("UPDATE savings_goals SET current_amount = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmtUpdate->execute([$newCurrent, $newStatus, $goal_id, $user_id]);

        // If goal achieved with this deposit, create notification
        if ($newStatus === 'completed' && $goal['status'] !== 'completed') {
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmtNotif->execute([$user_id, '🎉 Savings Goal Achieved!', "Hooray! You reached 100% of your '{$goal['title']}' goal with this deposit.", 'success']);
        }

        echo json_encode([
            'status' => 'success', 
            'message' => 'Deposit added successfully! Goal updated.',
            'new_amount' => $newCurrent,
            'goal_status' => $newStatus
        ]);
        exit;
    }

    // 4. DELETE SAVINGS GOAL
    if ($action === 'delete') {
        $goal_id = (int)($input['goal_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Savings goal removed.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Savings error: ' . $e->getMessage()]);
}
?>
