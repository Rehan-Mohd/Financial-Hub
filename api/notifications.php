<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$user_id = require_auth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['action'])) $action = $input['action'];

try {
    // 1. LIST NOTIFICATIONS
    if ($action === 'list' || $_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 25");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();

        $unreadCount = 0;
        foreach ($notifications as $n) {
            if ($n['is_read'] == 0) $unreadCount++;
        }

        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        exit;
    }

    // 2. MARK ALL AS READ
    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read.']);
        exit;
    }

    // 3. CLEAR ALL NOTIFICATIONS
    if ($action === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['status' => 'success', 'message' => 'Notifications cleared.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Notification error: ' . $e->getMessage()]);
}
?>
