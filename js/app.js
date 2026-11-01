// ========================================================
// Financial Hub - Full-Featured Client Application
// ========================================================

let currentUser = null;
let currentCurrency = '₹';
let allCategories = [];
let allAccounts = [];
let currentTxPage = 1;

// Chart Instances
let expenseChartInst = null;
let trendChartInst = null;
let portfolioChartInst = null;
let yearlyChartInst = null;
let dailyChartInst = null;

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    // Set default dates to today
    const todayStr = new Date().toISOString().split('T')[0];
    ['expDate', 'incDate', 'modalTxDate', 'transferDate', 'recNextDue'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = todayStr;
    });

    // Check Auth Session
    checkAuthSession();

    // Setup Event Listeners
    setupEventListeners();
});

// ========================================================
// 1. AUTHENTICATION & SESSION MANAGEMENT
// ========================================================
async function checkAuthSession() {
    try {
        const res = await fetch('api/auth.php?action=me');
        const data = await res.json();

        if (data.status === 'success' && data.authenticated && data.user) {
            currentUser = data.user;
            currentCurrency = currentUser.currency || '₹';
            showAppInterface();
        } else {
            showAuthInterface();
        }
    } catch (err) {
        showAuthInterface();
    }
}

function showAuthInterface() {
    document.getElementById('authSection').style.display = 'flex';
    document.getElementById('appSection').style.display = 'none';
}

function showAppInterface() {
    document.getElementById('authSection').style.display = 'none';
    document.getElementById('appSection').style.display = 'block';

    updateUserProfileUI();
    loadCategoriesAndAccounts().then(() => {
        loadDashboardData();
        loadNotifications();
    });
}

function updateUserProfileUI() {
    if (!currentUser) return;
    document.getElementById('userNameDisplay').textContent = currentUser.name;
    
    // Calculate 2-letter Initials
    const initials = currentUser.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() || 'U';
    document.getElementById('userAvatar').textContent = initials;

    // Update all currency symbols
    document.querySelectorAll('.curr-sym').forEach(el => el.textContent = currentCurrency);

    // Profile Settings Form
    document.getElementById('prof_name').value = currentUser.name || '';
    document.getElementById('prof_email').value = currentUser.email || '';
    document.getElementById('prof_currency').value = currentCurrency;
    if (currentUser.security_question) {
        document.getElementById('prof_question').value = currentUser.security_question;
    }
}

function switchAuthTab(tabId) {
    document.querySelectorAll('.auth-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-auth-tab') === tabId);
    });
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.toggle('active', form.id === tabId);
    });
}

// Quick Demo Login
async function quickDemoLogin() {
    document.getElementById('loginEmail').value = 'rehan@example.com';
    document.getElementById('loginPassword').value = 'demo123';
    document.getElementById('loginForm').dispatchEvent(new Event('submit'));
}

// Handle Login Submission
async function handleLoginSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    try {
        const res = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', email, password })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            currentUser = data.user;
            currentCurrency = currentUser.currency || '₹';
            showAppInterface();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Login failed. Please check server.', 'error');
    }
}

// Handle Register Submission
async function handleRegisterSubmit(e) {
    e.preventDefault();
    const name = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const currency = document.getElementById('regCurrency').value;
    const security_question = document.getElementById('regQuestion').value;
    const security_answer = document.getElementById('regAnswer').value.trim();

    try {
        const res = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', name, email, password, currency, security_question, security_answer })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            currentUser = data.user;
            currentCurrency = currentUser.currency || '₹';
            showAppInterface();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Registration failed.', 'error');
    }
}

// Handle Forgot Password - Step 1: Verify Email
async function fetchSecurityQuestion() {
    const email = document.getElementById('forgotEmail').value.trim();
    if (!email) return showToast('Please enter your email first.', 'error');

    try {
        const res = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_security_question', email })
        });
        const data = await res.json();

        if (data.status === 'success') {
            document.getElementById('displaySecQuestion').textContent = data.question;
            document.getElementById('forgotStep2').style.display = 'block';
            showToast('Security question loaded.');
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Failed to verify email.', 'error');
    }
}

// Handle Forgot Password - Step 2: Reset
async function handleForgotSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('forgotEmail').value.trim();
    const security_answer = document.getElementById('forgotAnswer').value.trim();
    const new_password = document.getElementById('forgotNewPassword').value;

    try {
        const res = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_password', email, security_answer, new_password })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            switchAuthTab('loginForm');
            document.getElementById('forgotForm').reset();
            document.getElementById('forgotStep2').style.display = 'none';
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Password reset failed.', 'error');
    }
}

// Handle Logout
async function handleLogout() {
    if (!confirm('Are you sure you want to log out?')) return;
    try {
        await fetch('api/auth.php?action=logout');
        currentUser = null;
        showToast('Logged out successfully.');
        showAuthInterface();
    } catch (err) {
        showAuthInterface();
    }
}

// ========================================================
// 2. NAVIGATION & TABS SETUP
// ========================================================
function setupEventListeners() {
    // Auth Tab buttons
    document.querySelectorAll('.auth-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => switchAuthTab(btn.getAttribute('data-auth-tab')));
    });

    // Auth Forms
    document.getElementById('loginForm').addEventListener('submit', handleLoginSubmit);
    document.getElementById('registerForm').addEventListener('submit', handleRegisterSubmit);
    document.getElementById('forgotForm').addEventListener('submit', handleForgotSubmit);

    // Main Navigation Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => switchMainTab(btn.getAttribute('data-tab')));
    });

    // Forms
    document.getElementById('quickExpenseForm').addEventListener('submit', handleQuickExpense);
    document.getElementById('quickIncomeForm').addEventListener('submit', handleQuickIncome);
    document.getElementById('genericTxForm').addEventListener('submit', handleGenericTxSubmit);
    document.getElementById('transferForm').addEventListener('submit', handleTransferSubmit);
    document.getElementById('depositForm').addEventListener('submit', handleDepositSubmit);
    document.getElementById('emiForm').addEventListener('submit', handleEmiSubmit);
    document.getElementById('csvForm').addEventListener('submit', handleCsvImportSubmit);
    document.getElementById('accountForm').addEventListener('submit', handleAccountSubmit);
    document.getElementById('budgetForm').addEventListener('submit', handleBudgetSubmit);
    document.getElementById('savingsGoalForm').addEventListener('submit', handleSavingsGoalSubmit);
    document.getElementById('recurringForm').addEventListener('submit', handleRecurringSubmit);
    document.getElementById('loanForm').addEventListener('submit', handleLoanSubmit);
    document.getElementById('investmentForm').addEventListener('submit', handleInvestmentSubmit);
    document.getElementById('profileForm').addEventListener('submit', handleProfileSubmit);

    // Search input with debounce
    let searchDebounce = null;
    document.getElementById('txSearchInput').addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => loadTransactionsTable(1), 300);
    });
}

function switchMainTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });
    document.querySelectorAll('.tab-content').forEach(c => {
        c.classList.toggle('active', c.id === tabId);
    });

    // Close notifications drawer if open
    const drawer = document.getElementById('notificationsDrawer');
    if (drawer) drawer.classList.remove('active');

    // Trigger tab-specific data load
    if (tabId === 'dashboard') loadDashboardData();
    if (tabId === 'income_expense') loadIncomeExpenseTab();
    if (tabId === 'transactions') loadTransactionsTable(1);
    if (tabId === 'budgets_goals') { loadBudgets(); loadSavingsGoals(); }
    if (tabId === 'accounts') loadAccounts();
    if (tabId === 'recurring_debts') { loadRecurring(); loadDebts(); }
    if (tabId === 'investments') loadInvestments();
    if (tabId === 'reports') loadReports();
}

// ========================================================
// 3. MASTER DATA: CATEGORIES & ACCOUNTS
// ========================================================
async function loadCategoriesAndAccounts() {
    try {
        const [catRes, accRes] = await Promise.all([
            fetch('api/get_categories.php'),
            fetch('api/accounts.php?action=list')
        ]);
        const catData = await catRes.json();
        const accData = await accRes.json();

        if (catData.status === 'success') {
            allCategories = catData.categories;
            populateCategoryDropdowns();
        }

        if (accData.status === 'success') {
            allAccounts = accData.accounts;
            populateAccountDropdowns();
        }
    } catch (err) {
        console.error('Master data load error:', err);
    }
}

function populateCategoryDropdowns() {
    const expenseCats = allCategories.filter(c => c.type === 'expense');
    const incomeCats = allCategories.filter(c => c.type === 'income');

    // Quick Expense
    populateSelect('expCategory', expenseCats, 'Select Category');
    // Quick Income
    populateSelect('incCategory', incomeCats, 'Select Category');
    // Modal Generic
    populateSelect('modalTxCategory', expenseCats, 'Select Category');
    // Budget Modal
    populateSelect('budgetCategory', expenseCats, 'Select Category');
    // Recurring Modal
    populateSelect('recCategory', allCategories, 'Select Category');
    // Transaction Filter
    populateSelect('txCategoryFilter', allCategories, 'All Categories', 0);
}

function populateAccountDropdowns() {
    populateSelect('expAccount', allAccounts, 'Select Account', null, 'account_name');
    populateSelect('incAccount', allAccounts, 'Select Account', null, 'account_name');
    populateSelect('modalTxAccount', allAccounts, 'Select Account', null, 'account_name');
    populateSelect('transferFrom', allAccounts, 'Select Source Account', null, 'account_name');
    populateSelect('transferTo', allAccounts, 'Select Destination Account', null, 'account_name');
    populateSelect('emiAccount', allAccounts, 'Deduct from Account', null, 'account_name');
    populateSelect('recAccount', allAccounts, 'Default Account', null, 'account_name');
    populateSelect('txAccountFilter', allAccounts, 'All Accounts', 0, 'account_name');
}

function populateSelect(selectId, items, placeholder, defaultVal = '', labelKey = 'name') {
    const el = document.getElementById(selectId);
    if (!el) return;
    el.innerHTML = `<option value="${defaultVal}">${placeholder}</option>`;
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item[labelKey];
        el.appendChild(opt);
    });
}

function loadCategoriesForType(type) {
    const cats = allCategories.filter(c => c.type === type);
    populateSelect('modalTxCategory', cats, 'Select Category');
}

// ========================================================
// 4. AUTOMATIC TRANSACTION CATEGORIZATION (SMART NLP)
// ========================================================
function autoSuggestCategory(text, selectId) {
    if (!text || text.length < 3) return;
    const str = text.toLowerCase();
    let catId = null;

    if (/zomato|swiggy|restaurant|mcdonald|burger|pizza|starbucks|domino|grocery|supermarket|bigbasket|blinkit|zepto|food|dining|cafe|lunch|dinner|dosa|biryani|tea|coffee/i.test(str)) {
        catId = 8; // Food & Dining
    } else if (/uber|ola|rapido|metro|irctc|train|petrol|diesel|fuel|auto|flight|bus|transport|cab/i.test(str)) {
        catId = 9; // Transportation
    } else if (/amazon|flipkart|myntra|ajio|zara|clothes|shoes|electronics|shopping|mall/i.test(str)) {
        catId = 10; // Shopping
    } else if (/electricity|bescom|water|gas|lpg|wifi|broadband|airtel|jio|vi|recharge|mobile|bill|utility/i.test(str)) {
        catId = 11; // Bills & Utilities
    } else if (/netflix|spotify|hotstar|prime|movie|pvr|inox|bookmyshow|gaming|youtube|entertainment/i.test(str)) {
        catId = 12; // Entertainment
    } else if (/apollo|pharmacy|medplus|doctor|hospital|medicine|clinic|health/i.test(str)) {
        catId = 13; // Healthcare
    } else if (/udemy|coursera|books|tuition|college|school|course|fee/i.test(str)) {
        catId = 14; // Education
    } else if (/rent|housing|landlord|maintenance/i.test(str)) {
        catId = 15; // Housing & Rent
    }

    if (catId) {
        const selectEl = document.getElementById(selectId);
        if (selectEl) {
            selectEl.value = catId;
        }
    }
}

// ========================================================
// 5. DASHBOARD MODULE
// ========================================================
async function loadDashboardData() {
    try {
        const res = await fetch('api/dashboard.php');
        const data = await res.json();

        if (data.status === 'success') {
            if (data.user) {
                currentUser = data.user;
                currentCurrency = currentUser.currency || '₹';
                updateUserProfileUI();
            }

            renderDashboardSummary(data.summary);
            renderFinancialHealth(data.financial_health);
            renderExpenseDoughnut(data.category_breakdown);
            renderTrendChart(data.history_chart);
            renderRecentTransactions(data.recent_transactions);

            // AI Insights snippet
            const insightsBox = document.getElementById('dashboardAiInsights');
            if (data.ai_insights && data.ai_insights.length > 0) {
                insightsBox.innerHTML = data.ai_insights.map(i => `<p style="margin-bottom:0.5rem;">• ${i}</p>`).join('');
            } else {
                insightsBox.innerHTML = 'Add more transactions to generate personalized AI financial insights.';
            }

            // Unread notifications
            updateNotifBadge(data.unread_notifications || 0);
        }
    } catch (err) {
        console.error('Error loading dashboard:', err);
    }
}

