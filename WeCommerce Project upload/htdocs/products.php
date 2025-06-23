<?php
// Database connection at the very top with no whitespace before it
$host = "sql305.infinityfree.com";        
$db   = "if0_39218569_redstore_db";       
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT";       

// Create connections to both databases
$redstore_conn = new mysqli($host, $user, $pass, "if0_39218569_redstore_db");
$users_conn = new mysqli($host, $user, $pass, "if0_39218569_users_db");

// Check connections
if ($redstore_conn->connect_error) {
    die("Redstore Connection failed: " . $redstore_conn->connect_error);
}
if ($users_conn->connect_error) {
    die("Users Connection failed: " . $users_conn->connect_error);
}

// Default sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$order_by = "p.created_at DESC";

switch($sort) {
    case 'price_asc':
        $order_by = "p.price ASC";
        break;
    case 'price_desc':
        $order_by = "p.price DESC";
        break;
    case 'name_asc':
        $order_by = "p.product_name ASC";
        break;
    case 'name_desc':
        $order_by = "p.product_name DESC";
        break;
    default:
        $order_by = "p.created_at DESC";
}

// Get all products from database with sorting
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY $order_by";
$result = $redstore_conn->query($sql);

// Pre-fetch seller names
$seller_names = [];
if ($result->num_rows > 0) {
    $seller_ids = [];
    while($row = $result->fetch_assoc()) {
        $seller_ids[] = $row['seller_id'];
    }
    $result->data_seek(0); // Reset pointer
    
    if (!empty($seller_ids)) {
        $ids_str = implode(',', array_unique($seller_ids));
        $seller_query = $users_conn->prepare("SELECT id, name FROM users WHERE id IN ($ids_str)");
        $seller_query->execute();
        $seller_result = $seller_query->get_result();
        
        while($seller = $seller_result->fetch_assoc()) {
            $seller_names[$seller['id']] = $seller['name'];
        }
    }
}

$categories = [];
$cat_query = "SELECT * FROM categories";
$cat_result = $redstore_conn->query($cat_query);
if ($cat_result->num_rows > 0) {
    while($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width-device-width, initial-scale=1.0">
        <title>All Products - RedStore</title>
        <link rel="stylesheet" href="CSS/style.css"> 
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <style>
            .product-specs {
                margin-top: 10px;
                font-size: 14px;
            }
            .product-specs ul {
                padding-left: 20px;
                margin: 5px 0;
            }
            .product-specs li {
                margin-bottom: 3px;
            }
            .seller-info {
                font-size: 14px;
                margin-top: 5px;
                color: #555;
            }
            .seller-info strong {
                color: #333;
            }
        </style>
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
                        <li><a href="sellinghome.php">Selling</a></li>
                        <li><a href="">Contact</a></li>
                        <li><a href="login.php">Account</a></li>
                    </ul>
                </nav>
                <a href="cart.php">
                    <img src="images/cart.png" alt="cart" width=30px height=30px>
                    <span class="cart-count">
                        <?php 
                        session_start();
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
        </div>

        <div class="small container">
            <div class="row row-2">
                <h2>All Products</h2>
                <form method="get" action="products.php" id="sortForm">
                    <select name="sort" onchange="document.getElementById('sortForm').submit()">
                        <option value="default" <?php echo ($sort == 'default') ? 'selected' : ''; ?>>Default Sorting</option>
                        <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                        <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                    </select>
                </form>
            </div>

            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $seller_name = isset($seller_names[$row['seller_id']]) ? 
                            $seller_names[$row['seller_id']] : 
                            'Seller #'.$row['seller_id'];
                    ?>
                        <div class="col-4">
                            <img src="<?php echo $row['image_path']; ?>" alt="<?php echo $row['product_name']; ?>">
                            <h4><?php echo $row['product_name']; ?></h4>
                            <div class="rating">
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star" aria-hidden="true"></i>
                                <i class="fa fa-star-o" aria-hidden="true"></i>
                            </div>
                            <p>$<?php echo number_format($row['price'], 2); ?></p>
                            
                            <!-- Seller Information -->
                            <div class="seller-info">
                                <strong>Sold by:</strong> <?php echo htmlspecialchars($seller_name); ?>
                            </div>
                            
                            <!-- Product Specifications -->
                            <?php if (!empty($row['specifications'])): ?>
                            <div class="product-specs">
                                <?php 
                                $specs = json_decode($row['specifications'], true);
                                if (!empty($specs)): ?>
                                    <ul>
                                        <?php foreach($specs as $key => $value): ?>
                                            <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="post" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="name" value="<?php echo $row['product_name']; ?>">
                                <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                <input type="hidden" name="image" value="<?php echo $row['image_path']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn">Add to Cart</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback to static products if database is empty -->
                    <div class="col-4">
                        <img src="Images/product-1.jpg">
                        <h4>Red Printed T-shirt</h4>
                        <div class="rating">
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star-o" aria-hidden="true"></i>
                        </div>
                        <p>$50.00</p>
                        <div class="seller-info">
                            <strong>Sold by:</strong> Example Seller
                        </div>
                        <form method="post" action="cart.php">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="1">
                            <input type="hidden" name="name" value="Red Printed T-shirt">
                            <input type="hidden" name="price" value="50.00">
                            <input type="hidden" name="image" value="Images/product-1.jpg">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn">Add to Cart</button>
                        </form>
                    </div>
                    <!-- Add more static products as needed -->
                <?php endif; ?>
            </div>

            <div class="page-btn">
                <span>1</span>
                <span>2</span>
                <span>3</span>
                <span>4</span>
                <span>&#8594;</span>
            </div>
        </div>

        <div class="footer">
            <div class="container">
                <div class="row">
                    <div class="footer-col-1">
                        <h3>Download Our App</h3>
                        <p>Download App for Android and Ios mobile phone.</p>
                        <div class="app-logo">
                            <img src="images/play-store.png">
                            <img src="images/app-store.png">
                        </div>   
                    </div>
                    <div class="footer-col-2">
                        <img src="images/logo.png.png">
                        <p>Our purpose is To Sustainably Make the lives of those without access to a store to share the products with the world.</p>
                    </div>
                    <div class="footer-col-3">
                        <h3>Useful Links</h3>
                        <ul>
                            <li>Coupons</li>
                            <li>Blog Post</li>
                            <li>Return Policy</li>
                            <li>Join Affiliate</li>
                        </ul>
                    </div>
                    <div class="footer-col-4">
                        <h3>Follow Us</h3>
                        <ul>
                            <li>Facebook</li>
                            <li>Twitter</li>
                            <li>Instagram</li>
                            <li>Youtube</li>
                        </ul>
                    </div>
                </div>
                <hr>
                <a href="" class="copyright">Copyright 2025 - Made with ❤️ by Mohammed Kareem Khan</a>
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
<?php
$redstore_conn->close();
$users_conn->close();
?>