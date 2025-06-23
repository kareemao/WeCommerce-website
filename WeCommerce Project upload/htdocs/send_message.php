<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || !isset($_GET['to'])) {
    header("Location: login.php");
    exit();
}

$to = $_GET['to'];
$from = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $from, $to, $message);
        $stmt->execute();
        $success = "Message sent!";
    }
}

// Fetch chat history
$stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_email = ? AND receiver_email = ?) OR (sender_email = ? AND receiver_email = ?) ORDER BY timestamp ASC");
$stmt->bind_param("ssss", $from, $to, $to, $from);
$stmt->execute();
$chat = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat with <?= htmlspecialchars($to) ?></title>
    <style>
        .chat-box { border: 1px solid #ccc; padding: 15px; height: 300px; overflow-y: scroll; margin-bottom: 20px; }
        .chat-message { margin-bottom: 10px; }
        .mine { color: green; }
        .theirs { color: red; }
        textarea { width: 100%; height: 70px; }
    </style>
</head>
<body>
    <h2>Chat with <?= htmlspecialchars($to) ?></h2>

    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

    <div class="chat-box">
        <?php while ($row = $chat->fetch_assoc()): ?>
            <div class="chat-message <?= $row['sender_email'] === $from ? 'mine' : 'theirs' ?>">
                <strong><?= $row['sender_email'] ?>:</strong> <?= htmlspecialchars($row['message']) ?> <br>
                <small><?= $row['timestamp'] ?></small>
            </div>
        <?php endwhile; ?>
    </div>

    <form method="post">
        <textarea name="message" required placeholder="Type your message here..."></textarea>
        <button type="submit">Send</button>
    </form>
</body>
</html>
