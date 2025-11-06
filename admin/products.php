<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../includes/Product.php";
require_once "../includes/ImageUploader.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$imageUploader = new ImageUploader();

$message = '';
$error = '';

// Handle product actions
if($_POST) {
    if(isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        try {
            $db->beginTransaction();
            
            // Create product first
            $product->name = $name;
            $product->description = $description;
            $product->price = $price;
            $product->category_id = $category_id;
            $product->stock_quantity = $stock_quantity;
            $product->featured = $featured;
            $product->image_url = null; // Will be set by uploaded images
            
            if($product->create()) {
                $product_id = $product->id;
                $image_url = null;
                
                // Handle image upload
                if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = $imageUploader->uploadImage($_FILES['product_image'], $product_id);
                    
                    if($upload_result['success']) {
                        // Add image to database and set as primary
                        $product->addImage($product_id, $upload_result['relative_path'], true);
                        $image_url = $upload_result['relative_path'];
                        
                        // Update product with image URL for backward compatibility
                        $product->updateWithImage($product_id, $name, $description, $price, $category_id, $stock_quantity, $featured, $image_url);
                    } else {
                        $error = "Product created but image upload failed: " . implode(', ', $upload_result['errors']);
                    }
                }
                
                $db->commit();
                $message = $error ? $error : "Product added successfully!";
                $error = '';
                
                // Clear form on success
                if(!$error) {
                    $_POST = array();
                }
            } else {
                $db->rollBack();
                $error = "Failed to create product.";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get all products
$products = $product->read();

// Get categories for dropdown
$categories_query = "SELECT * FROM categories";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - TechStore Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: #35424a;
            color: white;
            padding: 20px;
        }
        
        .admin-sidebar h2 {
            color: #e8491d;
            margin-bottom: 30px;
        }
        
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .admin-sidebar li {
            margin-bottom: 10px;
        }
        
        .admin-sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-sidebar a:hover, .admin-sidebar a.active {
            background: #e8491d;
        }
        
        .admin-main {
            padding: 30px;
            background: #f4f4f4;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .product-card-admin {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .product-image-admin {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .add-product-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #35424a;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-button {
            display: block;
            padding: 10px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-button:hover {
            border-color: #e8491d;
            background: #fff;
        }
        
        .image-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e8491d;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Products</h1>
            </div>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="add-product-form">
                <h2>Add New Product</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Price *</label>
                            <input type="number" name="price" step="0.01" value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock_quantity" value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : '0'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Image</label>
                            <div class="file-input-wrapper">
                                <div class="file-input-button">
                                    <span id="file-name">Choose product image (Max 5MB)</span>
                                </div>
                                <input type="file" name="product_image" id="product_image" accept="image/*" onchange="updateFileName(this)">
                            </div>
                            <small>Supported formats: JPG, PNG, GIF, WEBP</small>
                            <div class="image-preview" id="image-preview"></div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="featured" value="1" <?php echo (isset($_POST['featured']) && $_POST['featured']) ? 'checked' : ''; ?>>
                                <label>Featured Product</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_product" class="button_1">Add Product</button>
                </form>
            </div>
            
            <h2>All Products (<?php echo $products->rowCount(); ?>)</h2>
            
            <?php if($products->rowCount() > 0): ?>
                <div class="products-grid">
                    <?php while ($product_row = $products->fetch(PDO::FETCH_ASSOC)): 
                        $product_images = $product->getImages($product_row['id']);
                        $image_count = $product_images->rowCount();
                    ?>
                    <div class="product-card-admin">
                        <?php if($image_count > 0): ?>
                            <span class="image-count"><?php echo $image_count; ?> img</span>
                        <?php endif; ?>
                        
                        <img src="<?php 
                            $primary_image = $product->getPrimaryImage($product_row['id']);
                            if($primary_image) {
                                echo '../' . $primary_image['image_path'];
                            } else {
                                echo $product_row['image_url'] ? '../' . $product_row['image_url'] : '../assets/images/placeholder.jpg';
                            }
                        ?>" alt="<?php echo $product_row['name']; ?>" class="product-image-admin">
                        
                        <h3><?php echo $product_row['name']; ?></h3>
                        <p class="price">$<?php echo number_format($product_row['price'], 2); ?></p>
                        <p class="stock">Stock: <?php echo $product_row['stock_quantity']; ?></p>
                        <p class="category">Category ID: <?php echo $product_row['category_id']; ?></p>
                        
                        <?php if($product_row['featured']): ?>
                            <span style="color: #e8491d; font-weight: bold;">⭐ Featured</span>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <a href="edit_product.php?id=<?php echo $product_row['id']; ?>" class="button_1 btn-sm">Edit</a>
                            <a href="manage_images.php?id=<?php echo $product_row['id']; ?>" class="button_1 btn-sm" style="background: #17a2b8;">Images</a>
                            <a href="delete_product.php?id=<?php echo $product_row['id']; ?>" class="button_1 btn-sm" style="background: #dc3545;" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; background: white; border-radius: 8px;">
                    <h3>No products found</h3>
                    <p>Add your first product using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Choose product image (Max 5MB)';
            document.getElementById('file-name').textContent = fileName;
            
            // Show image preview
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('image-preview').innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                document.getElementById('image-preview').innerHTML = '';
            }
        }
        
        // Validate file size before upload
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('product_image');
            if (fileInput.files[0]) {
                const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 5) {
                    e.preventDefault();
                    alert('File size must be less than 5MB');
                    return false;
                }
            }
        });
    </script>
</body>
</html>