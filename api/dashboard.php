<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();

try {
    // 1. Fetch User Profile
    $stmtUser = $pdo->prepare("SELECT id, name, email, currency FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();
    $currency = $user['currency'] ?? '₹';

    $currentMonth = date('Y-m');
    $currentDate = date('Y-m-d');
    $daysInMonth = (int)date('t');
    $dayOfMonth = (int)date('j');

    // 2. Total Liquid Balance across user accounts
    $stmtAcc = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN account_type != 'Credit Card' THEN balance ELSE 0 END), 0) as total_liquid,
        COALESCE(SUM(CASE WHEN account_type = 'Credit Card' AND balance < 0 THEN ABS(balance) ELSE 0 END), 0) as credit_debt
        FROM accounts WHERE user_id = ?");
    $stmtAcc->execute([$user_id]);
    $accRow = $stmtAcc->fetch();
    $total_liquid = (float)$accRow['total_liquid'];
    $credit_debt = (float)$accRow['credit_debt'];

    // 3. This Month Financial Totals
    $stmtMonth = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS month_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS month_expense
        FROM transactions 
        WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
    $stmtMonth->execute([$user_id, $currentMonth]);
    $monthTotals = $stmtMonth->fetch();
    $month_income = (float)$monthTotals['month_income'];
    $month_expense = (float)$monthTotals['month_expense'];
    $month_balance = $month_income - $month_expense;
    $daily_avg_spend = $dayOfMonth > 0 ? round($month_expense / $dayOfMonth, 2) : 0;

    // 4. All-time Financial Totals
    $stmtAll = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
        FROM transactions WHERE user_id = ?");
    $stmtAll->execute([$user_id]);
    $allTotals = $stmtAll->fetch();
    $total_income = (float)$allTotals['total_income'];
    $total_expense = (float)$allTotals['total_expense'];
    $net_balance = $total_income - $total_expense;

    // 5. Total Savings (from savings_goals)
    $stmtSavings = $pdo->prepare("SELECT 
        COALESCE(SUM(current_amount), 0) as total_saved,
        COALESCE(SUM(target_amount), 0) as total_target
        FROM savings_goals WHERE user_id = ?");
    $stmtSavings->execute([$user_id]);
    $savingsRow = $stmtSavings->fetch();
    $total_saved = (float)$savingsRow['total_saved'];
    $total_target = (float)$savingsRow['total_target'];

    // 6. Investments Total
    $stmtInv = $pdo->prepare("SELECT 
        COALESCE(SUM(amount_invested), 0) as total_invested,
        COALESCE(SUM(current_value), 0) as total_current_val
        FROM investments WHERE user_id = ?");
    $stmtInv->execute([$user_id]);
    $invRow = $stmtInv->fetch();
    $total_invested = (float)$invRow['total_invested'];
    $total_investments = (float)$invRow['total_current_val'];

    // 7. Debts & Loans Total
    $stmtDebts = $pdo->prepare("SELECT 
        COALESCE(SUM(current_balance), 0) as total_debt,
        COALESCE(SUM(monthly_emi), 0) as total_monthly_emi
        FROM debts_loans WHERE user_id = ?");
    $stmtDebts->execute([$user_id]);
    $debtRow = $stmtDebts->fetch();
    $total_debt = (float)$debtRow['total_debt'] + $credit_debt;
    $total_monthly_emi = (float)$debtRow['total_monthly_emi'];

    // 8. Category Breakdown for This Month (Expense Doughnut)
    $stmtCategory = $pdo->prepare("SELECT c.name, c.color, c.icon, COALESCE(SUM(t.amount), 0) as total 
        FROM categories c
        JOIN transactions t ON c.id = t.category_id 
        WHERE t.type = 'expense' AND t.user_id = ? AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
        GROUP BY c.id, c.name, c.color, c.icon
        HAVING total > 0
        ORDER BY total DESC");
    $stmtCategory->execute([$user_id, $currentMonth]);
    $category_breakdown = $stmtCategory->fetchAll();

    // If no data this month, get all time
    if (empty($category_breakdown)) {
        $stmtCategoryAll = $pdo->prepare("SELECT c.name, c.color, c.icon, COALESCE(SUM(t.amount), 0) as total 
            FROM categories c
            JOIN transactions t ON c.id = t.category_id 
            WHERE t.type = 'expense' AND t.user_id = ?
            GROUP BY c.id, c.name, c.color, c.icon
            HAVING total > 0
            ORDER BY total DESC");
        $stmtCategoryAll->execute([$user_id]);
        $category_breakdown = $stmtCategoryAll->fetchAll();
    }

    // 9. Last 6 Months Income vs Expense History (Bar / Area Chart)
    $history = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} month"));
        $mLabel = date('M Y', strtotime("-{$i} month"));
        
        $stmtH = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS inc,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS exp
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
        $stmtH->execute([$user_id, $m]);
        $hRow = $stmtH->fetch();

        $history[] = [
            'month' => $m,
            'label' => $mLabel,
            'income' => (float)$hRow['inc'],
            'expense' => (float)$hRow['exp']
        ];
    }

    // 10. Recent Transactions (last 6)
    $stmtTx = $pdo->prepare("SELECT t.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon, a.account_name 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC 
        LIMIT 6");
    $stmtTx->execute([$user_id]);
    $recent_transactions = $stmtTx->fetchAll();

    // 11. Budget Utilization Check
    $stmtBudgets = $pdo->prepare("SELECT b.*, c.name as category_name,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = b.user_id AND category_id = b.category_id AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = b.month_year), 0) as spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = ? AND b.month_year = ?");
    $stmtBudgets->execute([$user_id, $currentMonth]);
    $budgets = $stmtBudgets->fetchAll();

    $budget_total_limit = 0;
    $budget_total_spent = 0;
    $budget_alerts_count = 0;
    foreach ($budgets as $b) {
        $limit = (float)$b['monthly_limit'];
        $spent = (float)$b['spent'];
        $budget_total_limit += $limit;
        $budget_total_spent += $spent;
        if ($spent >= $limit) $budget_alerts_count++;
    }

    // 12. Dynamic Financial Health Score (0 - 100)
    // 1. Savings rate (Weight 30%): (Month Income - Month Expense) / Month Income * 100
    $savingsRate = $month_income > 0 ? max(0, min(100, (($month_income - $month_expense) / $month_income) * 100)) : 0;
    $scoreSavings = ($savingsRate / 50) * 30; // 50% savings rate gives max 30 pts

    // 2. Budget adherence (Weight 25%):
    $scoreBudget = 25;
    if ($budget_total_limit > 0) {
        $utilization = ($budget_total_spent / $budget_total_limit) * 100;
        if ($utilization <= 100) {
            $scoreBudget = 25 - (($utilization / 100) * 5); // 20-25 pts
        } else {
            $scoreBudget = max(5, 25 - (($utilization - 100) * 0.5));
        }
    }

    // 3. Debt-to-income ratio (Weight 20%):
    $scoreDebt = 20;
    if ($month_income > 0) {
        $debtRatio = ($total_monthly_emi / $month_income) * 100;
        if ($debtRatio > 40) $scoreDebt = 5;
        elseif ($debtRatio > 25) $scoreDebt = 12;
        elseif ($debtRatio > 0) $scoreDebt = 16;
        else $scoreDebt = 20;
    }

    // 4. Emergency Fund Coverage (Weight 15%):
    // Saved amount / monthly expense (Target 3-6 months)
    $scoreEmergency = 15;
    if ($month_expense > 0) {
        $monthsCovered = $total_saved / $month_expense;
        $scoreEmergency = min(15, ($monthsCovered / 3) * 15);
    }

    // 5. Investment Habit (Weight 10%):
    $scoreInvestment = $total_investments > 0 ? 10 : 5;

    $totalScore = round(min(100, max(10, $scoreSavings + $scoreBudget + $scoreDebt + $scoreEmergency + $scoreInvestment)));

    $healthGrade = 'Good';
    $healthBadgeColor = '#10b981';
    $healthSummary = 'Your financial habits are healthy. Keep tracking budgets and building savings!';
    if ($totalScore >= 85) {
        $healthGrade = 'Excellent';
        $healthBadgeColor = '#10b981';
        $healthSummary = 'Outstanding financial discipline! You have strong savings and low debt burden.';
    } elseif ($totalScore >= 70) {
        $healthGrade = 'Good';
        $healthBadgeColor = '#06b6d4';
        $healthSummary = 'Healthy cash flow with steady savings. Watch discretionary budget categories.';
    } elseif ($totalScore >= 50) {
        $healthGrade = 'Fair';
        $healthBadgeColor = '#f59e0b';
        $healthSummary = 'Moderate score. Increasing your monthly savings and cutting debt will boost your score.';
    } else {
        $healthGrade = 'Needs Attention';
        $healthBadgeColor = '#ef4444';
        $healthSummary = 'High expense ratio or debt detected. Focus on reducing non-essential spending.';
    }

    // 13. Dynamic AI Smart Insights Snippet
    $insights = [];
    if (!empty($category_breakdown)) {
        $topCat = $category_breakdown[0];
        $pctOfTotal = $month_expense > 0 ? round(($topCat['total'] / $month_expense) * 100) : 0;
        $insights[] = "Your highest spending category this month is <strong>{$topCat['name']}</strong> ({$currency}" . number_format($topCat['total']) . ", {$pctOfTotal}% of total).";
    }

    if ($savingsRate >= 20) {
        $insights[] = "Great job! You saved <strong>" . round($savingsRate) . "%</strong> of your income this month.";
    } elseif ($month_income > 0) {
        $insights[] = "Try curbing restaurant and shopping expenses by 15% to increase your monthly savings rate.";
    }

    if ($budget_alerts_count > 0) {
        $insights[] = "⚠️ <strong>{$budget_alerts_count} budget categories</strong> have reached or exceeded monthly limits.";
    }

    // Unread Notifications Count
    $stmtNotifCount = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotifCount->execute([$user_id]);
    $unreadNotifs = (int)$stmtNotifCount->fetch()['unread'];

    echo json_encode([
        'status' => 'success',
        'user' => $user,
        'summary' => [
            'total_balance' => $total_liquid > 0 ? $total_liquid : $net_balance,
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'month_income' => $month_income,
            'month_expense' => $month_expense,
            'month_balance' => $month_balance,
            'daily_avg_spend' => $daily_avg_spend,
            'total_savings' => $total_saved,
            'total_investments' => $total_investments,
            'total_debt' => $total_debt
        ],
        'financial_health' => [
            'score' => $totalScore,
            'grade' => $healthGrade,
            'badge_color' => $healthBadgeColor,
            'summary' => $healthSummary,
            'savings_rate' => round($savingsRate),
            'budget_adherence' => round($scoreBudget * 4),
            'debt_management' => round($scoreDebt * 5),
            'emergency_fund_months' => $month_expense > 0 ? round($total_saved / $month_expense, 1) : 0
        ],
        'history_chart' => $history,
        'category_breakdown' => $category_breakdown,
        'recent_transactions' => $recent_transactions,
        'ai_insights' => $insights,
        'unread_notifications' => $unreadNotifs
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Dashboard error: ' . $e->getMessage()]);
}
?>
