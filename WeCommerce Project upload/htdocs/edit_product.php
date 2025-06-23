<?php
session_start();

// Access control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("sql305.infinityfree.com", "if0_39218569", "cQNv6p985h0xT", "if0_39218569_redstore_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid product ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    $stmt = $conn->prepare("UPDATE products SET product_name=?, price=?, stock=? WHERE id=?");
    $stmt->bind_param("sdii", $name, $price, $stock, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}
?>

<h2>Edit Product</h2>
<form method="post">
    <label>Product Name:</label><br>
    <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required><br><br>

    <label>Price:</label><br>
    <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required><br><br>

    <label>Stock:</label><br>
    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" required><br><br>

    <button type="submit">Save Changes</button>
</form>
