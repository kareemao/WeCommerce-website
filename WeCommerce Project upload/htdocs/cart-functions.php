<?php
// Database connections
$host = "sql305.infinityfree.com";        
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT"; 

$redstore_conn = new mysqli($host, $user, $pass, "if0_39218569_redstore_db");
$users_conn = new mysqli($host, $user, $pass, "if0_39218569_users_db");

// Check connections
if ($redstore_conn->connect_error) {
    die("Redstore Connection failed: " . $redstore_conn->connect_error);
}
if ($users_conn->connect_error) {
    die("Users Connection failed: " . $users_conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Place order function with seller notifications
function placeOrder($redstore_conn, $users_conn) {
    if (empty($_SESSION['cart'])) {
    error_log("Cart is empty.");
    return false;
}

    // Get buyer info
    $buyer_id = $_SESSION['user_id'] ?? 0;
    $buyer_email = $_SESSION['email'] ?? '';
    $buyer_name = $_SESSION['name'] ?? 'A buyer';
    $buyer_message = $_POST['buyer_message'] ?? '';

    // Calculate total
    $total = getCartTotal();

    // Insert order
    $order_query = $redstore_conn->prepare(
        "INSERT INTO orders (user_id, total, status, buyer_message) VALUES (?, ?, 'pending', ?)"
    );
    if (!$order_query) {
        error_log("Order query failed: " . $redstore_conn->error);
        return false;
    }
    $order_query->bind_param("ids", $buyer_id, $total, $buyer_message);
    if (!$order_query->execute()) {
        error_log("Order execution failed: " . $order_query->error);
        return false;
    }
    $order_id = $redstore_conn->insert_id;
    $order_query->close();

    // Process each cart item
    foreach ($_SESSION['cart'] as $item) {
        $item_query = $redstore_conn->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
        );
        if (!$item_query) {
            error_log("Item insert failed: " . $redstore_conn->error);
            continue;
        }
        $item_query->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $item_query->execute();
        $item_query->close();

        // Fetch seller info
        $product_query = $redstore_conn->prepare("SELECT seller_id, product_name FROM products WHERE id = ?");
        if (!$product_query) {
            error_log("Product query failed: " . $redstore_conn->error);
            continue;
        }
        $product_query->bind_param("i", $item['id']);
        $product_query->execute();
        $product_result = $product_query->get_result();

        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            $seller_id = $product['seller_id'];
            $product_name = $product['product_name'];
            $product_query->close();

            // Notification message
            $message = "$buyer_name is interested in buying your product '$product_name'. Contact them at $buyer_email";

            // Insert into notifications
            $notif_query = $redstore_conn->prepare("INSERT INTO notifications (user_id, message, related_order_id) 
                                       VALUES (?, ?, ?)"

            );
            if ($notif_query) {
                $notif_query->bind_param("isi", $seller_id, $message, $order_id);
                $notif_query->execute();
                $notif_query->close();
            }

            // Send direct message to seller
            $seller_email_query = $users_conn->prepare("SELECT email FROM users WHERE id = ?");
            if ($seller_email_query) {
                $seller_email_query->bind_param("i", $seller_id);
                $seller_email_query->execute();
                $seller_result = $seller_email_query->get_result();

                if ($seller_result->num_rows > 0) {
                    $seller_row = $seller_result->fetch_assoc();
                    $seller_email = $seller_row['email'];

                    $message_query = $users_conn->prepare(
                        "INSERT INTO messages (sender_email, receiver_email, message) VALUES (?, ?, ?)"
                    );
                    if ($message_query) {
                        $message_query->bind_param("sss", $buyer_email, $seller_email, $message);
                        $message_query->execute();
                        $message_query->close();
                    }
                }
                $seller_email_query->close();
            }
        } else {
            $product_query->close();
        }
    }
    error_log("Order placed successfully for buyer ID: $buyer_id");
    return true;
}

// Add item to cart
function addToCart($product) {
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product['id']) {
            $item['quantity'] += (int)$product['quantity'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = $product;
    }
}

// Remove item from cart
function removeFromCart($id) {
    foreach ($_SESSION['cart'] as $i => $item) {
        if ($item['id'] == $id) {
            unset($_SESSION['cart'][$i]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }
}

// Update quantity in cart
function updateQuantity($id, $quantity) {
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $id) {
            $item['quantity'] = max(1, (int)$quantity);
            break;
        }
    }
}

// Calculate total
function getCartTotal() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product = [
                    'id' => (int)$_POST['id'],
                    'name' => $_POST['name'],
                    'price' => (float)$_POST['price'],
                    'image' => $_POST['image'],
                    'quantity' => (int)($_POST['quantity'] ?? 1)
                ];
                addToCart($product);
                header("Location: cart.php");
                exit;

            case 'update':
                updateQuantity((int)$_POST['id'], (int)$_POST['quantity']);
                header("Location: cart.php");
                exit;
        }
    }
}

// Handle remove action via GET
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    removeFromCart((int)$_GET['id']);
    header("Location: cart.php");
    exit;
}
?>
