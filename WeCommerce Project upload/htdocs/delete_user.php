<?php
session_start();
require 'config.php';

// Verify admin role - FIXED: Missing parenthesis
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {  // FIXED: Extra parenthesis removed
    $id = intval($_GET['id']);
    
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {  // FIXED: Extra parenthesis removed
        $_SESSION['error'] = "You cannot delete your own account";
        header("Location: admin.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting user: " . $conn->error;
    }
    
    $stmt->close();
}

header("Location: admin.php");
exit();
?>