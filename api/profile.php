<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$name = trim($input['name'] ?? '');
$currency = trim($input['currency'] ?? '₹');
$security_question = trim($input['security_question'] ?? '');
$security_answer = trim($input['security_answer'] ?? '');
$current_password = trim($input['current_password'] ?? '');
$new_password = trim($input['new_password'] ?? '');

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Name cannot be empty.']);
    exit;
}

try {
    // If user is trying to change password
    if (!empty($new_password)) {
        if (empty($current_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide current password to set a new password.']);
            exit;
        }

        if (strlen($new_password) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters.']);
            exit;
        }

        $stmtPass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtPass->execute([$user_id]);
        $row = $stmtPass->fetch();

        if (!$row || !password_verify($current_password, $row['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Current password entered is incorrect.']);
            exit;
        }

        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, currency = ?, password = ?, security_question = COALESCE(NULLIF(?, ''), security_question), security_answer = COALESCE(NULLIF(?, ''), security_answer) WHERE id = ?");
        $stmtUpdate->execute([$name, $currency, $hash, $security_question, $security_answer, $user_id]);
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, currency = ?, security_question = COALESCE(NULLIF(?, ''), security_question), security_answer = COALESCE(NULLIF(?, ''), security_answer) WHERE id = ?");
        $stmtUpdate->execute([$name, $currency, $security_question, $security_answer, $user_id]);
    }

    $_SESSION['user_name'] = $name;

    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully!',
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'currency' => $currency
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Profile update failed: ' . $e->getMessage()]);
}
?>
