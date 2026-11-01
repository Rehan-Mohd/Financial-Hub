-- Comprehensive Personal Finance Hub Database Schema
CREATE DATABASE IF NOT EXISTS `expense_tracker_db`;
USE `expense_tracker_db`;

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `debts_loans`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `investments`;
DROP TABLE IF EXISTS `savings_goals`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `currency` VARCHAR(10) DEFAULT '₹',
    `security_question` VARCHAR(255) DEFAULT 'What is your favorite city?',
    `security_answer` VARCHAR(255) DEFAULT 'Mumbai',
    `avatar` VARCHAR(255) DEFAULT 'default.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `color` VARCHAR(20) DEFAULT '#4f46e5',
    `icon` VARCHAR(50) DEFAULT 'tag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Standard Categories
INSERT INTO `categories` (`id`, `name`, `type`, `color`, `icon`) VALUES
-- Income Categories
(1, 'Salary', 'income', '#10b981', 'briefcase'),
(2, 'Freelance & Consulting', 'income', '#06b6d4', 'laptop'),
(3, 'Business Income', 'income', '#3b82f6', 'trending-up'),
(4, 'Investments & Dividends', 'income', '#8b5cf6', 'pie-chart'),
(5, 'Rental Income', 'income', '#14b8a6', 'home'),
(6, 'Gifts & Grants', 'income', '#ec4899', 'gift'),
(7, 'Other Income', 'income', '#6b7280', 'dollar-sign'),
-- Expense Categories
(8, 'Food & Dining', 'expense', '#ef4444', 'utensils'),
(9, 'Transportation & Fuel', 'expense', '#f59e0b', 'car'),
(10, 'Shopping & Lifestyle', 'expense', '#ec4899', 'shopping-bag'),
(11, 'Bills & Utilities', 'expense', '#6366f1', 'zap'),
(12, 'Entertainment & Subscriptions', 'expense', '#a855f7', 'film'),
(13, 'Healthcare & Pharmacy', 'expense', '#f97316', 'activity'),
(14, 'Education & Courses', 'expense', '#0ea5e9', 'book'),
(15, 'Housing & Rent', 'expense', '#84cc16', 'home'),
(16, 'Travel & Vacations', 'expense', '#06b6d4', 'compass'),
(17, 'Debt & Loan EMI', 'expense', '#e11d48', 'credit-card'),
(18, 'Other Expense', 'expense', '#94a3b8', 'tag');

-- 3. Accounts & Wallets Table
CREATE TABLE `accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `account_name` VARCHAR(100) NOT NULL,
    `account_type` ENUM('Bank Account', 'Cash', 'Digital Wallet', 'Credit Card', 'Other') NOT NULL,
    `balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `opening_balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `account_number` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#6366f1',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Transactions Table
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `account_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `type` ENUM('income', 'expense', 'transfer') NOT NULL,
    `category_id` INT NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'UPI',
    `payment_source` VARCHAR(100) DEFAULT NULL,
    `transaction_date` DATE NOT NULL,
    `notes` TEXT,
    `is_recurring` TINYINT(1) DEFAULT 0,
    `transfer_to_account_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`transfer_to_account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Budgets Table
CREATE TABLE `budgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `monthly_limit` DECIMAL(12, 2) NOT NULL,
    `month_year` VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Savings Goals Table
CREATE TABLE `savings_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `target_amount` DECIMAL(12, 2) NOT NULL,
    `current_amount` DECIMAL(12, 2) DEFAULT 0.00,
    `target_date` DATE DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#10b981',
    `notes` TEXT,
    `status` ENUM('active', 'completed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Recurring Transactions Table
CREATE TABLE `recurring_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `account_id` INT DEFAULT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `frequency` ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `next_due_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'paused') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Debts & Loans Table
CREATE TABLE `debts_loans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `loan_name` VARCHAR(100) NOT NULL,
    `lender` VARCHAR(100) NOT NULL,
    `loan_type` ENUM('Personal Loan', 'Home Loan', 'Car Loan', 'Education Loan', 'Credit Card', 'Other') NOT NULL,
    `principal_amount` DECIMAL(12, 2) NOT NULL,
    `current_balance` DECIMAL(12, 2) NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    `monthly_emi` DECIMAL(12, 2) NOT NULL,
    `tenure_months` INT NOT NULL DEFAULT 36,
    `start_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Investments Table
