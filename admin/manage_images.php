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

// Get product ID
$product_id = isset($_GET['id']) ? $_GET['id'] : 0;
if(!$product_id) {
    header("Location: products.php");
    exit();
}

// Get product info
$product->id = $product_id;
if(!$product->readOne()) {
    header("Location: products.php");
    exit();
}

$message = '';
$error = '';

// Handle image actions
if($_POST) {
    if(isset($_POST['upload_images'])) {
        // Handle multiple file uploads
        if(isset($_FILES['product_images'])) {
            $uploaded_count = 0;
            
            foreach($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['product_images']['name'][$key],
                        'type' => $_FILES['product_images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['product_images']['error'][$key],
                        'size' => $_FILES['product_images']['size'][$key]
                    ];
                    
                    $upload_result = $imageUploader->uploadImage($file, $product_id);
                    
                    if($upload_result['success']) {
                        $is_primary = ($uploaded_count === 0 && $product->getImages($product_id)->rowCount() === 0); // First image is primary if no images exist
                        $product->addImage($product_id, $upload_result['relative_path'], $is_primary);
                        $uploaded_count++;
                    }
                }
            }
            
            if($uploaded_count > 0) {
                $message = "Successfully uploaded $uploaded_count image(s)!";
            } else {
                $error = "No images were uploaded successfully.";
            }
        }
    } elseif(isset($_POST['set_primary'])) {
        $image_id = $_POST['image_id'];
        if($product->setPrimaryImage($product_id, $image_id)) {
            $message = "Primary image updated!";
        } else {
            $error = "Failed to update primary image.";
        }
    } elseif(isset($_POST['delete_image'])) {
        $image_id = $_POST['image_id'];
        $deleted_image = $product->deleteImage($image_id);
        
        if($deleted_image) {
            // Delete physical file
            $filename = basename($deleted_image['image_path']);
            $imageUploader->deleteImage($filename);
            $message = "Image deleted successfully!";
        } else {
            $error = "Failed to delete image.";
        }
    }
}

// Get product images
$product_images = $product->getImages($product_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Images - TechStore Admin</title>
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
        
        .upload-section, .images-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #35424a;
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
            padding: 20px;
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
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .image-card.primary {
            border-color: #e8491d;
            border-width: 2px;
        }
        
        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .image-actions {
            display: flex;
            gap: 5px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
        }
        
        .primary-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #e8491d;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .product-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .product-info img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-details h2 {
            margin: 0 0 5px 0;
            color: #35424a;
        }
        
        .product-details p {
            margin: 0;
            color: #666;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #e8491d;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
                <li><a href="products.php">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <a href="products.php" class="back-link">← Back to Products</a>
            
            <div class="admin-header">
                <h1>Manage Product Images</h1>
            </div>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Product Info -->
            <div class="product-info">
                <?php 
                $primary_image = $product->getPrimaryImage($product_id);
                $image_src = $primary_image ? '../' . $primary_image['image_path'] : '../assets/images/placeholder.jpg';
                ?>
                <img src="<?php echo $image_src; ?>" alt="<?php echo $product->name; ?>">
                <div class="product-details">
                    <h2><?php echo $product->name; ?></h2>
                    <p>Product ID: <?php echo $product->id; ?></p>
                    <p>Price: $<?php echo number_format($product->price, 2); ?></p>
                    <p>Stock: <?php echo $product->stock_quantity; ?> units</p>
                </div>
            </div>
            
            <!-- Upload Section -->
            <div class="upload-section">
                <h2>Upload New Images</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Select Images (Multiple allowed)</label>
                        <div class="file-input-wrapper">
                            <div class="file-input-button">
                                <span id="file-names">Choose images (Max 5MB each)</span>
                            </div>
                            <input type="file" name="product_images[]" id="product_images" accept="image/*" multiple onchange="updateFileNames(this)">
                        </div>
                        <small>Supported formats: JPG, PNG, GIF, WEBP. You can select multiple files.</small>
                        <div id="image-previews" class="image-previews" style="margin-top: 15px;"></div>
                    </div>
                    
                    <button type="submit" name="upload_images" class="button_1">Upload Images</button>
                </form>
            </div>
            
            <!-- Current Images Section -->
            <div class="images-section">
                <h2>Current Images (<?php echo $product_images->rowCount(); ?>)</h2>
                
                <?php if($product_images->rowCount() > 0): ?>
                    <div class="images-grid">
                        <?php while ($image = $product_images->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="image-card <?php echo $image['is_primary'] ? 'primary' : ''; ?>">
                            <?php if($image['is_primary']): ?>
                                <span class="primary-badge">Primary</span>
                            <?php endif; ?>
                            
                            <img src="../<?php echo $image['image_path']; ?>" alt="Product Image" class="image-preview">
                            
                            <div class="image-info">
                                <small>Uploaded: <?php echo date('M j, Y', strtotime($image['created_at'])); ?></small>
                            </div>
                            
                            <div class="image-actions">
                                <?php if(!$image['is_primary']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        <button type="submit" name="set_primary" class="button_1 btn-xs" style="background: #17a2b8;">Set Primary</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    <button type="submit" name="delete_image" class="button_1 btn-xs" style="background: #dc3545;" onclick="return confirm('Are you sure you want to delete this image?')">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3>No images uploaded yet</h3>
                        <p>Upload images using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateFileNames(input) {
            const fileNames = Array.from(input.files).map(file => file.name).join(', ');
            document.getElementById('file-names').textContent = fileNames || 'Choose images (Max 5MB each)';
            
            // Show image previews
            const previewContainer = document.getElementById('image-previews');
            previewContainer.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.style.display = 'inline-block';
                            preview.style.margin = '5px';
                            preview.style.textAlign = 'center';
                            preview.innerHTML = `
                                <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 80px; border-radius: 4px; border: 1px solid #ddd;">
                                <div style="font-size: 12px; color: #666;">${file.name}</div>
                            `;
                            previewContainer.appendChild(preview);
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
        
        // Validate file sizes before upload
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('product_images');
            if (fileInput.files) {
                for (let file of fileInput.files) {
                    const fileSize = file.size / 1024 / 1024; // in MB
                    if (fileSize > 5) {
                        e.preventDefault();
                        alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                        return false;
                    }
                }
            }
        });
    </script>
</body>
</html>