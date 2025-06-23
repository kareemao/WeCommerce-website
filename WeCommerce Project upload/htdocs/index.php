<?php
session_start();
// Database connection at the very top

$host = "sql305.infinityfree.com";        
$db   = "if0_39218569_redstore_db";       
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT";       


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Get featured products (limit to 4)
$featured_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = 1 
                ORDER BY p.created_at DESC 
                LIMIT 4";
$featured_result = $conn->query($featured_sql);

// Get latest products (limit to 8)
$latest_sql = "SELECT p.*, c.name as category_name 
              FROM products p 
              JOIN categories c ON p.category_id = c.id 
              ORDER BY p.created_at DESC 
              LIMIT 8";
$latest_result = $conn->query($latest_sql);
?>
<!DOCTYPE html>
<html lang = "en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeCommerce | The #1 Ecommerce Website </title>
    <link rel="stylesheet" href="CSS/style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>

<!--making header-->
<div class="header">
    <header style="position: relative;">

    <?php if (isset($_SESSION['name'])): ?>
    <div class="welcome-container">
        <p class="welcome-msg">Welcome, <?= htmlspecialchars($_SESSION['name']); ?>!</p>
    <a href="logout.php" class="logout-btn">Logout</a>
    </div>
<?php endif; ?>

    <?php if (isset($_SESSION['email'])): ?>
    <div class="top-right-icons <?= $hasUnreadMessages ? 'has-messages' : '' ?>">
        <a href="message.php" class="message-icon" title="Messages">
        <i class="fas fa-comment-dots"></i>
        </a>
        <a href="account.php" class="account-link"></a>
    </div>
<?php endif; ?>
</header>
     

    <div class="container">

        <!--making navigation bar consist of logo and menu links-->
        <div class="navbar">
            <div class="logo">
                <a href="index.php"><img src="images/logo.png.png" alt="WeCommercelogo" width=125px></a>
            </div>
           

            <!--menu-->
            <nav> 
                <ul id="MenuItems">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="sellinghome.php">Selling</a></li>
                    <li><a href="login.php">Account</a></li>

                   
                </ul>
            </nav>

            <a href="cart.php"><img src="images/cart.png" alt="cart" width=30px height=30px></a>
            <img src="images/menu.png" class="menu-icon" onclick="menutoggle()">
        </div>

        <!--making another section in header class container-->
        <div class="row">

            <!--1st column for heading button and text-->
            <div class="col-2">
                <h1>Buying and Selling<br>Like No Other!</h1>
                <p>Success isn't always about greatness. It's about consistency.
                Consistent <br>hard work gains success. Greatness will come. <br>Proudly helping South Africans inspire
                </p>
                <a href="products.php" class="btn">Explore Now &#8594;</a>
            </div>

            <!--2nd for image-->
            <div class="col-2">
                <img src="images/pic1.svg" alt="header image">
            </div>

        </div>
    </div>