function renderDashboardSummary(s) {
    document.getElementById('totalBalance').textContent = `${currentCurrency}${formatNumber(s.total_balance)}`;
    document.getElementById('monthIncome').textContent = `${currentCurrency}${formatNumber(s.month_income)}`;
    document.getElementById('monthExpense').textContent = `${currentCurrency}${formatNumber(s.month_expense)}`;
    document.getElementById('totalSavingsSummary').textContent = `${currentCurrency}${formatNumber(s.total_savings)}`;
    document.getElementById('totalInvestmentsSummary').textContent = `${currentCurrency}${formatNumber(s.total_investments)}`;
    document.getElementById('totalDebtSummary').textContent = `${currentCurrency}${formatNumber(s.total_debt)}`;

    document.getElementById('totalIncomeSub').textContent = `All-time: ${currentCurrency}${formatNumber(s.total_income)}`;
    document.getElementById('dailyAvgSpendSub').textContent = `Daily avg: ${currentCurrency}${formatNumber(s.daily_avg_spend)}`;
}

function renderFinancialHealth(fh) {
    if (!fh) return;
    document.getElementById('healthScoreValue').textContent = fh.score;
    document.getElementById('healthScoreGrade').textContent = fh.grade;
    document.getElementById('healthScoreGrade').style.background = `${fh.badge_color}22`;
    document.getElementById('healthScoreGrade').style.color = fh.badge_color;
    document.getElementById('healthScoreSummary').textContent = fh.summary;

    document.getElementById('healthSavingsRate').textContent = `${fh.savings_rate}%`;
    document.getElementById('healthBudgetScore').textContent = `${fh.budget_adherence}%`;
    document.getElementById('healthDebtScore').textContent = `${fh.debt_management}%`;
    document.getElementById('healthEmergencyCover').textContent = `${fh.emergency_fund_months} mos`;

    // Dynamic Conic Gradient for health meter
    const circle = document.getElementById('healthScoreCircle');
    if (circle) {
        circle.style.background = `conic-gradient(${fh.badge_color} ${fh.score}%, rgba(255,255,255,0.08) 0)`;
        circle.style.boxShadow = `0 0 20px ${fh.badge_color}44`;
    }
}

function renderExpenseDoughnut(breakdown) {
    const ctx = document.getElementById('expenseChart').getContext('2d');
    const labels = breakdown.map(item => item.name);
    const amounts = breakdown.map(item => parseFloat(item.total));
    const colors = breakdown.map(item => item.color || '#6366f1');

    if (expenseChartInst) expenseChartInst.destroy();

    if (breakdown.length === 0) {
        ctx.font = '14px Inter'; ctx.fillStyle = '#94a3b8'; ctx.textAlign = 'center';
        ctx.fillText('No expenses recorded this month', ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }

    expenseChartInst = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data: amounts, backgroundColor: colors, borderWidth: 2, borderColor: '#141b2d' }] },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Inter', size: 11 } } } 
            } 
        }
    });
}

function renderTrendChart(history) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    const labels = history.map(h => h.label);
    const incomes = history.map(h => h.income);
    const expenses = history.map(h => h.expense);

    if (trendChartInst) trendChartInst.destroy();

    trendChartInst = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Income', data: incomes, backgroundColor: 'rgba(16, 185, 129, 0.85)', borderRadius: 6 },
                { label: 'Expense', data: expenses, backgroundColor: 'rgba(244, 63, 94, 0.85)', borderRadius: 6 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { color: '#94a3b8' } } },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

function renderRecentTransactions(txs) {
    const tbody = document.getElementById('recentTransactionsBody');
    tbody.innerHTML = '';

    if (!txs || txs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#94a3b8;">No recent transactions.</td></tr>`;
        return;
    }

    txs.forEach(t => {
        const isInc = t.type === 'income';
        const isTransfer = t.type === 'transfer';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="color:#94a3b8;">${t.transaction_date}</td>
            <td><strong>${escapeHtml(t.title)}</strong></td>
            <td><span class="badge" style="background:${t.category_color}22; color:${t.category_color}; border:1px solid ${t.category_color}44;">${escapeHtml(t.category_name || 'General')}</span></td>
            <td class="${isInc ? 'amount-income' : isTransfer ? 'amount-transfer' : 'amount-expense'}">
                ${isInc ? '+' : isTransfer ? '↔' : '-'}${currentCurrency}${formatNumber(t.amount)}
            </td>
            <td style="color:#94a3b8;">${escapeHtml(t.account_name || '-')}</td>
        `;
        tbody.appendChild(tr);
    });
}

// ========================================================
// 6. INCOME & EXPENSE MANAGEMENT TAB
// ========================================================
async function loadIncomeExpenseTab() {
    try {
        const res = await fetch('api/transactions.php?action=list&type=income&limit=10');
        const data = await res.json();

        // Update monthly flow cards
        const dashRes = await fetch('api/dashboard.php');
        const dashData = await dashRes.json();
        if (dashData.status === 'success') {
            document.getElementById('flowMonthIncome').textContent = `${currentCurrency}${formatNumber(dashData.summary.month_income)}`;
            document.getElementById('flowMonthExpense').textContent = `${currentCurrency}${formatNumber(dashData.summary.month_expense)}`;
        }

        // Render income history table
        const tbody = document.getElementById('incomeHistoryBody');
        tbody.innerHTML = '';
        if (data.status === 'success' && data.transactions.length > 0) {
            data.transactions.forEach(tx => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="color:#94a3b8;">${tx.transaction_date}</td>
                    <td><strong>${escapeHtml(tx.title)}</strong></td>
                    <td><span class="badge" style="background:${tx.category_color}22; color:${tx.category_color};">${escapeHtml(tx.category_name)}</span></td>
                    <td style="color:#94a3b8;">${escapeHtml(tx.account_name || '-')}</td>
                    <td class="amount-income">+${currentCurrency}${formatNumber(tx.amount)}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#94a3b8;">No income recorded yet.</td></tr>`;
        }
    } catch (err) {
        console.error('Error loading income/expense tab:', err);
    }
}

async function handleQuickExpense(e) {
    e.preventDefault();
    const title = document.getElementById('expTitle').value.trim();
    const amount = document.getElementById('expAmount').value;
    const transaction_date = document.getElementById('expDate').value;
    const category_id = document.getElementById('expCategory').value;
    const account_id = document.getElementById('expAccount').value;
    const payment_method = document.getElementById('expMethod').value;
    const notes = document.getElementById('expNotes').value.trim();

    try {
        const res = await fetch('api/transactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', type: 'expense', title, amount, transaction_date, category_id, account_id, payment_method, notes })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            document.getElementById('quickExpenseForm').reset();
            document.getElementById('expDate').value = new Date().toISOString().split('T')[0];
            loadDashboardData();
            loadIncomeExpenseTab();
            loadNotifications();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to record expense', 'error'); }
}

