<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. User Registration
    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $currency = trim($input['currency'] ?? '₹');
        $security_question = trim($input['security_question'] ?? 'What is your favorite city?');
        $security_answer = trim($input['security_answer'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Full name, email, and password are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
            exit;
        }

        if (strlen($password) < 3) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 3 characters.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, currency, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $currency, $security_question, $security_answer]);
        $user_id = $pdo->lastInsertId();

        // Create default starter accounts for new user
        $stmtAcc = $pdo->prepare("INSERT INTO accounts (user_id, account_name, account_type, balance, opening_balance, color) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtAcc->execute([$user_id, 'Primary Bank Account', 'Bank Account', 0.00, 0.00, '#3b82f6']);
        $stmtAcc->execute([$user_id, 'Cash Wallet', 'Cash', 0.00, 0.00, '#10b981']);

        // Create welcome notification
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmtNotif->execute([$user_id, 'Welcome to Financial Hub!', 'Your account has been set up successfully. Start by logging your opening balances or adding your first income!', 'success']);

        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;

        echo json_encode([
            'status' => 'success',
            'message' => 'Account created successfully! Welcome aboard.',
            'user' => [
                'id' => $user_id,
                'name' => $name,
                'email' => $email,
                'currency' => $currency
            ]
        ]);
        exit;
    }

    // 2. User Login
    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            echo json_encode([
                'status' => 'success',
                'message' => 'Welcome back, ' . htmlspecialchars($user['name']) . '!',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'currency' => $user['currency']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password. Please try again.']);
        }
        exit;
    }

    // 3. User Logout
    if ($action === 'logout') {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
        exit;
    }

    // 4. Current Session Check (Me)
    if ($action === 'me') {
        if (!is_authenticated()) {
            echo json_encode(['status' => 'success', 'authenticated' => false, 'user' => null]);
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, name, email, currency, security_question, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode(['status' => 'success', 'authenticated' => true, 'user' => $user]);
        } else {
            unset($_SESSION['user_id']);
            echo json_encode(['status' => 'success', 'authenticated' => false, 'user' => null]);
        }
        exit;
    }

    // 5. Forgot Password - Fetch Security Question
    if ($action === 'get_security_question') {
        $email = trim($input['email'] ?? '');
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide your registered email.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT security_question FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode([
                'status' => 'success',
                'question' => $user['security_question'] ?: 'What is your favorite city?'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No account found with this email address.']);
        }
        exit;
    }

    // 6. Reset Password with Security Answer
    if ($action === 'reset_password') {
        $email = trim($input['email'] ?? '');
        $security_answer = trim($input['security_answer'] ?? '');
        $new_password = trim($input['new_password'] ?? '');

        if (empty($email) || empty($security_answer) || empty($new_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email, security answer, and new password are required.']);
            exit;
        }

        if (strlen($new_password) < 3) {
            echo json_encode(['status' => 'error', 'message' => 'New password must be at least 3 characters.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, security_answer FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            exit;
        }

        // Case-insensitive check of answer
        if (strcasecmp(trim($user['security_answer'] ?? ''), $security_answer) !== 0) {
            echo json_encode(['status' => 'error', 'message' => 'Security answer is incorrect.']);
            exit;
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtUpdate->execute([$hash, $user['id']]);

        echo json_encode(['status' => 'success', 'message' => 'Password reset successfully! You can now log in.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action requested.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication error: ' . $e->getMessage()]);
}
?>