CREATE TABLE `investments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `asset_name` VARCHAR(100) NOT NULL,
    `asset_type` ENUM('Stocks', 'Mutual Funds', 'ETFs', 'Fixed Deposits', 'Gold', 'Bonds', 'Cryptocurrency', 'Real Estate', 'Other') NOT NULL,
    `amount_invested` DECIMAL(12, 2) NOT NULL,
    `current_value` DECIMAL(12, 2) NOT NULL,
    `dividends` DECIMAL(12, 2) DEFAULT 0.00,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Notifications Table
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Seed Initial Demo Users
-- 1) Rehan Mohd (Password: demo123)
-- 2) Sarah Khan (Password: demo123)
-- ========================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `currency`, `security_question`, `security_answer`) VALUES
(1, 'Rehan Mohd', 'rehan@example.com', '$2y$10$EZBZ3BOKDXzId.HooYPCA.oylhX8rIpuMj8tmHmEHyt1t8c5n0tWW', '₹', 'What is your favorite city?', 'Mumbai'),
(2, 'Sarah Khan', 'sarah@example.com', '$2y$10$EZBZ3BOKDXzId.HooYPCA.oylhX8rIpuMj8tmHmEHyt1t8c5n0tWW', '₹', 'What is your pet name?', 'Milo');

-- Seed Accounts for Rehan Mohd
INSERT INTO `accounts` (`id`, `user_id`, `account_name`, `account_type`, `balance`, `opening_balance`, `account_number`, `color`) VALUES
(1, 1, 'HDFC Salary Account', 'Bank Account', 68500.00, 20000.00, 'XX-5678', '#3b82f6'),
(2, 1, 'SBI Savings Account', 'Bank Account', 35000.00, 10000.00, 'XX-1234', '#06b6d4'),
(3, 1, 'Cash Wallet', 'Cash', 4500.00, 5000.00, 'Cash In Hand', '#10b981'),
(4, 1, 'Paytm / UPI Wallet', 'Digital Wallet', 3200.00, 2000.00, '9876543210', '#8b5cf6'),
(5, 1, 'HDFC Millennia Credit Card', 'Credit Card', -8500.00, 0.00, 'XX-9012', '#ec4899');

-- Seed Accounts for Sarah Khan
INSERT INTO `accounts` (`id`, `user_id`, `account_name`, `account_type`, `balance`, `opening_balance`, `account_number`, `color`) VALUES
(6, 2, 'ICICI Bank', 'Bank Account', 45000.00, 15000.00, 'XX-3344', '#f59e0b'),
(7, 2, 'Cash Wallet', 'Cash', 3000.00, 3000.00, 'Cash In Hand', '#10b981');

-- Seed Transactions for Rehan Mohd
INSERT INTO `transactions` (`user_id`, `account_id`, `title`, `amount`, `type`, `category_id`, `payment_method`, `payment_source`, `transaction_date`, `notes`) VALUES
(1, 1, 'Monthly Salary', 85000.00, 'income', 1, 'Bank Transfer', 'Tech Corp India', CURRENT_DATE(), 'November Salary credited'),
(1, 1, 'Freelance Web App Design', 22000.00, 'income', 2, 'UPI', 'Overseas Client', CURRENT_DATE(), 'Landing page UI/UX milestone'),
(1, 4, 'Grocery & Essentials (Supermarket)', 4250.00, 'expense', 8, 'UPI', 'Nature Basket', CURRENT_DATE(), 'Weekly provisions and fruits'),
(1, 3, 'Petrol & Fuel Refill', 1800.00, 'expense', 9, 'Cash', 'Indian Oil', CURRENT_DATE(), 'Full tank bike & commute'),
(1, 1, 'Electricity & High-speed Wifi Bill', 2450.00, 'expense', 11, 'Net Banking', 'BESCOM & Airtel', CURRENT_DATE(), 'Monthly home bills'),
(1, 5, 'Amazon Festive Shopping', 5800.00, 'expense', 10, 'Credit Card', 'Amazon India', CURRENT_DATE(), 'Wireless headphones & clothes'),
(1, 1, 'House Rent Payment', 18000.00, 'expense', 15, 'Bank Transfer', 'Landlord', CURRENT_DATE(), 'Monthly apartment rent');

-- Seed Budgets for Rehan Mohd (Current Month)
INSERT INTO `budgets` (`user_id`, `category_id`, `monthly_limit`, `month_year`) VALUES
(1, 8, 8000.00, DATE_FORMAT(CURRENT_DATE(), '%Y-%m')),   -- Food & Dining
(1, 9, 3500.00, DATE_FORMAT(CURRENT_DATE(), '%Y-%m')),   -- Transportation
(1, 10, 7000.00, DATE_FORMAT(CURRENT_DATE(), '%Y-%m')),  -- Shopping
(1, 11, 4000.00, DATE_FORMAT(CURRENT_DATE(), '%Y-%m')),  -- Bills
(1, 12, 2500.00, DATE_FORMAT(CURRENT_DATE(), '%Y-%m'));  -- Entertainment

-- Seed Savings Goals for Rehan Mohd
INSERT INTO `savings_goals` (`user_id`, `title`, `target_amount`, `current_amount`, `target_date`, `color`, `notes`, `status`) VALUES
(1, 'Emergency Fund (6 Months)', 200000.00, 125000.00, '2026-12-31', '#10b981', 'Keep in liquid fixed deposits', 'active'),
(1, 'New MacBook Pro M3', 140000.00, 85000.00, '2026-12-15', '#06b6d4', 'Work machine upgrade', 'active'),
(1, 'Goa & Kerala Holiday', 45000.00, 32000.00, '2027-02-28', '#f59e0b', 'Flights and resort stay', 'active'),
(1, 'Bike Down Payment', 60000.00, 60000.00, '2026-10-01', '#8b5cf6', 'Yamaha MT-15 achieved!', 'completed');

-- Seed Recurring Transactions for Rehan Mohd
INSERT INTO `recurring_transactions` (`user_id`, `account_id`, `category_id`, `title`, `amount`, `type`, `frequency`, `start_date`, `next_due_date`, `status`) VALUES
(1, 1, 1, 'Monthly Job Salary', 85000.00, 'income', 'monthly', '2026-01-01', DATE_ADD(CURRENT_DATE(), INTERVAL 25 DAY), 'active'),
(1, 1, 15, 'Apartment Rent', 18000.00, 'expense', 'monthly', '2026-01-05', DATE_ADD(CURRENT_DATE(), INTERVAL 4 DAY), 'active'),
(1, 4, 12, 'Netflix & Spotify Premium', 799.00, 'expense', 'monthly', '2026-01-10', DATE_ADD(CURRENT_DATE(), INTERVAL 8 DAY), 'active'),
(1, 1, 17, 'Car Loan EMI', 9450.00, 'expense', 'monthly', '2026-01-15', DATE_ADD(CURRENT_DATE(), INTERVAL 6 DAY), 'active');

-- Seed Debts & Loans for Rehan Mohd
INSERT INTO `debts_loans` (`user_id`, `loan_name`, `lender`, `loan_type`, `principal_amount`, `current_balance`, `interest_rate`, `monthly_emi`, `tenure_months`, `start_date`) VALUES
(1, 'Car Loan (Hyundai i20)', 'HDFC Bank', 'Car Loan', 450000.00, 285000.00, 8.75, 9450.00, 48, '2025-06-01'),
(1, 'Education Loan', 'SBI', 'Education Loan', 300000.00, 120000.00, 7.50, 6200.00, 60, '2024-01-01');

-- Seed Investments for Rehan Mohd
INSERT INTO `investments` (`user_id`, `asset_name`, `asset_type`, `amount_invested`, `current_value`, `dividends`, `notes`) VALUES
(1, 'Nifty 50 Index Fund (Direct Growth)', 'Mutual Funds', 120000.00, 148500.00, 0.00, 'Core equity index SIP'),
(1, 'Tata Motors & Reliance Shares', 'Stocks', 80000.00, 94200.00, 1500.00, 'BSE/NSE Bluechips'),
(1, 'Sovereign Gold Bond (SGB)', 'Gold', 50000.00, 61500.00, 1250.00, '2.5% annual coupon yield'),
(1, 'Fixed Deposit (7.25% p.a.)', 'Fixed Deposits', 100000.00, 107250.00, 7250.00, 'Emergency backup reserve'),
(1, 'Bitcoin & Ethereum', 'Cryptocurrency', 35000.00, 44000.00, 0.00, 'High risk digital assets');

-- Seed Notifications for Rehan Mohd
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 'Upcoming EMI Reminder', 'Car Loan EMI of ₹9,450 is due in 6 days.', 'warning', 0),
(1, 'Budget Update', 'You have used 62% of your Food & Dining budget this month.', 'info', 0),
(1, 'Goal Achieved!', 'Congratulations! You reached 100% of your Bike Down Payment goal.', 'success', 0);
