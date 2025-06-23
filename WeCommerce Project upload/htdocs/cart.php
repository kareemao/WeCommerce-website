<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("cart-functions.php");

// Initialize database connections
$host = "sql305.infinityfree.com";        
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT"; 

$redstore_conn = new mysqli($host, $user, $pass, "if0_39218569_redstore_db");
$users_conn = new mysqli($host, $user, $pass, "if0_39218569_users_db");

// Check for database connection errors
if ($redstore_conn->connect_error || $users_conn->connect_error) {
    die("Database connection failed: " . $redstore_conn->connect_error . $users_conn->connect_error);
}

// Handle place order action
if (isset($_POST['action']) && $_POST['action'] == 'place_order') {
    if (!empty($_SESSION['cart'])) {
        if (placeOrder($redstore_conn, $users_conn)) {
            $_SESSION['order_success'] = true;
            $_SESSION['cart'] = [];
        } else {
            $_SESSION['order_error'] = "Failed to place order. Please try again.";
        }
    } else {
        $_SESSION['order_error'] = "Cannot place order with an empty cart.";
    }
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - RedStore</title>
    <link rel="stylesheet" href="CSS/style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .place-order-btn {
            display: block;
            width: 200px;
            margin: 30px auto;
            text-align: center;
            background: #ff523b;
            color: #fff;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.5s;
        }
        .place-order-btn:hover {
            background: #563434;
        }
        .order-success {
            text-align: center;
            color: green;
            margin: 20px 0;
            font-size: 18px;
        }
        .order-error {
            text-align: center;
            color: red;
            margin: 20px 0;
            font-size: 18px;
        }
        .browsing-section {
            text-align: center;
            margin: 40px 0;
        }
        .browsing-message {
            font-size: 18px;
            margin-bottom: 15px;
            color: #555;
        }
        .continue-btn {
            display: inline-block;
            background: #4CAF50;
            color: #fff;
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            transition: background 0.5s;
        }
        .continue-btn:hover {
            background: #45a049;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        .order-message-section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .order-message-section h3 {
            margin-bottom: 15px;
            color: #333;
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
            font-family: inherit;
        }
        .btn-update {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
        }
        .btn-update:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="logo">
                <img src="images/logo.png.png" alt="RedStore logo" width="125px">
            </div>
            <nav> 
                <ul id="MenuItems">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="login.php">Account</a></li>
                </ul>
            </nav>
            <a href="cart.php">
                <img src="images/cart.png" alt="cart" width="30px" height="30px">
                <span class="cart-count">
                    <?php 
                    $count = 0;
                    if (!empty($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $count += $item['quantity'];
                        }
                    }
                    echo $count;
                    ?>
                </span>
            </a>
            <img src="images/menu.png" class="menu-icon" onclick="menutoggle()">
        </div>

        <div class="small-container cart-page">
            <?php if (isset($_SESSION['order_success'])): ?>
                <div class="order-success">
                    <i class="fa fa-check-circle"></i> Your order has been placed successfully!
                </div>
                <?php unset($_SESSION['order_success']); ?>
            <?php elseif (isset($_SESSION['order_error'])): ?>
                <div class="order-error">
                    <i class="fa fa-exclamation-circle"></i> <?= $_SESSION['order_error'] ?>
                </div>
                <?php unset($_SESSION['order_error']); ?>
            <?php endif; ?>

            <table>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
                <?php
                $total = 0;
                if (!empty($_SESSION['cart'])):
                    foreach ($_SESSION['cart'] as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                ?>
                <tr>
                    <td>
                        <div class="cart-info">
                            <img src="<?= htmlspecialchars($item['image']) ?>">
                            <div>
                                <p><?= htmlspecialchars($item['name']) ?></p>
                                <small>Price: $<?= number_format($item['price'], 2) ?></small>
                                <a href="cart.php?action=remove&id=<?= $item['id'] ?>">Remove</a>
                            </div>
                        </div>
                    </td>
                    <td>
                        <form method="post" action="cart.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
                            <button type="submit" class="btn-update">Update</button>
                        </form>
                    </td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php
                    endforeach;
                else:
                ?>
                <tr>
                    <td colspan="3">Your cart is empty</td>
                </tr>
                <?php endif; ?>
            </table>

            <div class="total-price">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td>$<?= number_format($total, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Vat (15%)</td>
                        <td>$<?= number_format($total * 0.15, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td>$<?= number_format($total * 1.15, 2) ?></td>
                    </tr>
                </table>
            </div>

            <?php if (!empty($_SESSION['cart'])): ?>
            <div class="order-message-section">
                <h3>Order Details</h3>
                <form method="post" action="cart.php">
                    <div class="form-group">
                        <label for="buyer-message">Message to sellers (optional):</label>
                        <textarea id="buyer-message" name="buyer_message" class="form-control" rows="3" 
                                placeholder="Any special instructions for the sellers..."></textarea>
                    </div>
                    <input type="hidden" name="action" value="place_order">
                    <button type="submit" class="place-order-btn">Place Order</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="browsing-section">
                <div class="browsing-message">Still browsing?</div>
                <a href="products.php" class="continue-btn">Continue Shopping</a>
            </div>
        </div>

        <div class="footer">
            <!-- Your existing footer code -->
        </div>
    </div>

    <script>
        var MenuItems = document.getElementById("MenuItems");
        MenuItems.style.maxHeight = "0px";
        
        function menutoggle() {
            if (MenuItems.style.maxHeight == "0px") {
                MenuItems.style.maxHeight = "200px";
            } else {
                MenuItems.style.maxHeight = "0px";
            }
        }
    </script>
</body>
</html>