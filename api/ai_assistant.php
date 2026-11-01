<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$query = trim($input['query'] ?? '');

if (empty($query)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a question or query.']);
    exit;
}

try {
    // Fetch user context
    $stmtUser = $pdo->prepare("SELECT name, currency FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();
    $currency = $user['currency'] ?? '₹';

    $currentMonth = date('Y-m');
    $queryLower = strtolower($query);

    // Context stats
    $stmtStats = $pdo->prepare("SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = ? THEN amount ELSE 0 END), 0) as month_income,
        COALESCE(SUM(CASE WHEN type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ? THEN amount ELSE 0 END), 0) as month_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
        FROM transactions WHERE user_id = ?");
    $stmtStats->execute([$currentMonth, $currentMonth, $user_id]);
    $stats = $stmtStats->fetch();

    $monthIncome = (float)$stats['month_income'];
    $monthExpense = (float)$stats['month_expense'];
    $monthSurplus = $monthIncome - $monthExpense;

    // Accounts liquid balance
    $stmtAcc = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN account_type != 'Credit Card' THEN balance ELSE 0 END), 0) as liquid FROM accounts WHERE user_id = ?");
    $stmtAcc->execute([$user_id]);
    $totalBalance = (float)$stmtAcc->fetch()['liquid'];

    // Total savings
    $stmtSav = $pdo->prepare("SELECT COALESCE(SUM(current_amount), 0) as saved FROM savings_goals WHERE user_id = ?");
    $stmtSav->execute([$user_id]);
    $totalSaved = (float)$stmtSav->fetch()['saved'];

    $answer = "";
    $type = "general";
    $highlights = [];

    // 1. "Where did I spend the most" / "Highest spending"
    if (preg_match('/where.*spend.*most|highest.*(expense|spend)|top.*spend/i', $queryLower)) {
        $stmtTop = $pdo->prepare("SELECT c.name, SUM(t.amount) as total, COUNT(t.id) as count
            FROM transactions t JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            GROUP BY c.id, c.name ORDER BY total DESC LIMIT 3");
        $stmtTop->execute([$user_id, $currentMonth]);
        $tops = $stmtTop->fetchAll();

        if (!empty($tops)) {
            $top1 = $tops[0];
            $pct = $monthExpense > 0 ? round(($top1['total'] / $monthExpense) * 100) : 0;
            $answer = "This month, you spent the most money on <strong>{$top1['name']}</strong>, totaling <strong>{$currency}" . number_format($top1['total'], 2) . "</strong> across {$top1['count']} transactions. That accounts for <strong>{$pct}%</strong> of your total monthly expenses.";
            
            if (count($tops) > 1) {
                $answer .= "<br><br><strong>Top 3 categories this month:</strong><ol style='margin-left: 20px; margin-top: 6px;'>";
                foreach ($tops as $idx => $t) {
                    $answer .= "<li>{$t['name']}: {$currency}" . number_format($t['total'], 2) . "</li>";
                }
                $answer .= "</ol>";
            }
        } else {
            $answer = "You haven't recorded any expenses yet for this month (" . date('F Y') . ").";
        }
    }

    // 2. "How much did I spend on food" / "Spend on [category]"
    elseif (preg_match('/how much.*spend on (food|transport|shopping|bills|entertainment|rent|travel|healthcare|education)/i', $queryLower, $matches) || preg_match('/(food|grocery|fuel|shopping).*spend/i', $queryLower)) {
        $searchCat = 'food';
        if (isset($matches[1])) $searchCat = strtolower($matches[1]);
        elseif (strpos($queryLower, 'transport') !== false) $searchCat = 'transport';
        elseif (strpos($queryLower, 'shopping') !== false) $searchCat = 'shopping';

        $stmtCat = $pdo->prepare("SELECT c.name, SUM(t.amount) as total, COUNT(t.id) as count
            FROM transactions t JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND c.name LIKE ? AND t.transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            GROUP BY c.id, c.name");
        $stmtCat->execute([$user_id, "%{$searchCat}%"]);
        $catRow = $stmtCat->fetch();

        if ($catRow) {
            $answer = "In the last 30 days, you spent <strong>{$currency}" . number_format($catRow['total'], 2) . "</strong> on <strong>{$catRow['name']}</strong> across {$catRow['count']} transactions.";
        } else {
            $answer = "In the last 30 days, you had zero recorded expenses under {$searchCat}.";
        }
    }

    // 3. "Can I afford a [amount] [item]" / "Afford"
    elseif (preg_match('/afford.*?(?:₹|rs\.?|inr)?\s*([0-9,]+)/i', $queryLower, $matches) || preg_match('/can i buy.*?(?:₹|rs\.?|inr)?\s*([0-9,]+)/i', $queryLower, $matches)) {
        $cost = (float)str_replace(',', '', $matches[1]);
        if ($cost <= 0) $cost = 50000;

        $answer = "<strong>Affordability Analysis for {$currency}" . number_format($cost) . ":</strong><br>";
        if ($totalBalance >= $cost * 1.5) {
            $answer .= "✅ <strong>Yes, comfortably!</strong> Your current liquid balance is <strong>{$currency}" . number_format($totalBalance) . "</strong>, which covers this purchase while preserving a safe cash cushion.";
        } elseif ($totalBalance >= $cost) {
            $answer .= "⚠️ <strong>Yes, but proceed with caution.</strong> You have {$currency}" . number_format($totalBalance) . " in your accounts, but spending {$currency}" . number_format($cost) . " would use " . round(($cost / $totalBalance) * 100) . "% of your total liquid cash reserves.";
        } else {
            $deficit = $cost - $totalBalance;
            $monthsNeeded = $monthSurplus > 0 ? ceil($deficit / $monthSurplus) : 0;
            $answer .= "❌ <strong>Not right now.</strong> Your liquid cash balance is {$currency}" . number_format($totalBalance) . ", leaving a shortfall of {$currency}" . number_format($deficit) . ".";
            if ($monthSurplus > 0) {
                $answer .= "<br><br>💡 Based on your current monthly surplus of {$currency}" . number_format($monthSurplus) . ", you can afford this purchase in about <strong>{$monthsNeeded} month(s)</strong> of saving.";
            } else {
                $answer .= "<br><br>💡 Focus on reducing monthly expenses to create a positive cash surplus.";
            }
        }
    }

    // 4. "How can I save [amount] every month"
    elseif (preg_match('/how (?:can|to) save.*?(?:₹|rs\.?|inr)?\s*([0-9,]+)/i', $queryLower, $matches) || preg_match('/how (?:can|to) save/i', $queryLower)) {
        $targetSavings = isset($matches[1]) ? (float)str_replace(',', '', $matches[1]) : 10000;
        if ($targetSavings <= 0) $targetSavings = 10000;

        // Discretionary spending this month (Food, Shopping, Entertainment)
        $stmtDisc = $pdo->prepare("SELECT c.name, SUM(t.amount) as total FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.type = 'expense' AND c.name IN ('Food & Dining', 'Shopping & Lifestyle', 'Entertainment & Subscriptions')
            AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
            GROUP BY c.name");
        $stmtDisc->execute([$user_id, $currentMonth]);
        $discRows = $stmtDisc->fetchAll();

        $answer = "<strong>Plan to save {$currency}" . number_format($targetSavings) . " every month:</strong><br>";
        $answer .= "Your current monthly surplus is <strong>{$currency}" . number_format($monthSurplus) . "</strong>.<br><br>";
        
        $answer .= "Here are personalized adjustments to achieve your target:<br>";
        $answer .= "<ul style='margin-left: 20px; margin-top: 6px;'>";
        if (!empty($discRows)) {
            foreach ($discRows as $dr) {
                $cut = round($dr['total'] * 0.25);
                $answer .= "<li>Reduce <strong>{$dr['name']}</strong> by 25% to free up <strong>{$currency}" . number_format($cut) . "/month</strong>.</li>";
            }
        } else {
            $answer .= "<li>Set strict category budgets for Dining and Entertainment.</li>";
            $answer .= "<li>Automate a recurring transfer of {$currency}" . number_format($targetSavings) . " to your Savings Goal on salary day.</li>";
        }
        $answer .= "<li>Review recurring subscriptions and cancel unused OTT/streaming services.</li>";
        $answer .= "</ul>";
    }

    // 5. "How much money will I save by the end of the year" / "Save by year end"
    elseif (preg_match('/save by (?:the )?end of (?:the )?year|future savings|year end/i', $queryLower)) {
        $monthsLeft = 12 - (int)date('n') + 1;
        $projectedYearEnd = $totalSaved + ($monthSurplus > 0 ? $monthSurplus * $monthsLeft : 0);

        $answer = "<strong>Year-End Savings Projection:</strong><br>";
        $answer .= "With {$monthsLeft} month(s) remaining this year and an average monthly surplus of <strong>{$currency}" . number_format($monthSurplus) . "</strong>:<br><br>";
        $answer .= "• Projected additional savings: <strong>{$currency}" . number_format(max(0, $monthSurplus * $monthsLeft)) . "</strong><br>";
        $answer .= "• Estimated total savings reserve by year-end: <strong>{$currency}" . number_format($projectedYearEnd) . "</strong>.<br><br>";
        $answer .= "💡 <em>Tip: Investing part of this surplus into index funds or mutual funds can help outpace inflation!</em>";
    }

    // 6. "Which category has increased the most"
    elseif (preg_match('/which category.*(increased|grown|up)|category.*increase/i', $queryLower)) {
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $stmtComp = $pdo->prepare("SELECT c.name,
            COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = ? AND category_id = c.id AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?), 0) as this_month,
            COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id = ? AND category_id = c.id AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?), 0) as last_month
            FROM categories c WHERE c.type = 'expense'");
        $stmtComp->execute([$user_id, $currentMonth, $user_id, $lastMonth]);
        $compRows = $stmtComp->fetchAll();

        $maxIncrease = 0;
        $maxCat = null;
        foreach ($compRows as $cr) {
            $diff = (float)$cr['this_month'] - (float)$cr['last_month'];
            if ($diff > $maxIncrease) {
                $maxIncrease = $diff;
                $maxCat = $cr;
            }
        }

        if ($maxCat && $maxIncrease > 0) {
            $pctIncrease = $maxCat['last_month'] > 0 ? round(($maxIncrease / $maxCat['last_month']) * 100) : 100;
            $answer = "The category that increased the most this month is <strong>{$maxCat['name']}</strong>.<br><br>";
            $answer .= "• Last Month: {$currency}" . number_format($maxCat['last_month']) . "<br>";
            $answer .= "• This Month: {$currency}" . number_format($maxCat['this_month']) . "<br>";
            $answer .= "• Net Increase: <strong>+{$currency}" . number_format($maxIncrease) . " (+{$pctIncrease}%)</strong>.";
        } else {
            $answer = "Your category expenses have remained stable compared with last month!";
        }
    }

    // 7. "Monthly financial summary" / "Summary"
    elseif (preg_match('/monthly.*summary|financial summary|overview|how am i doing/i', $queryLower)) {
        $savingsRate = $monthIncome > 0 ? round(($monthSurplus / $monthIncome) * 100) : 0;
        $answer = "<strong>📊 Financial Summary for " . date('F Y') . ":</strong><br><br>";
        $answer .= "• Total Income: <strong>{$currency}" . number_format($monthIncome, 2) . "</strong><br>";
        $answer .= "• Total Expenses: <strong>{$currency}" . number_format($monthExpense, 2) . "</strong><br>";
        $answer .= "• Net Cash Flow: <strong style='color:" . ($monthSurplus >= 0 ? '#10b981' : '#ef4444') . ";'>{$currency}" . number_format($monthSurplus, 2) . "</strong><br>";
        $answer .= "• Savings Rate: <strong>{$savingsRate}%</strong><br>";
        $answer .= "• Current Liquid Bank/Cash Balance: <strong>{$currency}" . number_format($totalBalance, 2) . "</strong><br>";
        $answer .= "• Total Goals Saved: <strong>{$currency}" . number_format($totalSaved, 2) . "</strong>.<br><br>";
        if ($savingsRate >= 20) {
            $answer .= "🌟 <em>You are maintaining a healthy savings rate above the recommended 20% rule!</em>";
        } else {
            $answer .= "💡 <em>Aim to save at least 20% of your take-home pay by curbing impulse purchases.</em>";
        }
    }

    // 8. General Financial Intelligence Fallback
    else {
        $answer = "Based on your financial data, your current liquid balance is <strong>{$currency}" . number_format($totalBalance, 2) . "</strong> and your net monthly cash flow is <strong>{$currency}" . number_format($monthSurplus, 2) . "</strong>.<br><br>";
        $answer .= "You can ask me specific questions like:<br>";
        $answer .= "• <em>'Where did I spend the most money this month?'</em><br>";
        $answer .= "• <em>'How much did I spend on food in the last 30 days?'</em><br>";
        $answer .= "• <em>'Can I afford a ₹50,000 laptop?'</em><br>";
        $answer .= "• <em>'How can I save ₹10,000 every month?'</em><br>";
        $answer .= "• <em>'Give me a monthly financial summary.'</em>";
    }

    echo json_encode([
        'status' => 'success',
        'query' => $query,
        'answer' => $answer
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'AI Assistant error: ' . $e->getMessage()]);
}
?>
