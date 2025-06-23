<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['email'];
$users = $conn->query("SELECT name, email FROM users WHERE email != '$currentUser'");

$notifications = [];
if (isset($_SESSION['user_id'])) {
    $notif_query = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $notif_query->bind_param("i", $_SESSION['user_id']);
    $notif_query->execute();
    $notifications = $notif_query->get_result()->fetch_all(MYSQLI_ASSOC);

    // Mark notifications as read
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$_SESSION['user_id']}");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 40px;
            background-color: #fff5f5;
            color: #333;
        }

        h2 {
            text-align: center;
            color: #cc1f3a;
            margin-bottom: 30px;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .user-list a {
            display: block;
            padding: 12px 15px;
            margin: 10px 0;
            background-color: #ffe5e9;
            border: 1px solid #f3c5cc;
            border-radius: 8px;
            text-decoration: none;
            color: #cc1f3a;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .user-list a:hover {
            background-color: #fbd0d7;
            transform: translateX(5px);
        }

        .notifications {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .notification {
            padding: 12px;
            margin: 10px 0;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        .notification.unread {
            background-color: #fff0f0;
            border-left: 4px solid #dc3545;
            font-weight: bold;
        }

        .notification p {
            margin: 0 0 5px 0;
        }

        .notification small {
            color: #6c757d;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Send a Message</h2>
        <div class="user-list">
            <?php while ($row = $users->fetch_assoc()): ?>
                <a href="send_message.php?to=<?= urlencode($row['email']) ?>">
                    📩 Message <?= htmlspecialchars($row['name']) ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ✅ Moved OUTSIDE of <style> -->
    <div class="container notifications">
        <h3>Your Notifications</h3>
        <?php if (!empty($notifications)): ?>
            <?php foreach($notifications as $notif): ?>
                <div class="notification <?= $notif['is_read'] ? 'read' : 'unread'; ?>">
                    <p><?= htmlspecialchars($notif['message']); ?></p>
                    <small><?= date('M j, Y g:i a', strtotime($notif['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notifications yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