async function handleQuickIncome(e) {
    e.preventDefault();
    const title = document.getElementById('incTitle').value.trim();
    const amount = document.getElementById('incAmount').value;
    const transaction_date = document.getElementById('incDate').value;
    const category_id = document.getElementById('incCategory').value;
    const account_id = document.getElementById('incAccount').value;
    const payment_source = document.getElementById('incSource').value.trim();
    const notes = document.getElementById('incNotes').value.trim();

    try {
        const res = await fetch('api/transactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', type: 'income', title, amount, transaction_date, category_id, account_id, payment_source, notes })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            document.getElementById('quickIncomeForm').reset();
            document.getElementById('incDate').value = new Date().toISOString().split('T')[0];
            loadDashboardData();
            loadIncomeExpenseTab();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to record income', 'error'); }
}

// ========================================================
// 7. CENTRALIZED TRANSACTIONS LEDGER (FILTER / SEARCH / PAGINATE)
// ========================================================
async function loadTransactionsTable(page = 1) {
    currentTxPage = page;
    const search = document.getElementById('txSearchInput').value.trim();
    const type = document.getElementById('txTypeFilter').value;
    const category_id = document.getElementById('txCategoryFilter').value;
    const account_id = document.getElementById('txAccountFilter').value;
    const sortVal = document.getElementById('txSortBy').value;

    let sort_by = 'date';
    let sort_order = 'DESC';
    if (sortVal === 'date_asc') { sort_by = 'date'; sort_order = 'ASC'; }
    if (sortVal === 'amount_desc') { sort_by = 'amount'; sort_order = 'DESC'; }
    if (sortVal === 'amount_asc') { sort_by = 'amount'; sort_order = 'ASC'; }

    const queryParams = new URLSearchParams({
        action: 'list',
        page: currentTxPage,
        limit: 10,
        search,
        type,
        category_id,
        account_id,
        sort_by,
        sort_order
    });

    try {
        const res = await fetch(`api/transactions.php?${queryParams.toString()}`);
        const data = await res.json();
        const tbody = document.getElementById('allTransactionsBody');
        tbody.innerHTML = '';

        if (data.status === 'success' && data.transactions.length > 0) {
            data.transactions.forEach(t => {
                const isInc = t.type === 'income';
                const isTransfer = t.type === 'transfer';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="color:#94a3b8; font-size:0.85rem;">${t.transaction_date}</td>
                    <td><strong>${escapeHtml(t.title)}</strong></td>
                    <td><span class="badge" style="background:${isInc ? 'rgba(16,185,129,0.15)' : isTransfer ? 'rgba(56,189,248,0.15)' : 'rgba(244,63,94,0.15)'}; color:${isInc ? '#10b981' : isTransfer ? '#38bdf8' : '#f43f5e'};">${ucfirst(t.type)}</span></td>
                    <td><span class="badge" style="background:${t.category_color}22; color:${t.category_color};">${escapeHtml(t.category_name || '-')}</span></td>
                    <td style="color:#cbd5e1; font-size:0.85rem;">${escapeHtml(t.account_name || '-')}</td>
                    <td class="${isInc ? 'amount-income' : isTransfer ? 'amount-transfer' : 'amount-expense'}">
                        ${isInc ? '+' : isTransfer ? '↔' : '-'}${currentCurrency}${formatNumber(t.amount)}
                    </td>
                    <td style="color:#94a3b8; font-size:0.82rem;">${escapeHtml(t.notes || '-')}</td>
                    <td>
                        <button class="btn-delete" onclick="deleteTransaction(${t.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Pagination UI
            const p = data.pagination;
            document.getElementById('txPaginationInfo').textContent = `Showing page ${p.current_page} of ${p.total_pages} (${p.total_count} transactions)`;
            document.getElementById('txCurrentPageBadge').textContent = p.current_page;
            document.getElementById('txPrevBtn').disabled = p.current_page <= 1;
            document.getElementById('txNextBtn').disabled = p.current_page >= p.total_pages;
        } else {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#94a3b8;">No matching transactions found.</td></tr>`;
            document.getElementById('txPaginationInfo').textContent = 'Showing 0 transactions';
            document.getElementById('txPrevBtn').disabled = true;
            document.getElementById('txNextBtn').disabled = true;
        }
    } catch (err) {
        console.error('Error loading transactions ledger:', err);
    }
}

function changeTxPage(delta) {
    loadTransactionsTable(currentTxPage + delta);
}

async function deleteTransaction(id) {
    if (!confirm('Are you sure you want to delete this transaction?')) return;
    try {
        const res = await fetch('api/transactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Transaction deleted.', 'success');
            loadTransactionsTable(currentTxPage);
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// Modal Generic Transaction
function openTransactionModal() {
    document.getElementById('genericTxForm').reset();
    document.getElementById('modalTxDate').value = new Date().toISOString().split('T')[0];
    loadCategoriesForType('expense');
    openModal('transactionModal');
}

async function handleGenericTxSubmit(e) {
    e.preventDefault();
    const type = document.getElementById('modalTxType').value;
    const title = document.getElementById('modalTxTitle').value.trim();
    const amount = document.getElementById('modalTxAmount').value;
    const transaction_date = document.getElementById('modalTxDate').value;
    const category_id = document.getElementById('modalTxCategory').value;
    const account_id = document.getElementById('modalTxAccount').value;
    const payment_method = document.getElementById('modalTxMethod').value;
    const notes = document.getElementById('modalTxNotes').value.trim();

    try {
        const res = await fetch('api/transactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', type, title, amount, transaction_date, category_id, account_id, payment_method, notes })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('transactionModal');
            loadTransactionsTable(1);
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to save transaction', 'error'); }
}

// ========================================================
// 8. BUDGET MANAGEMENT & SAVINGS GOALS
// ========================================================
async function loadBudgets() {
    try {
        const res = await fetch('api/budgets.php?action=list');
        const data = await res.json();
        const container = document.getElementById('budgetsContainer');
        container.innerHTML = '';

        if (data.status === 'success' && data.budgets.length > 0) {
            data.budgets.forEach(b => {
                const limit = parseFloat(b.monthly_limit);
                const spent = parseFloat(b.spent);
                const pct = b.percentage;

                let barColor = '#10b981';
                let badgeStatus = `<span class="badge" style="background:rgba(16,185,129,0.15); color:#10b981;">Normal</span>`;
                if (b.status === 'exceeded') {
                    barColor = '#ef4444';
                    badgeStatus = `<span class="badge" style="background:rgba(239,68,68,0.2); color:#ef4444;">Exceeded!</span>`;
                } else if (b.status === 'warning') {
                    barColor = '#f59e0b';
                    badgeStatus = `<span class="badge" style="background:rgba(245,158,11,0.2); color:#f59e0b;">Warning (>80%)</span>`;
                }

                const card = document.createElement('div');
                card.className = 'budget-card';
                card.innerHTML = `
                    <div class="budget-header">
                        <h4 style="color:#f1f5f9; font-size:1.05rem;">${escapeHtml(b.category_name)}</h4>
                        ${badgeStatus}
                    </div>
                    <div style="font-size:0.88rem; color:#94a3b8; display:flex; justify-content:space-between;">
                        <span>Spent: <strong style="color:#f8fafc;">${currentCurrency}${formatNumber(spent)}</strong></span>
                        <span>Limit: ${currentCurrency}${formatNumber(limit)}</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:${Math.min(100, pct)}%; background:${barColor}; box-shadow:0 0 10px ${barColor}66;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem;">
                        <span style="color:#94a3b8;">${pct}% used (${currentCurrency}${formatNumber(b.remaining)} left)</span>
                        <button class="btn-delete" style="padding:0.25rem 0.5rem;" onclick="deleteBudget(${b.id})">Remove</button>
                    </div>
                `;
                container.appendChild(card);
            });
        } else {
            container.innerHTML = `<div style="color:#94a3b8; grid-column:1/-1;">No monthly category budgets established yet. Click '+ Set Category Budget' above to start!</div>`;
        }
    } catch (err) {
        console.error('Error loading budgets:', err);
    }
}

function openBudgetModal() {
    document.getElementById('budgetForm').reset();
    openModal('budgetModal');
}

async function handleBudgetSubmit(e) {
    e.preventDefault();
    const category_id = document.getElementById('budgetCategory').value;
    const monthly_limit = document.getElementById('budgetLimit').value;

    try {
        const res = await fetch('api/budgets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save', category_id, monthly_limit })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('budgetModal');
            loadBudgets();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to save budget', 'error'); }
}

async function deleteBudget(id) {
    if (!confirm('Remove this category budget limit?')) return;
    try {
        const res = await fetch('api/budgets.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Budget removed', 'success');
            loadBudgets();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// Savings Goals
async function loadSavingsGoals() {
    try {
        const res = await fetch('api/savings.php?action=list');
        const data = await res.json();
        const container = document.getElementById('savingsContainer');
        container.innerHTML = '';

        if (data.status === 'success' && data.goals.length > 0) {
            data.goals.forEach(g => {
                const isCompleted = g.status === 'completed';
                const card = document.createElement('div');
                card.className = 'goal-card';
                card.innerHTML = `
                    <div class="goal-header">
                        <h4 style="color:#f8fafc; font-size:1.1rem; font-weight:700;">${escapeHtml(g.title)}</h4>
                        <span class="badge" style="background:${isCompleted ? 'rgba(16,185,129,0.2)' : 'rgba(6,182,212,0.2)'}; color:${isCompleted ? '#10b981' : '#06b6d4'}; border:1px solid ${g.color}44;">
                            ${isCompleted ? '✅ Achieved' : `${g.percentage}% Reached`}
                        </span>
                    </div>
                    <div style="font-size:0.88rem; color:#94a3b8;">
                        Saved: <strong style="color:#f8fafc;">${currentCurrency}${formatNumber(g.current_amount)}</strong> / Target: ${currentCurrency}${formatNumber(g.target_amount)}
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:${g.percentage}%; background:${g.color}; box-shadow:0 0 10px ${g.color}66;"></div>
                    </div>
                    ${g.monthly_needed > 0 && !isCompleted ? `<div style="font-size:0.8rem; color:#a5b4fc; margin-bottom:0.6rem;">💡 Needs approx. <strong>${currentCurrency}${formatNumber(g.monthly_needed)}/month</strong> to reach on time</div>` : ''}
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; margin-top:0.8rem;">
                        <button class="btn-secondary" style="flex:1;" onclick="openDepositModal(${g.id}, '${escapeHtml(g.title)}')">+ Deposit Funds</button>
                        <button class="btn-delete" onclick="deleteSavingsGoal(${g.id})">Delete</button>
                    </div>
                `;
                container.appendChild(card);
            });
        } else {
            container.innerHTML = `<div style="color:#94a3b8; grid-column:1/-1;">No savings goals created yet. Click '+ Create Savings Goal' above!</div>`;
        }
    } catch (err) {
        console.error('Error loading savings goals:', err);
    }
}

function openSavingsGoalModal() {
    document.getElementById('savingsGoalForm').reset();
    openModal('savingsGoalModal');
}

async function handleSavingsGoalSubmit(e) {
    e.preventDefault();
    const title = document.getElementById('goalTitle').value.trim();
    const target_amount = document.getElementById('goalTarget').value;
    const current_amount = document.getElementById('goalCurrent').value || 0;
    const target_date = document.getElementById('goalDate').value;
    const color = document.getElementById('goalColor').value;

    try {
        const res = await fetch('api/savings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', title, target_amount, current_amount, target_date, color })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('savingsGoalModal');
            loadSavingsGoals();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to create goal', 'error'); }
}

function openDepositModal(id, title) {
    document.getElementById('depositGoalId').value = id;
    document.getElementById('depositGoalTitle').textContent = title;
    document.getElementById('depositAmount').value = '';
    openModal('depositModal');
}

async function handleDepositSubmit(e) {
    e.preventDefault();
    const goal_id = document.getElementById('depositGoalId').value;
    const amount = document.getElementById('depositAmount').value;

    try {
        const res = await fetch('api/savings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deposit', goal_id, amount })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('depositModal');
            loadSavingsGoals();
            loadDashboardData();
            loadNotifications();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Deposit failed', 'error'); }
}

async function deleteSavingsGoal(id) {
    if (!confirm('Are you sure you want to remove this savings goal?')) return;
    try {
        const res = await fetch('api/savings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', goal_id: id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Savings goal deleted', 'success');
            loadSavingsGoals();
            loadDashboardData();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// ========================================================
// 9. MULTIPLE ACCOUNTS & WALLETS (INTERNAL TRANSFERS)
// ========================================================
async function loadAccounts() {
    try {
        const res = await fetch('api/accounts.php?action=list');
        const data = await res.json();
        const container = document.getElementById('accountsContainer');
        container.innerHTML = '';

        if (data.status === 'success' && data.accounts.length > 0) {
            allAccounts = data.accounts;
            populateAccountDropdowns();

            data.accounts.forEach(acc => {
                const bal = parseFloat(acc.balance);
                const isNeg = bal < 0;
                const card = document.createElement('div');
                card.className = 'account-card';
                card.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
                        <span class="badge" style="background:${acc.color}22; color:${acc.color}; border:1px solid ${acc.color}44;">
                            ${escapeHtml(acc.account_type)}
                        </span>
                        <small style="color:#64748b;">${escapeHtml(acc.account_number || '')}</small>
                    </div>
                    <h3 style="font-size:1.25rem; color:#f8fafc; font-weight:700;">${escapeHtml(acc.account_name)}</h3>
                    <h2 style="font-size:1.7rem; margin:0.6rem 0; color:${isNeg ? '#f43f5e' : '#10b981'}; font-weight:800;">
                        ${isNeg ? '-' : ''}${currentCurrency}${formatNumber(Math.abs(bal))}
                    </h2>
                    <div style="font-size:0.8rem; color:#94a3b8; display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.05); padding-top:0.8rem; margin-top:0.8rem;">
                        <span>Opening: ${currentCurrency}${formatNumber(acc.opening_balance)}</span>
                        <button class="btn-delete" style="padding:0.2rem 0.5rem;" onclick="deleteAccount(${acc.id})">Delete</button>
                    </div>
                `;
                container.appendChild(card);
            });
        } else {
            container.innerHTML = `<div style="color:#94a3b8; grid-column:1/-1;">No accounts found. Click '+ Add New Account' above to start tracking!</div>`;
        }
    } catch (err) {
        console.error('Error loading accounts:', err);
    }
}

function openAccountModal() {
    document.getElementById('accountForm').reset();
    openModal('accountModal');
}

async function handleAccountSubmit(e) {
    e.preventDefault();
    const account_name = document.getElementById('accName').value.trim();
    const account_type = document.getElementById('accType').value;
    const opening_balance = document.getElementById('accOpening').value || 0;
    const account_number = document.getElementById('accNumber').value.trim();

    try {
        const res = await fetch('api/accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', account_name, account_type, opening_balance, account_number })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('accountModal');
            loadAccounts();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to add account', 'error'); }
}

async function deleteAccount(id) {
    if (!confirm('Are you sure you want to delete this account?')) return;
    try {
        const res = await fetch('api/accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Account deleted.', 'success');
            loadAccounts();
            loadDashboardData();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// Internal Transfers
function openTransferModal() {
    document.getElementById('transferForm').reset();
    document.getElementById('transferDate').value = new Date().toISOString().split('T')[0];
    populateAccountDropdowns();
    openModal('transferModal');
}

async function handleTransferSubmit(e) {
    e.preventDefault();
    const from_account_id = document.getElementById('transferFrom').value;
    const to_account_id = document.getElementById('transferTo').value;
    const amount = document.getElementById('transferAmount').value;
    const transfer_date = document.getElementById('transferDate').value;
    const notes = document.getElementById('transferNotes').value.trim();

    try {
        const res = await fetch('api/accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'transfer', from_account_id, to_account_id, amount, transfer_date, notes })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('transferModal');
            loadAccounts();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Transfer failed', 'error'); }
}

// ========================================================
// 10. RECURRING TRANSACTIONS & DEBTS
// ========================================================
async function loadRecurring() {
    try {
        const res = await fetch('api/recurring.php?action=list');
        const data = await res.json();
        const tbody = document.getElementById('recurringBody');
        tbody.innerHTML = '';

        if (data.status === 'success' && data.recurring.length > 0) {
            data.recurring.forEach(r => {
                const isInc = r.type === 'income';
                let dueBadge = `<span class="badge" style="background:rgba(255,255,255,0.06); color:#94a3b8;">${escapeHtml(r.due_text)}</span>`;
                if (r.due_status === 'overdue' || r.due_status === 'due_today') {
                    dueBadge = `<span class="badge" style="background:rgba(239,68,68,0.2); color:#ef4444;">${escapeHtml(r.due_text)}</span>`;
                } else if (r.due_status === 'due_soon') {
                    dueBadge = `<span class="badge" style="background:rgba(245,158,11,0.2); color:#f59e0b;">${escapeHtml(r.due_text)}</span>`;
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapeHtml(r.title)}</strong></td>
                    <td><span class="badge" style="background:${isInc ? 'rgba(16,185,129,0.15)' : 'rgba(244,63,94,0.15)'}; color:${isInc ? '#10b981' : '#f43f5e'};">${ucfirst(r.type)}</span></td>
                    <td><span class="badge" style="background:${r.category_color}22; color:${r.category_color};">${escapeHtml(r.category_name)}</span></td>
                    <td><span class="badge" style="background:rgba(99,102,241,0.15); color:#a5b4fc;">${ucfirst(r.frequency)}</span></td>
                    <td class="${isInc ? 'amount-income' : 'amount-expense'}">${isInc ? '+' : '-'}${currentCurrency}${formatNumber(r.amount)}</td>
                    <td>${r.next_due_date} ${dueBadge}</td>
                    <td><span class="badge" style="background:rgba(16,185,129,0.15); color:#10b981;">Active</span></td>
                    <td style="display:flex; gap:0.4rem;">
                        <button class="btn-action-green" onclick="executeRecurring(${r.id})">⚡ Log Now</button>
                        <button class="btn-delete" onclick="deleteRecurring(${r.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#94a3b8;">No recurring schedules added yet.</td></tr>`;
        }
    } catch (err) {
        console.error('Error loading recurring:', err);
    }
}

function openRecurringModal() {
    document.getElementById('recurringForm').reset();
    document.getElementById('recNextDue').value = new Date().toISOString().split('T')[0];
    openModal('recurringModal');
}

async function handleRecurringSubmit(e) {
    e.preventDefault();
    const title = document.getElementById('recTitle').value.trim();
    const type = document.getElementById('recType').value;
    const frequency = document.getElementById('recFrequency').value;
    const amount = document.getElementById('recAmount').value;
    const next_due_date = document.getElementById('recNextDue').value;
    const category_id = document.getElementById('recCategory').value;
    const account_id = document.getElementById('recAccount').value;

    try {
        const res = await fetch('api/recurring.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', title, type, frequency, amount, next_due_date, category_id, account_id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('recurringModal');
            loadRecurring();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to schedule recurring', 'error'); }
}

async function executeRecurring(id) {
    try {
        const res = await fetch('api/recurring.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'execute', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            loadRecurring();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Execution failed', 'error'); }
}

async function deleteRecurring(id) {
    if (!confirm('Remove this recurring schedule?')) return;
    try {
        const res = await fetch('api/recurring.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Recurring schedule removed.', 'success');
            loadRecurring();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// Debts & Loans
async function loadDebts() {
    try {
        const res = await fetch('api/debts.php?action=list');
        const data = await res.json();
        const tbody = document.getElementById('debtsBody');
        tbody.innerHTML = '';

        if (data.status === 'success' && data.debts.length > 0) {
            data.debts.forEach(d => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapeHtml(d.loan_name)}</strong><br><small style="color:#64748b;">${escapeHtml(d.loan_type)}</small></td>
                    <td>${escapeHtml(d.lender)}</td>
                    <td>${currentCurrency}${formatNumber(d.principal_amount)}</td>
                    <td style="color:#f43f5e; font-weight:700;">${currentCurrency}${formatNumber(d.current_balance)}</td>
                    <td>${d.interest_rate}%</td>
                    <td><strong>${currentCurrency}${formatNumber(d.monthly_emi)}</strong></td>
                    <td>
                        <div style="font-size:0.75rem; color:#94a3b8;">${d.pct_paid}% paid (${d.months_left} EMIs left)</div>
                        <div class="progress-bar-bg" style="margin:4px 0 0 0; height:6px;">
                            <div class="progress-bar-fill" style="width:${d.pct_paid}%; background:#10b981;"></div>
                        </div>
                    </td>
                    <td style="display:flex; gap:0.4rem;">
                        <button class="btn-action-green" onclick="openEmiModal(${d.id}, '${escapeHtml(d.loan_name)}', ${d.monthly_emi})">💳 Pay EMI</button>
                        <button class="btn-delete" onclick="deleteLoan(${d.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color:#94a3b8;">No loans or debt records recorded.</td></tr>`;
        }
    } catch (err) {
        console.error('Error loading debts:', err);
    }
}

function openLoanModal() {
    document.getElementById('loanForm').reset();
    openModal('loanModal');
}

async function handleLoanSubmit(e) {
    e.preventDefault();
    const loan_name = document.getElementById('loanName').value.trim();
    const lender = document.getElementById('loanLender').value.trim();
    const loan_type = document.getElementById('loanType').value;
    const principal_amount = document.getElementById('loanPrincipal').value;
    const current_balance = document.getElementById('loanBalance').value || principal_amount;
    const interest_rate = document.getElementById('loanRate').value || 10;
    const monthly_emi = document.getElementById('loanEmi').value;

    try {
        const res = await fetch('api/debts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', loan_name, lender, loan_type, principal_amount, current_balance, interest_rate, monthly_emi })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('loanModal');
            loadDebts();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to add loan', 'error'); }
}

function openEmiModal(id, title, emi) {
    document.getElementById('emiLoanId').value = id;
    document.getElementById('emiLoanTitle').textContent = title;
    document.getElementById('emiAmount').value = emi;
    openModal('emiModal');
}

async function handleEmiSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('emiLoanId').value;
    const payment_amount = document.getElementById('emiAmount').value;
    const account_id = document.getElementById('emiAccount').value;

    try {
        const res = await fetch('api/debts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'pay_emi', id, payment_amount, account_id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('emiModal');
            loadDebts();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('EMI payment failed', 'error'); }
}

async function deleteLoan(id) {
    if (!confirm('Delete this loan record?')) return;
    try {
        const res = await fetch('api/debts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Loan record deleted.', 'success');
            loadDebts();
            loadDashboardData();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// ========================================================
// 11. INVESTMENT PORTFOLIO
// ========================================================
async function loadInvestments() {
    try {
        const res = await fetch('api/investments.php?action=list');
        const data = await res.json();
        const tbody = document.getElementById('investmentBody');
        tbody.innerHTML = '';

        if (data.status === 'success' && data.investments.length > 0) {
            data.investments.forEach(inv => {
                const isProfit = inv.profit_loss >= 0;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapeHtml(inv.asset_name)}</strong></td>
                    <td><span class="badge" style="background:rgba(168,85,247,0.15); color:#c084fc;">${escapeHtml(inv.asset_type)}</span></td>
                    <td>${currentCurrency}${formatNumber(inv.amount_invested)}</td>
                    <td><strong>${currentCurrency}${formatNumber(inv.current_value)}</strong></td>
                    <td style="color:${isProfit ? '#10b981' : '#f43f5e'}; font-weight:700;">
                        ${isProfit ? '+' : ''}${currentCurrency}${formatNumber(inv.profit_loss)} (${inv.roi_percentage}%)
                    </td>
                    <td><button class="btn-delete" onclick="deleteInvestment(${inv.id})">Delete</button></td>
                `;
                tbody.appendChild(tr);
            });

            // Summary banner
            const s = data.summary;
            document.getElementById('invSummaryBanner').textContent = `Total Portfolio ROI: ${s.total_roi_percentage}% (${s.total_profit_loss >= 0 ? '+' : ''}${currentCurrency}${formatNumber(s.total_profit_loss)})`;

            renderPortfolioChart(data.asset_breakdown);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#94a3b8;">No investment holdings recorded.</td></tr>`;
            document.getElementById('invSummaryBanner').textContent = '';
        }
    } catch (err) {
        console.error('Error loading investments:', err);
    }
}

function renderPortfolioChart(breakdown) {
    const ctx = document.getElementById('portfolioChart').getContext('2d');
    const labels = breakdown.map(i => i.asset_type);
    const amounts = breakdown.map(i => parseFloat(i.total_val));
    const colors = ['#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#3b82f6', '#f97316'];

    if (portfolioChartInst) portfolioChartInst.destroy();
    if (breakdown.length === 0) return;

    portfolioChartInst = new Chart(ctx, {
        type: 'pie',
        data: { labels, datasets: [{ data: amounts, backgroundColor: colors, borderWidth: 2, borderColor: '#141b2d' }] },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { position: 'bottom', labels: { color: '#94a3b8', font: { family: 'Inter', size: 11 } } } 
            } 
        }
    });
}

async function handleInvestmentSubmit(e) {
    e.preventDefault();
    const asset_name = document.getElementById('invAssetName').value.trim();
    const asset_type = document.getElementById('invAssetType').value;
    const amount_invested = document.getElementById('invAmountInvested').value;
    const current_value = document.getElementById('invCurrentValue').value || amount_invested;
    const dividends = document.getElementById('invDividends').value || 0;

    try {
        const res = await fetch('api/investments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', asset_name, asset_type, amount_invested, current_value, dividends })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            document.getElementById('investmentForm').reset();
            loadInvestments();
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Failed to add investment', 'error'); }
}

async function deleteInvestment(id) {
    if (!confirm('Remove this holding from your portfolio?')) return;
    try {
        const res = await fetch('api/investments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast('Investment removed', 'success');
            loadInvestments();
            loadDashboardData();
        }
    } catch (err) { showToast('Delete failed', 'error'); }
}

// ========================================================
// 12. REPORTS, ANALYTICS & FORECASTING
// ========================================================
async function loadReports() {
    try {
        const res = await fetch('api/reports.php');
        const data = await res.json();

        if (data.status === 'success') {
            // Yearly Comparison Chart
            renderYearlyChart(data.yearly_comparison);

            // Daily Spending Trend Chart
            renderDailyChart(data.daily_spending);

            // Category Table
            const tbody = document.getElementById('reportCategoryBody');
            tbody.innerHTML = '';
            if (data.expense_categories && data.expense_categories.length > 0) {
                data.expense_categories.forEach(c => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><span class="badge" style="background:${c.color}22; color:${c.color};">${escapeHtml(c.name)}</span></td>
                        <td>${c.tx_count}</td>
                        <td><strong>${currentCurrency}${formatNumber(c.total_amount)}</strong></td>
                        <td>
                            <div style="font-size:0.8rem; color:#f1f5f9; font-weight:700;">${c.percentage}%</div>
                            <div class="progress-bar-bg" style="margin:4px 0 0 0; height:6px;">
                                <div class="progress-bar-fill" style="width:${c.percentage}%; background:${c.color};"></div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#94a3b8;">No expense records this month.</td></tr>`;
            }

            // Forecasting Cards
            const f = data.forecasting;
            document.getElementById('forecastNextIncome').textContent = `${currentCurrency}${formatNumber(f.predicted_next_month_income)}`;
            document.getElementById('forecastNextExpense').textContent = `${currentCurrency}${formatNumber(f.predicted_next_month_expense)}`;
            document.getElementById('forecast6MonthSavings').textContent = `${currentCurrency}${formatNumber(f.projected_6_month_savings)}`;
        }
    } catch (err) {
        console.error('Error loading reports:', err);
    }
}

function renderYearlyChart(yearlyData) {
    const ctx = document.getElementById('yearlyChart').getContext('2d');
    const labels = yearlyData.map(d => d.month_name);
    const incs = yearlyData.map(d => d.income);
    const exps = yearlyData.map(d => d.expense);

    if (yearlyChartInst) yearlyChartInst.destroy();

    yearlyChartInst = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Income', data: incs, backgroundColor: 'rgba(16, 185, 129, 0.85)', borderRadius: 5 },
                { label: 'Expense', data: exps, backgroundColor: 'rgba(244, 63, 94, 0.85)', borderRadius: 5 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                title: { display: true, text: 'Annual Cash Flow Breakdown (Yearly Comparison)', color: '#f1f5f9' },
                legend: { labels: { color: '#94a3b8' } }
            },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

function renderDailyChart(dailyData) {
    const ctx = document.getElementById('dailySpendChart').getContext('2d');
    const labels = dailyData.map(d => d.day);
    const exps = dailyData.map(d => d.expense);

    if (dailyChartInst) dailyChartInst.destroy();

    dailyChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Daily Spending',
                data: exps,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

// ========================================================
// 13. AI FINANCIAL ASSISTANT (NLP INTELLIGENCE)
// ========================================================
function sendAiPrompt(text) {
    document.getElementById('aiQueryInput').value = text;
    handleAiChatSubmit(new Event('submit'));
}

async function handleAiChatSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();
    const input = document.getElementById('aiQueryInput');
    const query = input.value.trim();
    if (!query) return;

    const chatBox = document.getElementById('aiChatMessages');

    // Append User Bubble
    const userBubble = document.createElement('div');
    userBubble.className = 'chat-bubble user';
    userBubble.textContent = query;
    chatBox.appendChild(userBubble);
    input.value = '';

    // Append AI Loading Bubble
    const aiBubble = document.createElement('div');
    aiBubble.className = 'chat-bubble ai';
    aiBubble.innerHTML = 'Thinking and analyzing your financial ledger... ⚡';
    chatBox.appendChild(aiBubble);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const res = await fetch('api/ai_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query })
        });
        const data = await res.json();

        if (data.status === 'success') {
            aiBubble.innerHTML = data.answer;
        } else {
            aiBubble.innerHTML = `⚠️ ${escapeHtml(data.message || 'Error communicating with assistant.')}`;
        }
    } catch (err) {
        aiBubble.innerHTML = '⚠️ Assistant error. Please try again.';
    }
    chatBox.scrollTop = chatBox.scrollHeight;
}

// ========================================================
// 14. IN-APP NOTIFICATIONS & ALERTS
// ========================================================
function toggleNotificationsDrawer() {
    const drawer = document.getElementById('notificationsDrawer');
    drawer.classList.toggle('active');
}

async function loadNotifications() {
    try {
        const res = await fetch('api/notifications.php?action=list');
        const data = await res.json();

        if (data.status === 'success') {
            updateNotifBadge(data.unread_count);
            const list = document.getElementById('notifList');
            list.innerHTML = '';

            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(n => {
                    const item = document.createElement('div');
                    item.className = `notif-item ${n.is_read == 0 ? 'unread' : ''}`;
                    item.innerHTML = `
                        <h5>${escapeHtml(n.title)}</h5>
                        <p>${escapeHtml(n.message)}</p>
                        <div class="notif-time">${n.created_at}</div>
                    `;
                    list.appendChild(item);
                });
            } else {
                list.innerHTML = `<div style="padding:1.5rem; text-align:center; color:#94a3b8; font-size:0.85rem;">No notifications right now.</div>`;
            }
        }
    } catch (err) {
        console.error('Error loading notifications:', err);
    }
}

function updateNotifBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (count > 0) {
        badge.style.display = 'flex';
        badge.textContent = count > 9 ? '9+' : count;
    } else {
        badge.style.display = 'none';
    }
}

async function markAllNotificationsRead() {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        loadNotifications();
    } catch (err) { console.error(err); }
}

async function clearAllNotifications() {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear_all' })
        });
        loadNotifications();
    } catch (err) { console.error(err); }
}

// ========================================================
// 15. PROFILE & SETTINGS
// ========================================================
async function handleProfileSubmit(e) {
    e.preventDefault();
    const name = document.getElementById('prof_name').value.trim();
    const currency = document.getElementById('prof_currency').value;
    const security_question = document.getElementById('prof_question').value;
    const security_answer = document.getElementById('prof_answer').value.trim();
    const current_password = document.getElementById('prof_curr_password').value;
    const new_password = document.getElementById('prof_new_password').value;

    try {
        const res = await fetch('api/profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, currency, security_question, security_answer, current_password, new_password })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            currentUser.name = name;
            currentUser.currency = currency;
            currentCurrency = currency;
            updateUserProfileUI();
            loadDashboardData();
            document.getElementById('prof_curr_password').value = '';
            document.getElementById('prof_new_password').value = '';
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('Profile update failed', 'error'); }
}

// ========================================================
// 16. CSV IMPORT
// ========================================================
function openCsvModal() {
    document.getElementById('csvForm').reset();
    openModal('csvModal');
}

async function handleCsvImportSubmit(e) {
    e.preventDefault();
    const fileInput = document.getElementById('csvFileInput');
    if (!fileInput.files || fileInput.files.length === 0) return showToast('Please select a CSV file', 'error');

    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);

    try {
        const res = await fetch('api/transactions.php?action=import_csv', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast(data.message, 'success');
            closeModal('csvModal');
            loadTransactionsTable(1);
            loadDashboardData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) { showToast('CSV import failed', 'error'); }
}

// ========================================================
// 17. MODAL & UI UTILITIES
// ========================================================
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.borderLeftColor = type === 'error' ? '#f43f5e' : '#10b981';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

function formatNumber(num) {
    const n = parseFloat(num) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
