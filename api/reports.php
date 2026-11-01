<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$year = (int)($_GET['year'] ?? date('Y'));
$month = trim($_GET['month'] ?? date('m'));
$month_year = sprintf('%04d-%02d', $year, $month);

try {
    // 1. MONTHLY INCOME & EXPENSE PER CATEGORY
    $stmtCatSpend = $pdo->prepare("SELECT c.id, c.name, c.type, c.color, c.icon,
        COALESCE(SUM(t.amount), 0) as total_amount,
        COUNT(t.id) as tx_count
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ? AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
        GROUP BY c.id, c.name, c.type, c.color, c.icon
        HAVING total_amount > 0
        ORDER BY total_amount DESC");
    $stmtCatSpend->execute([$user_id, $month_year]);
    $catData = $stmtCatSpend->fetchAll();

    $incomeCategories = [];
    $expenseCategories = [];
    $totalMonthIncome = 0;
    $totalMonthExpense = 0;

    foreach ($catData as $row) {
        $amt = (float)$row['total_amount'];
        if ($row['type'] === 'income') {
            $incomeCategories[] = $row;
            $totalMonthIncome += $amt;
        } else {
            $expenseCategories[] = $row;
            $totalMonthExpense += $amt;
        }
    }

    // Add percentage to expense categories
    foreach ($expenseCategories as &$ec) {
        $ec['percentage'] = $totalMonthExpense > 0 ? round(($ec['total_amount'] / $totalMonthExpense) * 100, 1) : 0;
    }

    // 2. DAILY SPENDING TREND FOR SELECTED MONTH
    $daysInMonth = (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dailySpending = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $stmtDaily = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense,
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income
            FROM transactions WHERE user_id = ? AND transaction_date = ?");
        $stmtDaily->execute([$user_id, $dateStr]);
        $dRow = $stmtDaily->fetch();

        $dailySpending[] = [
            'day' => $d,
            'date' => $dateStr,
            'expense' => (float)$dRow['expense'],
            'income' => (float)$dRow['income']
        ];
    }

    // 3. FULL YEAR MONTHLY COMPARISON (JAN - DEC)
    $yearlyMonthlyComparison = [];
    for ($m = 1; $m <= 12; $m++) {
        $myStr = sprintf('%04d-%02d', $year, $m);
        $mName = date('M', mktime(0, 0, 0, $m, 10));

        $stmtYM = $pdo->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as inc,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as exp
            FROM transactions WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
        $stmtYM->execute([$user_id, $myStr]);
        $ymRow = $stmtYM->fetch();

        $inc = (float)$ymRow['inc'];
        $exp = (float)$ymRow['exp'];
        $yearlyMonthlyComparison[] = [
            'month_num' => $m,
            'month_name' => $mName,
            'income' => $inc,
            'expense' => $exp,
            'savings' => $inc - $exp
        ];
    }

    // 4. FINANCIAL FORECASTING (PREDICTIONS & ESTIMATES)
    // Calculate 3-month trailing averages
    $stmtTrailing = $pdo->prepare("SELECT 
        AVG(monthly_income) as avg_income,
        AVG(monthly_expense) as avg_expense
        FROM (
            SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as m,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as monthly_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as monthly_expense
            FROM transactions
            WHERE user_id = ?
            GROUP BY m
            ORDER BY m DESC
            LIMIT 3
        ) as t");
    $stmtTrailing->execute([$user_id]);
    $trailingRow = $stmtTrailing->fetch();

    $predictedIncome = max(0, round((float)($trailingRow['avg_income'] ?? $totalMonthIncome)));
    $predictedExpense = max(0, round((float)($trailingRow['avg_expense'] ?? $totalMonthExpense)));
    $predictedMonthlySurplus = $predictedIncome - $predictedExpense;

    $projected6MonthSavings = max(0, $predictedMonthlySurplus * 6);
    $projected12MonthSavings = max(0, $predictedMonthlySurplus * 12);

    $cashShortageWarning = false;
    if ($predictedExpense > $predictedIncome && $predictedIncome > 0) {
        $cashShortageWarning = true;
    }

    echo json_encode([
        'status' => 'success',
        'month_year' => $month_year,
        'summary' => [
            'total_income' => $totalMonthIncome,
            'total_expense' => $totalMonthExpense,
            'net_savings' => $totalMonthIncome - $totalMonthExpense,
            'savings_rate' => $totalMonthIncome > 0 ? round((($totalMonthIncome - $totalMonthExpense) / $totalMonthIncome) * 100, 1) : 0
        ],
        'income_categories' => $incomeCategories,
        'expense_categories' => $expenseCategories,
        'daily_spending' => $dailySpending,
        'yearly_comparison' => $yearlyMonthlyComparison,
        'forecasting' => [
            'predicted_next_month_income' => $predictedIncome,
            'predicted_next_month_expense' => $predictedExpense,
            'predicted_monthly_surplus' => $predictedMonthlySurplus,
            'projected_6_month_savings' => $projected6MonthSavings,
            'projected_12_month_savings' => $projected12MonthSavings,
            'cash_shortage_warning' => $cashShortageWarning
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Reports error: ' . $e->getMessage()]);
}
?>