</div>




    <!--Featured categories-->

    <div class="categories">

        <!--we will create 3 column for 3 images at a time in single row like we did above by making 2 columns-->

        <!--small container class contains row which consist of whole columns sections then col-3 which consist of col of images-->
        <div class="small container">,<!--container class to edit images section-->
            <div class="row">

                <!--3 columns for 3 images-->
                <div class="col-3">
                    <img src="images/category-1.1.jpg" alt="">
                </div>
                <div class="col-3">
                    <img src="images/category-2.1.jpg" alt="">
                </div>
                <div class="col-3">
                    <img src="images/category-3.1.jpg" alt="">
                </div>
    
            </div>
        </div>

    </div>

     <div class="small container">
            
            <div class="row">
                <?php if ($featured_result && $featured_result->num_rows > 0): ?>
                    <?php while($row = $featured_result->fetch_assoc()): ?>
                        <div class="col-4">
                            <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            </a>
                            <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                                <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                            </a>
                            <div class="rating">
                                <?php 
                                $rating = isset($row['rating']) ? $row['rating'] : 4;
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                                
                                for ($i = 0; $i < $full_stars; $i++): ?>
                                    <i class="fa fa-star" aria-hidden="true"></i>
                                <?php endfor; ?>
                                
                                <?php if ($has_half_star): ?>
                                    <i class="fa fa-star-half-o" aria-hidden="true"></i>
                                <?php endif; ?>
                                
                                <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                    <i class="fa fa-star-o" aria-hidden="true"></i>
                                <?php endfor; ?>
                            </div>
                            <p>$<?php echo number_format($row['price'], 2); ?></p>
                            <form method="post" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['product_name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($row['image_path']); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn">Add to Cart</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback static content if no featured products or query failed -->
                   
                    <!-- Add more static fallbacks as needed -->
                <?php endif; ?>
            </div>

            <!-- Latest Products -->
            <h2 class="title">Newest Products</h2>
            <div class="row">
                <?php if ($latest_result && $latest_result->num_rows > 0): ?>
                    <?php $count = 0; ?>
                    <?php while($row = $latest_result->fetch_assoc()): ?>
                        <?php if ($count % 4 == 0 && $count != 0): ?>
                            </div><div class="row">
                        <?php endif; ?>
                        <div class="col-4">
                            <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            </a>
                            <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                                <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                            </a>
                            <div class="rating">
                                <?php 
                                $rating = isset($row['rating']) ? $row['rating'] : 4;
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                                
                                for ($i = 0; $i < $full_stars; $i++): ?>
                                    <i class="fa fa-star" aria-hidden="true"></i>
                                <?php endfor; ?>
                                
                                <?php if ($has_half_star): ?>
                                    <i class="fa fa-star-half-o" aria-hidden="true"></i>
                                <?php endif; ?>
                                
                                <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                    <i class="fa fa-star-o" aria-hidden="true"></i>
                                <?php endfor; ?>
                            </div>
                            <p>$<?php echo number_format($row['price'], 2); ?></p>
                            <form method="post" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['product_name']); ?>">
                                <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($row['image_path']); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn">Add to Cart</button>
                            </form>
                        </div>
                        <?php $count++; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback static content if no latest products or query failed -->
                    <div class="col-4">
                        <img src="images/product-5.jpg">
                        <h4>Red Printed T-shirt</h4>
                        <div class="rating">
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <i class="fa fa-star-o" aria-hidden="true"></i>
                        </div>
                        <p>R50.00</p>
                    </div>
                    <!-- Add more static fallbacks as needed -->
                <?php endif; ?>
            </div>
        </div>
    <!--offer-->
    <div class="offer">
        <div class="container">
            <div class="row">
                
                <div class="col-2">
                   <img src="exclusive1.png" class="offer-img"> 
                </div>

                <div class="col-2">
                    <p>Exclusively available on WeCommerce</p>
                    <h1>Homemade Skincare</h1>
                    <small>
                        From the walls of a home comes these organic natural skin care products, toners that will keep your skin hydrated and beautiful
                    </small>
                    <a href="products.php" class="btn">Buy Now &#8594;</a><!--where &#8594 is the code of arrow icon in explore now button-->
                    
                </div>
            </div>
        </div>
    </div>

    <!----------------testimonials-------------->

    <div class="testimonial">
        <div class="small container"><!--section-->
            <div class="row"><!--row-->
                
                <!--3 comments-->
                <div class="col-3"><!--3 columns-->

                    <!--add " or left quote before comment-->
                    <i class="fa fa-quote-left" aria-hidden="true"></i>
                    <p>
                        Ever since I started using this platform to share my beautiful products with the world, I have never looked back
                    </p>
                    <div class="rating"><!--add stars for rating of product from font awesome 4-->
                        <i class="fa fa-star" aria-hidden="true"></i><!--add 4 black start and one transparent star to show 4 out of 5 rating-->
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star-half-o" aria-hidden="true"></i>
                     </div>
                     <img src="images/user-1.png"><!--image of person-->
                     <h3>Hannah Smith</h3><!--name of person-->
   
                </div>

                <div class="col-3"><!--3 columns-->

                    <!--add " or left quote before comment-->
                    <i class="fa fa-quote-left" aria-hidden="true"></i>
                    <p>
                        I have been making clothes and designing my own brand since I was a child, with this platform I have been able to truly live out my dream
                    </p>
                    <div class="rating"><!--add stars for rating of product from font awesome 4-->
                        <i class="fa fa-star" aria-hidden="true"></i><!--add 4 black start and one transparent star to show 4 out of 5 rating-->
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star-half-o" aria-hidden="true"></i>
                     </div>
                     <img src="images/user-2.png"><!--image of person-->
                     <h3>Kyle Jacobs</h3><!--name of person-->
   
                </div>

                <div class="col-3"><!--3 columns-->

                    <!--add " or left quote before comment-->
                    <i class="fa fa-quote-left" aria-hidden="true"></i>
                    <p>
                        Supporting local communities and discovering hidden gems is the best thing about WeCommerce
                    </p>
                    <div class="rating"><!--add stars for rating of product from font awesome 4-->
                        <i class="fa fa-star" aria-hidden="true"></i><!--add 4 black start and one transparent star to show 4 out of 5 rating-->
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star" aria-hidden="true"></i>
                        <i class="fa fa-star-half-o" aria-hidden="true"></i>
                     </div>
                     <img src="images/user-3.png"><!--image of person-->
                     <h3>Aneesa Adam</h3><!--name of person-->
   
                </div>

            </div>
        </div>
    </div>

    <!----------brands-------------->

    <div class="brands">
        <div class="small container">
            <div class="row">
                <div class="col-5">
                     <img src="images/logo-godrej.png">
                </div>

                <div class="col-5">
                    <img src="images/logo-oppo.png">
               </div>


               <div class="col-5">
                <img src="images/logo-coca-cola.png">
                </div>


                <div class="col-5">
                 <img src="images/logo-paypal.png">
                </div>


                 <div class="col-5">
                   <img src="images/logo-philips.png">
                </div>

            </div>
        </div>
    </div>
     
    <!-------footer------->
    <div class="footer">
        <div class="container"><!-- it will follow same styling as of header-->
            <div class="row">
                
                <!--it contains 4 columns col1 contains text and col2 contain image col3 contains useful links and col4 contains social media links-->
                <div class="footer-col-1">
                    <h3>Download Our App</h3>
                    <p>Download App for Android and Ios mobile phone.</p>
                   
                   <!---add images of play store and appstore in 1st column-->
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
            <!--add horizontal line and copyright text along with clickable link-->
            <hr>
            <a href="" class="copyright">Copyright 2025   - Made with ❤️ by Mohammed Kareem Khan</a>
        </div>
    </div>         

    <!-----------------JS----------------------->
    <script>
        //declare variable MenuItems which take ul having id "MenuItems"
        
        var MenuItems = document.getElementById("MenuItems");
        MenuItems.style.maxHeight = "0px";//by default, we have set menu or dropdown menu height to 0px, means it is close by default
        
        function menutoggle()//this is the function which we have called above in nav which works on small devices and users click on it
        {
            if (MenuItems.style.maxHeight =="0px")//when user click on it and if it is closed or its height is 0px, then it will open or it should have heigt of 200px upon clicking
            {
                MenuItems.style.maxHeight = "200px"
            }
            else//if user not clicked or it has already opened, then it will upon clicking again closed
            {
                MenuItems.style.maxHeight = "0px" 
            }
        
        }
    </script>    
    
    </body>

</html>