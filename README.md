# Financial Hub – Smart Personal Finance, Savings & Wealth Analytics System

![PHP](https://img.shields.io/badge/PHP-8.2-777bb4.svg?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479a1.svg?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E.svg?style=flat&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-Glassmorphism-1572B6.svg?style=flat&logo=css3&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-Analytics-FF6384.svg?style=flat&logo=chartdotjs&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

**Financial Hub** is a full-stack, responsive web application engineered using **PHP, MySQL, HTML5, CSS3, and JavaScript**. Designed as an end-to-end personal finance, budgeting, and wealth intelligence dashboard, it empowers users to track cash flow, establish visual category budgets, manage multi-asset investment portfolios, and interact with an **AI Financial Intelligence Assistant**.

---

## 🌟 Key Features & Modules

### 1. 🔐 User Authentication & Multi-User Isolation
- **Secure Registration & Login:** Password hashing with **BCrypt** (`password_hash` & `password_verify`).
- **Strict Session Authorization:** Multi-user isolation ensures each user accesses only their own accounts, transactions, and budgets.
- **Forgot / Reset Password Workflow:** Password recovery via registered email and security questions.
- **User Profile Management:** Update name, currency preferences (`₹`, `$`, `€`, `£`), and security credentials.

### 2. 📊 Centralized Dashboard & Financial Health Score
- **Live Metrics:** Total Balance (Liquid Net Worth), Monthly Inflow, Monthly Outflow, Savings Reserve, Portfolio Valuation, and Outstanding Debt.
- **Financial Health Score (0–100 Meter):** Algorithm dynamically evaluates savings rate, budget adherence, debt-to-income ratio, and emergency fund coverage with visual badges (*Excellent, Good, Fair, Needs Attention*).
- **Interactive Visualizations:** Chart.js Doughnut chart for category allocation & 6-month historical cash flow bar chart.

### 3. 💰 Income & Expense Tracking (Smart Auto-Categorization)
- **Record Cash Flow:** Log income sources (*Salary, Freelance, Business, Dividends, Rental*) and expenses (*Food, Transport, Shopping, Bills, Entertainment, Healthcare, Education, Rent, EMIs*).
- **⚡ Keyword-Based Auto-Categorization:** As you type descriptions (e.g. *Zomato, Swiggy, Uber, Ola, Amazon, Netflix, Bescom*), the system automatically selects the matching category in real-time.

### 4. 📑 Centralized Transaction Ledger
- Filter by transaction type (*Income / Expense / Transfer*), category, account, and date range.
- Live instant search across descriptions, notes, and payment methods.
- Multi-criteria sorting (*by Date or Amount*).
- Clean pagination for large financial histories.

### 5. 🎯 Budgets & Savings Goals
- **Monthly Category Budgets:** Establish limits per category with dynamic progress bars and automated threshold warnings (*at 80% and >100%*).
- **Savings Goals Tracker:** Define targets (*Emergency Fund, Laptop, Vehicle, Vacation*), compute required monthly contributions, and record deposit milestones.

### 6. 💳 Multiple Accounts & Wallets (Internal Transfers)
- Monitor balances across Bank accounts, Cash wallets, UPI digital wallets, and Credit cards.
- **Internal Transfers:** Transfer money between accounts (e.g., *Bank $\rightarrow$ Paytm Wallet*) with balance reconciliation without distorting income/expense analytics.

### 7. 🔄 Recurring Schedules & Debt Management
- **Recurring Transactions:** Automate recurring salaries, subscriptions, rent, and bills with countdown badges and **1-click execution** into actual ledger records.
- **Loan & EMI Tracker:** Track personal loans, car loans, and education loans with interest rates, remaining balances, and **"Pay EMI"** payment logging.

### 8. 📈 Investment Portfolio Tracking
- Multi-asset holdings across *Stocks (BSE/NSE), Mutual Funds, ETFs, Fixed Deposits, Gold/SGB, Crypto, and Real Estate*.
- Computes absolute profit/loss, dividends received, and Return on Investment (ROI %).
- Asset allocation pie chart visualization.

### 9. 📑 Reports, Daily Trends & Forecasting
- Annual comparison charts (Jan – Dec).
- Day-by-day daily spending trend line chart.
- **Financial Forecasting:** 3-month trailing average predictive model estimating next month's cash flow, 6-month/12-month savings projections, and cash shortage warnings.
- **Print / PDF Ready:** Clean `@media print` formatting for 1-click printable PDF reports.

### 10. 🤖 AI-Powered Financial Assistant
- Integrated conversational assistant understanding natural language queries:
  - *"Where did I spend the most money this month?"*
  - *"How much did I spend on food in the last 30 days?"*
  - *"Can I afford a ₹50,000 laptop?"*
  - *"How can I save ₹10,000 every month?"*
  - *"Which category has increased the most?"*
  - *"Give me a monthly financial summary."*

### 11. 📥 Export and Import Data
- **CSV Export:** Download all transaction histories to Excel-compatible `.csv` format.
- **CSV Import:** Upload bank statement CSV files with automatic keyword category matching.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Frontend** | HTML5, CSS3 (Dark Glassmorphism UI), Vanilla JavaScript (ES6+) |
| **Data Visualization** | Chart.js |
| **Backend & APIs** | PHP 8.x (RESTful architecture, PDO Prepared Statements, Session Auth) |
| **Database** | MySQL (Relational schema with Foreign Key cascading) |
| **Server** | Apache / PHP Built-in CLI Server |

---

## 🚀 Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) or any local server environment with **PHP 8.x** and **MySQL**.

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Rehan-Mohd/Financial-Hub.git
   cd Financial-Hub
   ```

2. **Import Database:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a database named `expense_tracker_db`.
   - Import the `database.sql` file included in the repository.

3. **Configure Database Connection (if needed):**
   - Check `config/db.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'expense_tracker_db');
     ```

4. **Run the Application:**
   - Using PHP built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Open your browser and navigate to:
     ```text
     http://localhost:8000
     ```

---

## 🔑 Demo Account Credentials

| Email / Username | Password | Notes |
| :--- | :--- | :--- |
| `rehan@example.com` | `demo123` | Pre-seeded with realistic financial records & portfolios |
| `admin123@gmail.com` | `123` | Sample account with security question (*Pet: tom*) |

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
