<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../includes/Product.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$message = '';

// Handle product actions
if($_POST) {
    if(isset($_POST['add_product'])) {
        $product->name = $_POST['name'];
        $product->description = $_POST['description'];
        $product->price = $_POST['price'];
        $product->category_id = $_POST['category_id'];
        $product->stock_quantity = $_POST['stock_quantity'];
        $product->featured = isset($_POST['featured']) ? 1 : 0;
        $product->image_url = $_POST['image_url'];
        
        // Simple insert - in production, you'd handle file uploads
        if($product->create()) {
            $message = "Product added successfully!";
        } else {
            $message = "Failed to add product.";
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
            
            <div class="add-product-form">
                <h2>Add New Product</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Price *</label>
                            <input type="number" name="price" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity *</label>
                            <input type="number" name="stock_quantity" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="text" name="image_url" placeholder="assets/images/product.jpg">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="featured" value="1">
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
                    <?php while ($product_row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="product-card-admin">
                        <img src="<?php echo $product_row['image_url'] ?: '../assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $product_row['name']; ?>" class="product-image-admin">
                        <h3><?php echo $product_row['name']; ?></h3>
                        <p class="price">$<?php echo number_format($product_row['price'], 2); ?></p>
                        <p class="stock">Stock: <?php echo $product_row['stock_quantity']; ?></p>
                        <p class="category">Category ID: <?php echo $product_row['category_id']; ?></p>
                        <?php if($product_row['featured']): ?>
                            <span class="featured-badge">⭐ Featured</span>
                        <?php endif; ?>
                        <div class="product-actions">
                            <a href="edit_product.php?id=<?php echo $product_row['id']; ?>" class="button_1 btn-sm">Edit</a>
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
</body>
</html>