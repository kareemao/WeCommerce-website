<?php
session_start(); // Make sure session is started

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to list a product.");
}

// Database connection
$host = "sql305.infinityfree.com";        
$db   = "if0_39218569_redstore_db";       
$user = "if0_39218569";                  
$pass = "cQNv6p985h0xT";       


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $product_name = $conn->real_escape_string($_POST['product_name']);
    $category_id = (int)$_POST['category'];
    $price = (float)$_POST['price'];
    $description = $conn->real_escape_string($_POST['description']);
    $seller_id = (int)$_SESSION['user_id'];

    // Process specifications
    $specifications = [];
    if (isset($_POST['specifications'])) {
        $keys = array_filter($_POST['specifications']['key']);
        $values = array_filter($_POST['specifications']['value']);
        
        // Pair keys with values
        foreach ($keys as $index => $key) {
            if (!empty($key) && isset($values[$index])) {
                $specifications[$key] = $values[$index];
            }
        }
    }
    $specs_json = $conn->real_escape_string(json_encode($specifications));

    // Handle file upload
    $target_dir = "uploads/";
    $file_extension = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
        // Insert into database with seller_id
        $sql = "INSERT INTO products (product_name, category_id, price, description, specifications, image_path, seller_id) 
                VALUES ('$product_name', $category_id, $price, '$description', '$specs_json', '$target_file', $seller_id)";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Product listed successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    } else {
        $error = "Sorry, there was an error uploading your file.";
    }
}

// Fetch categories from database
$categories = [];
$cat_query = "SELECT * FROM categories";
$cat_result = $conn->query($cat_query);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Your Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .specification-item {
            margin-bottom: 15px;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container bg-white">
            <h2 class="text-center mb-4">List Your Product</h2>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="price" class="form-label">Price (R)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="product_image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" required>
                    <img id="imagePreview" class="preview-image d-none" src="#" alt="Preview">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Specifications</label>
                    <div id="specifications-container">
                        <div class="specification-item row g-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="specifications[key][]" placeholder="Specification name">
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="specifications[value][]" placeholder="Specification value">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 remove-spec">Remove</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-spec" class="btn btn-secondary mt-2">Add Specification</button>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">List Product</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.getElementById('product_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Add/remove specifications
        document.getElementById('add-spec').addEventListener('click', function() {
            const container = document.getElementById('specifications-container');
            const newItem = document.createElement('div');
            newItem.className = 'specification-item row g-2 mt-2';
            newItem.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" name="specifications[key][]" placeholder="Specification name">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="specifications[value][]" placeholder="Specification value">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100 remove-spec">Remove</button>
                </div>
            `;
            container.appendChild(newItem);
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-spec')) {
                e.target.closest('.specification-item').remove();
            }
        });
    </script>
</body>
</html>