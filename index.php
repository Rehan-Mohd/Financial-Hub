<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Hub | Personal Finance, Savings & Wealth Analytics</title>
    
    <!-- Google Fonts & Chart.js -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- ========================================================
         1. AUTHENTICATION SECTION (Login / Register / Forgot Password)
         ======================================================== -->
    <div id="authSection" class="auth-wrapper" style="display: none;">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Financial Hub</h1>
                <p>Smart Personal Finance, Budgeting & Wealth Platform</p>
            </div>

            <!-- Auth Tabs -->
            <div class="auth-nav">
                <button type="button" class="auth-tab-btn active" data-auth-tab="loginForm">Sign In</button>
                <button type="button" class="auth-tab-btn" data-auth-tab="registerForm">Create Account</button>
                <button type="button" class="auth-tab-btn" data-auth-tab="forgotForm">Reset Password</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form active">
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" class="form-control" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">🔐 Sign In to Account</button>
                
                <div class="auth-helper-links">
                    <a href="javascript:void(0)" onclick="switchAuthTab('forgotForm')">Forgot password?</a>
                    <a href="javascript:void(0)" onclick="switchAuthTab('registerForm')">New user? Register</a>
                </div>

                <div class="demo-login-box">
                    <p style="font-size:0.8rem; color:#94a3b8; margin-bottom: 0.6rem;">Quick Testing Access:</p>
                    <button type="button" class="btn-secondary" style="width:100%; justify-content:center;" onclick="quickDemoLogin()">
                        ⚡ One-Click Demo Login (Rehan Mohd)
                    </button>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="regName">Full Name</label>
                    <input type="text" id="regName" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" class="form-control" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label for="regPassword">Password (Min. 3 chars)</label>
                    <input type="password" id="regPassword" class="form-control" placeholder="••••••••" minlength="3" required>
                </div>
                <div class="form-group">
                    <label for="regCurrency">Default Currency Symbol</label>
                    <select id="regCurrency" class="form-control">
                        <option value="₹" selected>Indian Rupee (₹)</option>
                        <option value="$">US Dollar ($)</option>
                        <option value="€">Euro (€)</option>
                        <option value="£">British Pound (£)</option>
                        <option value="A$">Australian Dollar (A$)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="regQuestion">Security Question (For Password Recovery)</label>
                    <select id="regQuestion" class="form-control">
                        <option value="What is your favorite city?">What is your favorite city?</option>
                        <option value="What was your first school name?">What was your first school name?</option>
                        <option value="What is your pet's name?">What is your pet's name?</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="regAnswer">Security Answer</label>
                    <input type="text" id="regAnswer" class="form-control" placeholder="Your secret answer" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">✨ Register & Get Started</button>
                <div class="auth-helper-links" style="justify-content: center; margin-top: 1rem;">
                    <span>Already have an account? <a href="javascript:void(0)" onclick="switchAuthTab('loginForm')">Sign in here</a></span>
                </div>
            </form>

            <!-- Forgot / Reset Password Form -->
            <form id="forgotForm" class="auth-form">
                <div class="form-group">
                    <label for="forgotEmail">Your Registered Email</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="email" id="forgotEmail" class="form-control" placeholder="name@example.com" required>
                        <button type="button" class="btn-secondary" onclick="fetchSecurityQuestion()" style="white-space:nowrap;">Verify</button>
                    </div>
                </div>
                <div id="forgotStep2" style="display:none;">
                    <div class="form-group">
                        <label>Security Question:</label>
                        <p id="displaySecQuestion" style="font-size:0.9rem; color:#a5b4fc; background:rgba(255,255,255,0.05); padding:0.6rem; border-radius:8px;"></p>
                    </div>
                    <div class="form-group">
                        <label for="forgotAnswer">Your Security Answer</label>
                        <input type="text" id="forgotAnswer" class="form-control" placeholder="Enter answer" required>
                    </div>
                    <div class="form-group">
                        <label for="forgotNewPassword">New Password</label>
                        <input type="password" id="forgotNewPassword" class="form-control" placeholder="New password (min 3 chars)" minlength="3" required>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">🔑 Reset My Password</button>
                </div>
                <div class="auth-helper-links" style="justify-content: center; margin-top: 1rem;">
                    <a href="javascript:void(0)" onclick="switchAuthTab('loginForm')">← Back to Sign In</a>
                </div>
            </form>

        </div>
    </div>


    <!-- ========================================================
         2. MAIN DASHBOARD APPLICATION
         ======================================================== -->
    <div id="appSection" class="container" style="display: none;">
        
        <!-- Header Section -->
        <header class="app-header">
            <div class="app-title">
                <h1>Financial Hub</h1>
                <p>Personal Finance, Budget Adherence & Portfolio Intelligence</p>
            </div>
            
            <div class="header-right">
                <!-- In-App Notification Bell -->
                <div class="icon-btn" id="notifBellBtn" title="Notifications & Alerts" onclick="toggleNotificationsDrawer()">
                    🔔
                    <span id="notifBadge" class="badge-count" style="display:none;">0</span>
                </div>

                <!-- Notifications Dropdown Drawer -->
                <div id="notificationsDrawer" class="notifications-drawer">
                    <div class="notif-header">
                        <h4>Alerts & Reminders</h4>
                        <div style="display:flex; gap:0.4rem;">
                            <button class="btn-secondary" style="padding:0.2rem 0.5rem; font-size:0.75rem;" onclick="markAllNotificationsRead()">Mark Read</button>
                            <button class="btn-secondary" style="padding:0.2rem 0.5rem; font-size:0.75rem;" onclick="clearAllNotifications()">Clear</button>
                        </div>
                    </div>
                    <div id="notifList" class="notif-list">
                        <div style="padding:1.5rem; text-align:center; color:#94a3b8; font-size:0.85rem;">No new notifications</div>
                    </div>
                </div>

                <!-- User Profile Badge -->
                <div class="user-badge" onclick="switchMainTab('profile')" title="View / Edit Profile">
                    <div class="user-avatar-circle" id="userAvatar">RM</div>
                    <span id="userNameDisplay">Rehan Mohd</span>
                </div>

                <!-- Logout Button -->
                <button class="btn-logout" onclick="handleLogout()" title="Log out">
                    <span>🚪</span> Logout
                </button>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="nav-tabs">
            <button class="tab-btn active" data-tab="dashboard">📊 Dashboard</button>
            <button class="tab-btn" data-tab="income_expense">💰 Income & Expense</button>
            <button class="tab-btn" data-tab="transactions">📑 All Transactions</button>
            <button class="tab-btn" data-tab="budgets_goals">🎯 Budgets & Goals</button>
            <button class="tab-btn" data-tab="accounts">💳 Accounts & Wallets</button>
            <button class="tab-btn" data-tab="recurring_debts">🔄 Recurring & Debts</button>
            <button class="tab-btn" data-tab="investments">📈 Investments</button>
            <button class="tab-btn" data-tab="reports">📑 Reports & Forecast</button>
            <button class="tab-btn" data-tab="ai_assistant">🤖 AI Assistant</button>
            <button class="tab-btn" data-tab="profile">⚙️ Profile</button>
        </nav>

        <!-- Top Metric Summary Cards -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="icon-box balance">⚖</div>
                <div class="metric-info">
                    <p>Total Balance</p>
                    <h2 id="totalBalance">₹0.00</h2>
                    <small>Liquid Net Worth</small>
                </div>
            </div>
            <div class="metric-card">
                <div class="icon-box income">↓</div>
                <div class="metric-info">
                    <p>Monthly Income</p>
                    <h2 id="monthIncome">₹0.00</h2>
                    <small id="totalIncomeSub">All-time: ₹0</small>
                </div>
            </div>
            <div class="metric-card">
                <div class="icon-box expense">↑</div>
                <div class="metric-info">
                    <p>Monthly Spending</p>
                    <h2 id="monthExpense">₹0.00</h2>
                    <small id="dailyAvgSpendSub">Daily avg: ₹0</small>
                </div>
            </div>
            <div class="metric-card">
                <div class="icon-box savings">🎯</div>
                <div class="metric-info">
                    <p>Total Savings</p>
                    <h2 id="totalSavingsSummary">₹0.00</h2>
                    <small>Goals Reserve</small>
                </div>
            </div>
            <div class="metric-card">
                <div class="icon-box investment">📈</div>
                <div class="metric-info">
                    <p>Investments Value</p>
                    <h2 id="totalInvestmentsSummary">₹0.00</h2>
                    <small>Portfolio Holdings</small>
                </div>
            </div>
            <div class="metric-card">
                <div class="icon-box debt">💳</div>
                <div class="metric-info">
                    <p>Outstanding Debt</p>
                    <h2 id="totalDebtSummary">₹0.00</h2>
                    <small>Loans & Credit</small>
                </div>
            </div>
        </section>

        <!-- ========================================================
             TAB 1: DASHBOARD
             ======================================================== -->
        <div id="dashboard" class="tab-content active">
            
            <!-- Financial Health Score & Smart AI Insights Card -->
            <div class="health-score-card">
                <div class="health-score-left">
                    <div class="score-circle" id="healthScoreCircle">
                        <div class="score-inner">
                            <span id="healthScoreValue">78</span>
                            <span>/ 100</span>
                        </div>
                    </div>
                    <div class="health-details">
                        <h3>Financial Health: <span id="healthScoreGrade" class="health-badge" style="background:rgba(16,185,129,0.2); color:#10b981;">Good</span></h3>
                        <p id="healthScoreSummary">Your financial habits are healthy. Keep tracking budgets and building savings!</p>
                    </div>
                </div>

                <div class="health-metrics-mini">
                    <div class="mini-metric">
                        <p>Savings Rate</p>
                        <strong id="healthSavingsRate">35%</strong>
                    </div>
                    <div class="mini-metric">
                        <p>Budget Adherence</p>
                        <strong id="healthBudgetScore">82%</strong>
                    </div>
                    <div class="mini-metric">
                        <p>Debt Ratio</p>
                        <strong id="healthDebtScore">14%</strong>
                    </div>
                    <div class="mini-metric">
                        <p>Emergency Cover</p>
                        <strong id="healthEmergencyCover">4.2 mos</strong>
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics Grid -->
            <div class="dashboard-grid">
                
                <!-- Left Column: Spending Breakdown & AI Insights -->
                <div class="left-column">
                    <div class="card">
                        <h3 class="card-title">Category Spending Allocation</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>

                    <!-- AI Insights Banner -->
                    <div class="card" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(99, 102, 241, 0.12)); border-color: rgba(99, 102, 241, 0.3);">
                        <h3 class="card-title">💡 Smart AI Financial Insights</h3>
                        <div id="dashboardAiInsights" style="font-size:0.9rem; line-height:1.6; color:#cbd5e1;">
                            Analyzing your spending patterns...
                        </div>
                        <div style="margin-top:1rem;">
                            <button class="btn-secondary" style="font-size:0.8rem;" onclick="switchMainTab('ai_assistant')">💬 Ask AI Assistant a Question</button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Income vs Expense Trend & Recent Ledger -->
                <div class="right-column">
                    <div class="card">
                        <h3 class="card-title">Income vs. Expense Trend (6 Months)</h3>
                        <div style="position: relative; height: 260px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-title">
                            <span>Recent Transactions</span>
                            <button class="btn-secondary" style="font-size:0.8rem;" onclick="switchMainTab('transactions')">View All →</button>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Account</th>
                                    </tr>
                                </thead>
                                <tbody id="recentTransactionsBody">
                                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Loading transactions...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================
             TAB 2: INCOME & EXPENSE MANAGEMENT
             ======================================================== -->
        <div id="income_expense" class="tab-content">
            <div class="dashboard-grid">
                
                <!-- Left Column: Add Income & Add Expense Forms -->
                <div class="left-column">
                    
                    <!-- Record Expense Card -->
                    <div class="card" style="border-top: 4px solid var(--accent-expense);">
                        <h3 class="card-title">
                            <span>➖ Record New Expense</span>
                            <span class="badge" style="background:rgba(244,63,94,0.15); color:var(--accent-expense);">Smart Categorization</span>
                        </h3>
                        <form id="quickExpenseForm">
                            <div class="form-group">
                                <label for="expTitle">Description / Title</label>
                                <input type="text" id="expTitle" class="form-control" placeholder="e.g. Zomato Dinner, Uber Ride, Amazon..." oninput="autoSuggestCategory(this.value, 'expCategory')" required>
                                <small style="font-size:0.75rem; color:#64748b;">Keywords like Zomato, Uber, Amazon auto-select categories!</small>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="expAmount">Amount (<span class="curr-sym">₹</span>)</label>
                                    <input type="number" step="0.01" id="expAmount" class="form-control" placeholder="0.00" required>
                                </div>
                                <div style="flex:1;">
                                    <label for="expDate">Date</label>
                                    <input type="date" id="expDate" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="expCategory">Expense Category</label>
                                    <select id="expCategory" class="form-control" required>
                                        <option value="">Loading categories...</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label for="expAccount">Pay From Account</label>
                                    <select id="expAccount" class="form-control" required>
                                        <option value="">Loading accounts...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="expMethod">Payment Method</label>
                                    <select id="expMethod" class="form-control">
                                        <option value="UPI">UPI</option>
                                        <option value="Debit Card">Debit Card</option>
                                        <option value="Credit Card">Credit Card</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Net Banking">Net Banking</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label for="expNotes">Notes (Optional)</label>
                                    <input type="text" id="expNotes" class="form-control" placeholder="Optional details">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary" style="background:linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">+ Add Expense</button>
                        </form>
                    </div>

                    <!-- Record Income Card -->
                    <div class="card" style="border-top: 4px solid var(--accent-income);">
                        <h3 class="card-title">
                            <span>➕ Record New Income</span>
                            <span class="badge" style="background:rgba(16,185,129,0.15); color:var(--accent-income);">Inflow</span>
                        </h3>
                        <form id="quickIncomeForm">
                            <div class="form-group">
                                <label for="incTitle">Income Source / Title</label>
                                <input type="text" id="incTitle" class="form-control" placeholder="e.g. Monthly Salary, Freelance Milestone..." required>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="incAmount">Amount (<span class="curr-sym">₹</span>)</label>
                                    <input type="number" step="0.01" id="incAmount" class="form-control" placeholder="0.00" required>
                                </div>
                                <div style="flex:1;">
                                    <label for="incDate">Date Received</label>
                                    <input type="date" id="incDate" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="incCategory">Income Category</label>
                                    <select id="incCategory" class="form-control" required>
                                        <option value="">Loading categories...</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label for="incAccount">Deposit To Account</label>
                                    <select id="incAccount" class="form-control" required>
                                        <option value="">Loading accounts...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="incSource">Employer / Payer</label>
                                    <input type="text" id="incSource" class="form-control" placeholder="e.g. Google, Client, Bank">
                                </div>
                                <div style="flex:1;">
                                    <label for="incNotes">Notes (Optional)</label>
                                    <input type="text" id="incNotes" class="form-control" placeholder="Bonus, invoice #">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);">+ Add Income</button>
                        </form>
                    </div>

                </div>

                <!-- Right Column: Cash Flow Overview & Monthly History -->
                <div class="right-column">
                    <div class="card">
                        <h3 class="card-title">This Month Cash Flow Overview</h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:12px; padding:1.2rem;">
                                <p style="font-size:0.8rem; color:#a7f3d0; text-transform:uppercase; font-weight:700;">Income Received</p>
                                <h3 id="flowMonthIncome" style="color:#10b981; font-size:1.6rem; margin-top:0.3rem;">₹0.00</h3>
                            </div>
                            <div style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.25); border-radius:12px; padding:1.2rem;">
                                <p style="font-size:0.8rem; color:#fecdd3; text-transform:uppercase; font-weight:700;">Expenses Incurred</p>
                                <h3 id="flowMonthExpense" style="color:#f43f5e; font-size:1.6rem; margin-top:0.3rem;">₹0.00</h3>
                            </div>
                        </div>

                        <div class="card-title">
                            <span>Income History</span>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Source / Title</th>
                                        <th>Category</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="incomeHistoryBody">
                                    <tr><td colspan="5" style="text-align:center; color:#94a3b8;">Loading income records...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================
             TAB 3: ALL TRANSACTIONS (CENTRALIZED LEDGER)
             ======================================================== -->
        <div id="transactions" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <span>Centralized Financial Ledger</span>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openTransactionModal()">+ New Transaction</button>
                        <button class="btn-secondary" onclick="openCsvModal()">📥 Import CSV</button>
                        <a href="api/transactions.php?action=export_csv" class="btn-secondary" style="text-decoration:none;">📤 Export CSV</a>
                    </div>
                </div>

                <!-- Comprehensive Filters & Search Bar -->
                <div class="filter-bar">
                    <input type="text" id="txSearchInput" class="form-control" placeholder="🔍 Search description, notes, categories...">
                    
                    <select id="txTypeFilter" class="form-control" onchange="loadTransactionsTable(1)">
                        <option value="all">All Types (Income / Expense / Transfer)</option>
                        <option value="income">Income Only</option>
                        <option value="expense">Expense Only</option>
                        <option value="transfer">Internal Transfers Only</option>
                    </select>

                    <select id="txCategoryFilter" class="form-control" onchange="loadTransactionsTable(1)">
                        <option value="0">All Categories</option>
                    </select>

                    <select id="txAccountFilter" class="form-control" onchange="loadTransactionsTable(1)">
                        <option value="0">All Accounts</option>
                    </select>

                    <select id="txSortBy" class="form-control" onchange="loadTransactionsTable(1)">
                        <option value="date_desc">Date: Newest First</option>
                        <option value="date_asc">Date: Oldest First</option>
                        <option value="amount_desc">Amount: Highest First</option>
                        <option value="amount_asc">Amount: Lowest First</option>
                    </select>
                </div>

                <!-- Transaction Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Title / Description</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Account</th>
                                <th>Amount</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="allTransactionsBody">
                            <tr><td colspan="8" style="text-align:center; color:#94a3b8;">Loading ledger...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="pagination-controls">
                    <span id="txPaginationInfo">Showing 0 of 0 transactions</span>
                    <div class="page-btn-group">
                        <button id="txPrevBtn" class="btn-secondary" onclick="changeTxPage(-1)">← Previous</button>
                        <span id="txCurrentPageBadge" style="padding:0.4rem 0.8rem; background:rgba(255,255,255,0.06); border-radius:6px; font-weight:700;">1</span>
                        <button id="txNextBtn" class="btn-secondary" onclick="changeTxPage(1)">Next →</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================
             TAB 4: BUDGETS & SAVINGS GOALS
             ======================================================== -->
        <div id="budgets_goals" class="tab-content">
            
            <!-- Category Budgets Section -->
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Monthly Category Budgets</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Set monthly limits and track spending progress. Alerts trigger at 80% and 100%.
                        </p>
                    </div>
                    <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openBudgetModal()">+ Set Category Budget</button>
                </div>

                <div id="budgetsContainer" class="budget-grid">
                    <div style="color:#94a3b8;">Loading budgets...</div>
                </div>
            </div>

            <!-- Savings Goals Section -->
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Target Savings Goals</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Plan milestones (vehicle, electronics, emergency fund) and track monthly required contributions.
                        </p>
                    </div>
                    <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openSavingsGoalModal()">+ Create Savings Goal</button>
                </div>

                <div id="savingsContainer" class="goals-grid">
                    <div style="color:#94a3b8;">Loading savings goals...</div>
                </div>
            </div>

        </div>

        <!-- ========================================================
             TAB 5: ACCOUNTS & WALLETS
             ======================================================== -->
        <div id="accounts" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Financial Accounts & Wallets</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Track balances across bank accounts, cash wallets, digital UPI wallets, and credit cards.
                        </p>
                    </div>
                    <div style="display:flex; gap:0.6rem;">
                        <button class="btn-action-green" onclick="openTransferModal()">🔄 Transfer Money Between Accounts</button>
                        <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openAccountModal()">+ Add New Account</button>
                    </div>
                </div>

                <div id="accountsContainer" class="accounts-grid">
                    <div style="color:#94a3b8;">Loading accounts...</div>
                </div>
            </div>
        </div>

        <!-- ========================================================
             TAB 6: RECURRING TRANSACTIONS & DEBT MANAGEMENT
             ======================================================== -->
        <div id="recurring_debts" class="tab-content">
            
            <!-- Recurring Schedules -->
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Scheduled & Recurring Transactions</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Automatically scheduled salaries, rent, utility bills, subscriptions, and memberships.
                        </p>
                    </div>
                    <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openRecurringModal()">+ Add Recurring Schedule</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Schedule Title</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Frequency</th>
                                <th>Amount</th>
                                <th>Next Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recurringBody">
                            <tr><td colspan="8" style="text-align:center; color:#94a3b8;">Loading recurring schedules...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Debts & Loans Management -->
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Loans, EMIs & Debt Payoff Tracker</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Track car loans, education loans, personal loans, and record monthly EMI payments.
                        </p>
                    </div>
                    <button class="btn-primary" style="width:auto; padding:0.5rem 1rem; font-size:0.85rem;" onclick="openLoanModal()">+ Add Loan / Debt</button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Loan / Debt</th>
                                <th>Lender</th>
                                <th>Principal</th>
                                <th>Current Balance</th>
                                <th>Interest</th>
                                <th>Monthly EMI</th>
                                <th>Payoff Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="debtsBody">
                            <tr><td colspan="8" style="text-align:center; color:#94a3b8;">Loading debts...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================
             TAB 7: INVESTMENT PORTFOLIO
             ======================================================== -->
        <div id="investments" class="tab-content">
            <div class="dashboard-grid">
                
                <!-- Left Column: Add Holding & Asset Class Distribution -->
                <div class="left-column">
                    <div class="card">
                        <h3 class="card-title">Add Investment Holding</h3>
                        <form id="investmentForm">
                            <div class="form-group">
                                <label for="invAssetName">Asset / Instrument Name</label>
                                <input type="text" id="invAssetName" class="form-control" placeholder="e.g. Nifty 50 Index Fund, TCS, SGB Gold..." required>
                            </div>
                            <div class="form-group">
                                <label for="invAssetType">Asset Class</label>
                                <select id="invAssetType" class="form-control">
                                    <option value="Mutual Funds">Mutual Funds (SIP / Lump sum)</option>
                                    <option value="Stocks">Stocks / Equity (BSE/NSE)</option>
                                    <option value="ETFs">ETFs</option>
                                    <option value="Fixed Deposits">Fixed Deposits (FD)</option>
                                    <option value="Gold">Gold / SGB</option>
                                    <option value="Cryptocurrency">Cryptocurrency</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Bonds">Bonds / Debt</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; gap:0.8rem;">
                                <div style="flex:1;">
                                    <label for="invAmountInvested">Amount Invested (<span class="curr-sym">₹</span>)</label>
                                    <input type="number" step="0.01" id="invAmountInvested" class="form-control" placeholder="10000.00" required>
                                </div>
                                <div style="flex:1;">
                                    <label for="invCurrentValue">Current Valuation (<span class="curr-sym">₹</span>)</label>
                                    <input type="number" step="0.01" id="invCurrentValue" class="form-control" placeholder="11500.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="invDividends">Dividends / Coupons Received</label>
                                <input type="number" step="0.01" id="invDividends" class="form-control" placeholder="0.00">
                            </div>
                            <button type="submit" class="btn-primary">📈 Add to Portfolio</button>
                        </form>
                    </div>

                    <div class="card">
                        <h3 class="card-title">Portfolio Asset Allocation</h3>
                        <div style="position: relative; height: 250px;">
                            <canvas id="portfolioChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Holdings Ledger & ROI -->
                <div class="right-column">
                    <div class="card">
                        <div class="card-title">
                            <span>Holdings & Returns Summary</span>
                            <div id="invSummaryBanner" style="font-size:0.9rem; font-weight:bold; color:#10b981;"></div>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Class</th>
                                        <th>Invested</th>
                                        <th>Current Val</th>
                                        <th>Profit / Loss (ROI)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="investmentBody">
                                    <tr><td colspan="6" style="text-align:center; color:#94a3b8;">Loading investments...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================
             TAB 8: REPORTS, ANALYTICS & FORECASTING
             ======================================================== -->
        <div id="reports" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <div>
                        <span>Financial Reports & Future Forecasting</span>
                        <p style="font-size:0.85rem; color:#94a3b8; font-weight:normal; margin-top:0.2rem;">
                            Analyze spending trends, view annual comparisons, and project wealth growth.
                        </p>
                    </div>
                    <button class="btn-secondary" onclick="window.print()">🖨️ Print / Download PDF Report</button>
                </div>

                <!-- Yearly Cash Flow Chart -->
                <div style="position: relative; height: 300px; margin-bottom: 2rem;">
                    <canvas id="yearlyChart"></canvas>
                </div>

                <!-- Financial Forecasting Cards -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.2rem; margin-bottom:2rem;">
                    <div style="background:rgba(99,102,241,0.12); border:1px solid rgba(99,102,241,0.3); border-radius:14px; padding:1.4rem;">
                        <p style="font-size:0.8rem; color:#a5b4fc; text-transform:uppercase; font-weight:700;">Predicted Next Month Income</p>
                        <h2 id="forecastNextIncome" style="font-size:1.6rem; color:#818cf8; margin-top:0.3rem;">₹0.00</h2>
                        <small style="color:#94a3b8;">Based on 3-month trailing average</small>
                    </div>
                    <div style="background:rgba(244,63,94,0.12); border:1px solid rgba(244,63,94,0.3); border-radius:14px; padding:1.4rem;">
                        <p style="font-size:0.8rem; color:#fda4af; text-transform:uppercase; font-weight:700;">Predicted Next Month Expenses</p>
                        <h2 id="forecastNextExpense" style="font-size:1.6rem; color:#f43f5e; margin-top:0.3rem;">₹0.00</h2>
                        <small style="color:#94a3b8;">Includes recurring commitments</small>
                    </div>
                    <div style="background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); border-radius:14px; padding:1.4rem;">
                        <p style="font-size:0.8rem; color:#6ee7b7; text-transform:uppercase; font-weight:700;">6-Month Savings Projection</p>
                        <h2 id="forecast6MonthSavings" style="font-size:1.6rem; color:#10b981; margin-top:0.3rem;">₹0.00</h2>
                        <small style="color:#94a3b8;">Estimated accumulated capital</small>
                    </div>
                </div>

                <!-- Daily Spending Trend -->
                <div class="card-title">Daily Spending Trend This Month</div>
                <div style="position: relative; height: 240px; margin-bottom: 2rem;">
                    <canvas id="dailySpendChart"></canvas>
                </div>

                <!-- Category Breakdown Table -->
                <div class="card-title">Category Spending Details (Percentages)</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Transactions Count</th>
                                <th>Total Spent</th>
                                <th>% of Total Expenses</th>
                            </tr>
                        </thead>
                        <tbody id="reportCategoryBody">
                            <tr><td colspan="4" style="text-align:center; color:#94a3b8;">Loading category reports...</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- ========================================================
             TAB 9: AI FINANCIAL ASSISTANT
             ======================================================== -->
        <div id="ai_assistant" class="tab-content">
            <div class="card ai-chat-card">
                <div class="card-title">
                    <span>🤖 AI Personal Finance Intelligence Assistant</span>
                    <span class="badge" style="background:rgba(99,102,241,0.2); color:#a5b4fc;">Live Financial Context</span>
                </div>

                <!-- Chat Messages History -->
                <div id="aiChatMessages" class="ai-chat-messages">
                    <div class="chat-bubble ai">
                        👋 Hello! I am your AI Financial Assistant. I can analyze your income, expenses, budgets, savings goals, and affordability in real-time.<br><br>
                        Click any prompt below or type your own question!
                    </div>
                </div>

                <!-- Quick Prompt Suggestions -->
                <div class="ai-suggestions-pills">
                    <span class="suggestion-pill" onclick="sendAiPrompt('Where did I spend the most money this month?')">📍 Where did I spend the most?</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('How much did I spend on food in the last 30 days?')">🍔 How much on food in 30 days?</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('Can I afford a ₹50,000 laptop?')">💻 Can I afford a ₹50,000 laptop?</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('How can I save ₹10,000 every month?')">💰 How to save ₹10,000/month?</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('Which category has increased the most?')">📈 Which category increased most?</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('How much money will I save by the end of the year?')">🗓️ Year-end savings forecast</span>
                    <span class="suggestion-pill" onclick="sendAiPrompt('Give me a monthly financial summary.')">📊 Monthly financial summary</span>
                </div>

                <!-- User Query Input -->
                <form id="aiChatForm" class="ai-input-row" onsubmit="handleAiChatSubmit(event)">
                    <input type="text" id="aiQueryInput" class="form-control" placeholder="Ask your financial assistant anything (e.g. 'Can I afford a ₹35,000 trip?')..." required>
                    <button type="submit" class="btn-primary" style="width:auto; padding:0 1.5rem;">Send 🚀</button>
                </form>
            </div>
        </div>

        <!-- ========================================================
             TAB 10: USER PROFILE & SETTINGS
             ======================================================== -->
        <div id="profile" class="tab-content">
            <div class="card" style="max-width: 620px; margin: 0 auto;">
                <h3 class="card-title">User Profile & Currency Settings</h3>
                <form id="profileForm">
                    <div class="form-group">
                        <label for="prof_name">Full Name</label>
                        <input type="text" id="prof_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prof_email">Email Address</label>
                        <input type="email" id="prof_email" class="form-control" readonly style="opacity:0.6; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label for="prof_currency">Preferred Currency Symbol</label>
                        <select id="prof_currency" class="form-control">
                            <option value="₹">Indian Rupee (₹)</option>
                            <option value="$">US Dollar ($)</option>
                            <option value="€">Euro (€)</option>
                            <option value="£">British Pound (£)</option>
                            <option value="A$">Australian Dollar (A$)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prof_question">Security Question</label>
                        <select id="prof_question" class="form-control">
                            <option value="What is your favorite city?">What is your favorite city?</option>
                            <option value="What was your first school name?">What was your first school name?</option>
                            <option value="What is your pet's name?">What is your pet's name?</option>
                            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prof_answer">Security Answer</label>
                        <input type="text" id="prof_answer" class="form-control" placeholder="Leave blank to keep unchanged">
                    </div>

                    <hr style="border:none; border-top:1px solid var(--card-border); margin:1.5rem 0;">
                    
                    <h4 style="font-size:0.95rem; margin-bottom:1rem; color:#f1f5f9;">Change Password</h4>
                    <div class="form-group">
                        <label for="prof_curr_password">Current Password (Required for change)</label>
                        <input type="password" id="prof_curr_password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label for="prof_new_password">New Password</label>
                        <input type="password" id="prof_new_password" class="form-control" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn-primary">💾 Save Profile Preferences</button>
                </form>
            </div>
        </div>

    </div>


    <!-- ========================================================
         MODALS
         ======================================================== -->

    <!-- Modal 1: Add / Edit Transaction -->
    <div id="transactionModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="txModalTitle">Add Transaction</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('transactionModal')">&times;</button>
            </div>
            <form id="genericTxForm">
                <input type="hidden" id="modalTxId">
                <div class="form-group">
                    <label for="modalTxType">Transaction Type</label>
                    <select id="modalTxType" class="form-control" onchange="loadCategoriesForType(this.value)">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modalTxTitle">Description / Title</label>
                    <input type="text" id="modalTxTitle" class="form-control" placeholder="e.g. Swiggy, Uber, Salary..." oninput="autoSuggestCategory(this.value, 'modalTxCategory')" required>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="modalTxAmount">Amount (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="modalTxAmount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div style="flex:1;">
                        <label for="modalTxDate">Date</label>
                        <input type="date" id="modalTxDate" class="form-control" required>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="modalTxCategory">Category</label>
                        <select id="modalTxCategory" class="form-control" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label for="modalTxAccount">Account</label>
                        <select id="modalTxAccount" class="form-control" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="modalTxMethod">Payment Method</label>
                        <select id="modalTxMethod" class="form-control">
                            <option value="UPI">UPI</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label for="modalTxNotes">Notes</label>
                        <input type="text" id="modalTxNotes" class="form-control" placeholder="Optional notes">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">💾 Save Transaction</button>
            </form>
        </div>
    </div>

    <!-- Modal 2: Transfer Money Between Accounts -->
    <div id="transferModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Transfer Funds (Internal Transfer)</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('transferModal')">&times;</button>
            </div>
            <form id="transferForm">
                <div class="form-group">
                    <label for="transferFrom">From Account</label>
                    <select id="transferFrom" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label for="transferTo">To Account</label>
                    <select id="transferTo" class="form-control" required></select>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="transferAmount">Amount (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="transferAmount" class="form-control" placeholder="1000.00" required>
                    </div>
                    <div style="flex:1;">
                        <label for="transferDate">Transfer Date</label>
                        <input type="date" id="transferDate" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="transferNotes">Notes</label>
                    <input type="text" id="transferNotes" class="form-control" placeholder="e.g. Wallet top up">
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">🔄 Confirm Transfer</button>
            </form>
        </div>
    </div>

    <!-- Modal 3: Deposit to Savings Goal -->
    <div id="depositModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Deposit to <span id="depositGoalTitle" style="color:#06b6d4;">Goal</span></h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('depositModal')">&times;</button>
            </div>
            <form id="depositForm">
                <input type="hidden" id="depositGoalId">
                <div class="form-group">
                    <label for="depositAmount">Deposit Amount (<span class="curr-sym">₹</span>)</label>
                    <input type="number" step="0.01" id="depositAmount" class="form-control" placeholder="1000.00" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">🎯 Add Deposit</button>
            </form>
        </div>
    </div>

    <!-- Modal 4: Pay EMI -->
    <div id="emiModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Record EMI Payment: <span id="emiLoanTitle" style="color:#f43f5e;">Loan</span></h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('emiModal')">&times;</button>
            </div>
            <form id="emiForm">
                <input type="hidden" id="emiLoanId">
                <div class="form-group">
                    <label for="emiAmount">EMI Amount (<span class="curr-sym">₹</span>)</label>
                    <input type="number" step="0.01" id="emiAmount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="emiAccount">Deduct from Account</label>
                    <select id="emiAccount" class="form-control"></select>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">💳 Confirm Payment</button>
            </form>
        </div>
    </div>

    <!-- Modal 5: Import CSV -->
    <div id="csvModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Import Transactions from CSV</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('csvModal')">&times;</button>
            </div>
            <form id="csvForm" enctype="multipart/form-data">
                <p style="font-size:0.85rem; color:#94a3b8; margin-bottom:1rem;">
                    Upload a CSV file containing transactions. Columns expected: <code>Date, Title, Amount, [Type], [Category], [Payment Method], [Notes]</code>.<br>
                    ✨ Auto-categorization will detect Zomato, Uber, Amazon, Netflix, Bills, etc.!
                </p>
                <div class="form-group">
                    <input type="file" id="csvFileInput" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">📥 Start CSV Import</button>
            </form>
        </div>
    </div>

    <!-- Modal 6: Add Account -->
    <div id="accountModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Financial Account / Wallet</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('accountModal')">&times;</button>
            </div>
            <form id="accountForm">
                <div class="form-group">
                    <label for="accName">Account Name</label>
                    <input type="text" id="accName" class="form-control" placeholder="e.g. Axis Bank, Paytm, Cash Wallet" required>
                </div>
                <div class="form-group">
                    <label for="accType">Account Type</label>
                    <select id="accType" class="form-control">
                        <option value="Bank Account">Bank Account</option>
                        <option value="Cash">Cash Wallet</option>
                        <option value="Digital Wallet">Digital Wallet (UPI/Paytm)</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="accOpening">Opening Balance (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="accOpening" class="form-control" placeholder="0.00">
                    </div>
                    <div style="flex:1;">
                        <label for="accNumber">Account / Ref Number</label>
                        <input type="text" id="accNumber" class="form-control" placeholder="e.g. XX-4321">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">💾 Save Account</button>
            </form>
        </div>
    </div>

    <!-- Modal 7: Add Budget Limit -->
    <div id="budgetModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Set Monthly Category Budget</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('budgetModal')">&times;</button>
            </div>
            <form id="budgetForm">
                <div class="form-group">
                    <label for="budgetCategory">Expense Category</label>
                    <select id="budgetCategory" class="form-control" required></select>
                </div>
                <div class="form-group">
                    <label for="budgetLimit">Monthly Limit (<span class="curr-sym">₹</span>)</label>
                    <input type="number" step="0.01" id="budgetLimit" class="form-control" placeholder="5000.00" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">🎯 Save Budget Limit</button>
            </form>
        </div>
    </div>

    <!-- Modal 8: Add Savings Goal -->
    <div id="savingsGoalModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Create Target Savings Goal</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('savingsGoalModal')">&times;</button>
            </div>
            <form id="savingsGoalForm">
                <div class="form-group">
                    <label for="goalTitle">Goal Title</label>
                    <input type="text" id="goalTitle" class="form-control" placeholder="e.g. Buy Motorcycle, Emergency Reserve" required>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="goalTarget">Target Amount (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="goalTarget" class="form-control" placeholder="100000.00" required>
                    </div>
                    <div style="flex:1;">
                        <label for="goalCurrent">Initial Deposit</label>
                        <input type="number" step="0.01" id="goalCurrent" class="form-control" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="goalDate">Target Completion Date</label>
                        <input type="date" id="goalDate" class="form-control">
                    </div>
                    <div style="flex:1;">
                        <label for="goalColor">Color Accent</label>
                        <select id="goalColor" class="form-control">
                            <option value="#10b981">Emerald Green</option>
                            <option value="#06b6d4">Cyan Blue</option>
                            <option value="#f59e0b">Amber Gold</option>
                            <option value="#8b5cf6">Purple</option>
                            <option value="#ec4899">Pink</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">🎯 Create Savings Goal</button>
            </form>
        </div>
    </div>

    <!-- Modal 9: Add Recurring Transaction -->
    <div id="recurringModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Scheduled / Recurring Transaction</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('recurringModal')">&times;</button>
            </div>
            <form id="recurringForm">
                <div class="form-group">
                    <label for="recTitle">Schedule Title</label>
                    <input type="text" id="recTitle" class="form-control" placeholder="e.g. Monthly Salary, House Rent, Netflix..." required>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="recType">Type</label>
                        <select id="recType" class="form-control">
                            <option value="expense">Expense</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label for="recFrequency">Frequency</label>
                        <select id="recFrequency" class="form-control">
                            <option value="monthly" selected>Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="recAmount">Amount (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="recAmount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div style="flex:1;">
                        <label for="recNextDue">Next Due Date</label>
                        <input type="date" id="recNextDue" class="form-control" required>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="recCategory">Category</label>
                        <select id="recCategory" class="form-control" required></select>
                    </div>
                    <div style="flex:1;">
                        <label for="recAccount">Account</label>
                        <select id="recAccount" class="form-control"></select>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">🔄 Schedule Transaction</button>
            </form>
        </div>
    </div>

    <!-- Modal 10: Add Loan / Debt -->
    <div id="loanModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Loan / Debt Record</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('loanModal')">&times;</button>
            </div>
            <form id="loanForm">
                <div class="form-group">
                    <label for="loanName">Loan Description</label>
                    <input type="text" id="loanName" class="form-control" placeholder="e.g. Car Loan, Education Loan, Credit Card Balance" required>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="loanLender">Lender / Bank</label>
                        <input type="text" id="loanLender" class="form-control" placeholder="e.g. HDFC, SBI" required>
                    </div>
                    <div style="flex:1;">
                        <label for="loanType">Loan Type</label>
                        <select id="loanType" class="form-control">
                            <option value="Personal Loan">Personal Loan</option>
                            <option value="Car Loan">Car Loan</option>
                            <option value="Home Loan">Home Loan</option>
                            <option value="Education Loan">Education Loan</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="loanPrincipal">Principal Amount (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="loanPrincipal" class="form-control" placeholder="100000.00" required>
                    </div>
                    <div style="flex:1;">
                        <label for="loanBalance">Current Balance</label>
                        <input type="number" step="0.01" id="loanBalance" class="form-control" placeholder="100000.00" required>
                    </div>
                </div>
                <div class="form-group" style="display:flex; gap:0.8rem;">
                    <div style="flex:1;">
                        <label for="loanRate">Interest Rate (% p.a.)</label>
                        <input type="number" step="0.01" id="loanRate" class="form-control" placeholder="8.50" value="8.50">
                    </div>
                    <div style="flex:1;">
                        <label for="loanEmi">Monthly EMI (<span class="curr-sym">₹</span>)</label>
                        <input type="number" step="0.01" id="loanEmi" class="form-control" placeholder="5000.00" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:0.8rem;">💳 Save Loan Record</button>
            </form>
        </div>
    </div>


    <!-- Toast Notification Element -->
    <div id="toast" class="toast">Action completed</div>

    <!-- App JavaScript -->
    <script src="js/app.js"></script>
</body>
</html>
