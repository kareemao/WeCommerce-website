<?php
// USERS DATABASE CONNECTION
$host = "sql305.infinityfree.com";        
$db   = "if0_39218569_users_db";       
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT";       


$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);

}
// Add user_id to session if email exists but user_id doesn't
if (isset($_SESSION['email']) && !isset($_SESSION['user_id'])) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
    }
}

?>