<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

$host = "sql305.infinityfree.com";        
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT"; 

// Establish connections
try {
    $redstore_conn = new mysqli($host, $user, $pass, "if0_39218569_redstore_db");
    $users_conn = new mysqli($host, $user, $pass, "if0_39218569_users_db");

    // Check for database connection errors
    if ($redstore_conn->connect_error) {
        throw new Exception("Redstore DB connection failed: " . $redstore_conn->connect_error);
    }
    if ($users_conn->connect_error) {
        throw new Exception("Users DB connection failed: " . $users_conn->connect_error);
    }

    // Set charset for both connections
    if (!$redstore_conn->set_charset("utf8mb4")) {
        error_log("Error setting charset for redstore: " . $redstore_conn->error);
    }
    if (!$users_conn->set_charset("utf8mb4")) {
        error_log("Error setting charset for users: " . $users_conn->error);
    }

    // Initialize variables with defaults
    $current_user_id = null;
    $user_name = "Guest";
    $user_products = [];
    $active_listings = 0;
    $monthly_revenue = 0;
    $total_orders = 0;
    $seller_rating = 4.7;

    // Debug session data
    error_log("Session data: " . print_r($_SESSION, true));

    // Get current user ID
    if (isset($_SESSION['user_id'])) {
        $current_user_id = (int)$_SESSION['user_id'];
        error_log("Using session user_id: $current_user_id");
    } elseif (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];
        error_log("Looking up user by email: $email");
        
        try {
            $user_query = $users_conn->prepare("SELECT id, name FROM users WHERE email = ?");
            if (!$user_query) {
                throw new Exception("Prepare failed: " . $users_conn->error);
            }
            
            $user_query->bind_param("s", $email);
            if (!$user_query->execute()) {
                throw new Exception("Execute failed: " . $user_query->error);
            }
            
            $user_result = $user_query->get_result();
            if ($user_result->num_rows > 0) {
                $user_row = $user_result->fetch_assoc();
                $current_user_id = (int)$user_row['id'];
                $_SESSION['user_id'] = $current_user_id;
                $user_name = $user_row['name'];
                error_log("Found user ID: $current_user_id, Name: $user_name");
            } else {
                error_log("No user found with email: $email");
            }
            $user_query->close();
        } catch (Exception $e) {
            error_log("User lookup error: " . $e->getMessage());
        }
    }

    // Get user's products only if we have a valid user ID
    if ($current_user_id) {
        error_log("Fetching products for user ID: $current_user_id");
        
        try {
            $sql = "SELECT products.*, categories.name AS category_name 
                    FROM products 
                    LEFT JOIN categories ON products.category_id = categories.id 
                    WHERE products.seller_id = ? 
                    ORDER BY products.created_at DESC";
                    
            $stmt = $redstore_conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $redstore_conn->error);
            }
            
            $stmt->bind_param("i", $current_user_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("Found products: " . $result->num_rows);
            
            if ($result && $result->num_rows > 0) {
                $user_products = $result->fetch_all(MYSQLI_ASSOC);
                error_log("First product: " . print_r($user_products[0] ?? null, true));
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Products query error: " . $e->getMessage());
            $_SESSION['error'] = "Error loading products. Please try again.";
        }

        // Seller stats - using redstore connection for product/order data
        try {
            // Active listings
            $active_sql = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND stock > 0";
            $active_stmt = $redstore_conn->prepare($active_sql);
            if ($active_stmt && $active_stmt->bind_param("i", $current_user_id) && $active_stmt->execute()) {
                $active_result = $active_stmt->get_result();
                $row = $active_result->fetch_assoc();
                $active_listings = $row['count'] ?? 0;
                $active_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Active listings error: " . $e->getMessage());
        }

        try {
            // Monthly revenue
            $revenue_sql = "SELECT SUM(oi.price * oi.quantity) as revenue 
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            WHERE o.seller_id = ?
                            AND MONTH(o.order_date) = MONTH(CURRENT_DATE())";
            $revenue_stmt = $redstore_conn->prepare($revenue_sql);
            if ($revenue_stmt && $revenue_stmt->bind_param("i", $current_user_id) && $revenue_stmt->execute()) {
                $revenue_result = $revenue_stmt->get_result();
                $monthly_revenue = $revenue_result->fetch_assoc()['revenue'] ?? 0;
                $revenue_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Revenue query error: " . $e->getMessage());
        }

        try {
            // Total orders
            $orders_sql = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ?";
            $orders_stmt = $redstore_conn->prepare($orders_sql);
            if ($orders_stmt && $orders_stmt->bind_param("i", $current_user_id) && $orders_stmt->execute()) {
                $orders_result = $orders_stmt->get_result();
                $row = $orders_result->fetch_assoc();
                $total_orders = $row['count'] ?? 0;
                $orders_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Orders query error: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    error_log("Critical error: " . $e->getMessage());
    die("A system error occurred. Please try again later. Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | WeCommerce</title>
    <link rel="stylesheet" href="sellerstyle.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
            <!-- Navigation bar -->
            <div class="navbar">
                <div class="logo">
                    <img src="images/logo.png.png" alt="RedStore logo" width=125px>
                </div>
                <nav> 
                    <ul id="MenuItems">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="">Contact</a></li>
                        <li><a href="login.php">Account</a></li>
                    </ul>
                </nav>
                <img src="images/cart.png" alt="cart" width=30px height=30px>
            </div>
        </div>
   
    <!-- Header with Background -->
    <header class="header">
        <div class="container1">
            <?php if (isset($_SESSION['name']) || isset($_SESSION['email'])): ?>
            <div class="welcome-container">
                <p class="welcome-msg">
                    Welcome, 
                    <?php 
                    if (isset($_SESSION['name'])) {
                        echo htmlspecialchars($_SESSION['name']);
                    } elseif (isset($_SESSION['email'])) {
                        echo htmlspecialchars($_SESSION['email']);
                    } 
                    ?>!
                </p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            <?php endif; ?>
            
            <div class="header-content">
                <h1>Welcome to Your Seller Dashboard</h1>
                <p>Manage your products, track your sales, and grow your business with our seller tools</p>
                <a href="selling.php" class="btn">
                    <i class="fas fa-plus-circle"></i>List a Product
                </a>
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <div class="container">
        <div class="stats-section">
            <div class="stat-card">
                <i class="fas fa-box-open"></i>
                <div class="number"><?php echo $active_listings; ?></div>
                <div class="label">Active Listings</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="number">$<?php echo number_format($monthly_revenue, 2); ?></div>
                <div class="label">Monthly Revenue</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart"></i>
                <div class="number"><?php echo $total_orders; ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <div class="number"><?php echo $seller_rating; ?></div>
                <div class="label">Seller Rating</div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="section-title">
                <h2>Your Listed Products</h2>
            </div>
            
            <!-- PHP Product Listing Integration -->
            <div class="products-grid">
                <?php if (!$current_user_id): ?>
                    <p class="empty-message">Please log in to view your products.</p>
                <?php elseif (empty($user_products)): ?>
                    <p class="empty-message">No products listed yet. Start by listing your first product!</p>
                <?php else: ?>
                    <?php foreach ($user_products as $product): ?>
                        <?php
                        // Determine badge status based on stock
                        $badge_class = "active";
                        $badge_text = "Active";
                        if ($product['stock'] <= 0) {
                            $badge_class = "sold";
                            $badge_text = "Sold Out";
                        } elseif ($product['stock'] < 5) {
                            $badge_class = "pending";
                            $badge_text = "Low Stock";
                        }
                        
                        // Format rating stars
                        $rating = isset($product['rating']) ? $product['rating'] : 4;
                        $full_stars = floor($rating);
                        $has_half_star = ($rating - $full_stars) >= 0.5;
                        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                        
                        $stars_html = '';
                        for ($i = 0; $i < $full_stars; $i++) {
                            $stars_html .= '<i class="fas fa-star"></i>';
                        }
                        if ($has_half_star) {
                            $stars_html .= '<i class="fas fa-star-half-alt"></i>';
                        }
                        for ($i = 0; $i < $empty_stars; $i++) {
                            $stars_html .= '<i class="far fa-star"></i>';
                        }
                        ?>
                        <div class="product-card">
                            <div class="product-badge"><?php echo $badge_text; ?></div>
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <span class="product-price">$<?php echo number_format($product['price'], 2); ?></span>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-meta">
                                    <div class="product-rating">
                                        <?php echo $stars_html; ?>
                                        (<?php echo isset($product['rating_count']) ? $product['rating_count'] : '0'; ?>)
                                    </div>
                                    <div class="product-actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="action-btn" onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>© 2023 Seller Dashboard. All rights reserved.</p>
                <p>Designed with ❤️ for WeCommerce sellers</p>
            </div>
        </div>
    </footer>

    <script>
        // Simple hover effects
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
        
        // Button animation
        document.querySelector('.btn')?.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        document.querySelector('.btn')?.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    </script>
</body>

    <!-- [Your navigation/header remains the same] -->

    <!-- Debug information (can be shown during development) -->
    <div class="debug-info">
        <h3>Debug Information</h3>
        <p><strong>User ID:</strong> <?php echo $current_user_id ?? 'Not set'; ?></p>
        <p><strong>Products Found:</strong> <?php echo count($user_products); ?></p>
        <p><strong>Last SQL Error:</strong> <?php echo $conn->error ?? 'None'; ?></p>
    </div>

    <!-- Error message display -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- [Rest of your HTML remains the same] -->

    <script>
        // Enhanced error handling for frontend
        document.addEventListener('DOMContentLoaded', function() {
            // Log any JavaScript errors to console
            window.onerror = function(message, source, lineno, colno, error) {
                console.error("Error:", message, "at", source, lineno + ":" + colno);
                return true;
            };
            
            // Debug: Uncomment to show debug info
            // document.querySelector('.debug-info').style.display = 'block';
        });
    </script>
</body>
</html>


<?php
// Close connections
if (isset($redstore_conn)) {
    $redstore_conn->close();
}
if (isset($users_conn)) {
    $users_conn->close();
}
?>