<?php
session_start();
require_once "config/database.php";
require_once "includes/Product.php";
require_once "includes/Cart.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);

// Get product ID from URL
$product->id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Product ID not found.');

// Read the product details
if($product->readOne()) {
    // Product exists
} else {
    // Product doesn't exist
    die('ERROR: Product not found.');
}

// Get product images
$product_images = $product->getImages($product->id);
$primary_image = $product->getPrimaryImage($product->id);

// Handle add to cart
$message = '';
if($_POST && isset($_POST['add_to_cart'])) {
    if(!isset($_SESSION['user_id'])) {
        $message = "Please login to add items to cart.";
    } else {
        $cart = new Cart($db);
        $cart->user_id = $_SESSION['user_id'];
        $cart->product_id = $_POST['product_id'];
        $cart->quantity = $_POST['quantity'];
        
        if($cart->addToCart()) {
            $message = "Product added to cart successfully!";
        } else {
            $message = "Failed to add product to cart.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product->name; ?> - TechStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-gallery {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .thumbnail-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .thumbnail {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #e8491d;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .specifications-list {
            list-style: none;
            padding: 0;
        }
        
        .specifications-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .specifications-list li:last-child {
            border-bottom: none;
        }
        
        .image-gallery-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            transform: translateY(-50%);
        }
        
        .modal-nav button {
            background: rgba(255,255,255,0.8);
            border: none;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Tech</span>Store</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="cart.php">Cart 
                            <?php 
                            if(isset($_SESSION['user_id'])) {
                                require_once "config/database.php";
                                require_once "includes/Cart.php";
                                $database = new Database();
                                $db = $database->getConnection();
                                $cart = new Cart($db);
                                $cart->user_id = $_SESSION['user_id'];
                                $summary = $cart->getCartSummary();
                                if($summary['total_items'] > 0) {
                                    echo "(" . $summary['total_items'] . ")";
                                }
                            }
                            ?>
                        </a></li>
                        <li><a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section id="product-detail">
        <div class="container">
            <?php if($message): ?>
                <div class="success" style="margin-bottom: 20px;"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="product-detail-container">
                <!-- Product Images -->
                <div class="product-image-large">
                    <?php if($product_images->rowCount() > 0): ?>
                        <div class="product-gallery">
                            <div class="thumbnail-list">
                                <?php 
                                $product_images = $product->getImages($product->id); // Reset pointer
                                $first_image = true;
                                while ($image = $product_images->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <img src="<?php echo $image['image_path']; ?>" 
                                     alt="<?php echo $product->name; ?>" 
                                     class="thumbnail <?php echo $first_image ? 'active' : ''; ?>"
                                     data-full-image="<?php echo $image['image_path']; ?>"
                                     onclick="changeMainImage(this)">
                                <?php 
                                $first_image = false;
                                endwhile; 
                                ?>
                            </div>
                            <div class="main-image-container">
                                <?php 
                                $primary_image = $product->getPrimaryImage($product->id);
                                $main_image_src = $primary_image ? $primary_image['image_path'] : ($product->image_url ?: 'assets/images/placeholder.jpg');
                                ?>
                                <img src="<?php echo $main_image_src; ?>" alt="<?php echo $product->name; ?>" class="main-image" id="main-product-image" onclick="openImageModal('<?php echo $main_image_src; ?>')">
                            </div>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo $product->image_url ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $product->name; ?>" class="main-image">
                    <?php endif; ?>
                </div>

                <!-- Product Information -->
                <div class="product-info-detail">
                    <h1><?php echo $product->name; ?></h1>
                    <p class="product-category">Category: <?php echo $product->category_name; ?></p>
                    <p class="product-price">$<?php echo number_format($product->price, 2); ?></p>
                    <p class="product-stock <?php echo $product->stock_quantity > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product->stock_quantity > 0 ? 'In Stock: ' . $product->stock_quantity . ' units' : 'Out of Stock'; ?>
                    </p>
                    
                    <?php if($product->featured): ?>
                        <div class="featured-badge" style="display: inline-block; background: #e8491d; color: white; padding: 5px 10px; border-radius: 4px; margin-bottom: 15px;">
                            ⭐ Featured Product
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product->description)); ?></p>
                    </div>

                    <?php if($product->specifications): ?>
                    <div class="product-specifications">
                        <h3>Specifications</h3>
                        <?php 
                        $specs = json_decode($product->specifications, true);
                        if($specs): 
                        ?>
                        <ul class="specifications-list">
                            <?php foreach($specs as $key => $value): ?>
                            <li><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo $value; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="product-actions">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($product->stock_quantity > 0): ?>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $product->id); ?>" class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                        <div>
                                            <label for="quantity">Quantity:</label>
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product->stock_quantity; ?>" style="width: 80px; padding: 8px;">
                                        </div>
                                        <button type="submit" name="add_to_cart" class="button_1" style="padding: 12px 30px; font-size: 16px;">Add to Cart</button>
                                        <a href="checkout.php?product_id=<?php echo $product->id; ?>&quantity=1" class="button_1" style="background: #28a745; padding: 12px 30px; font-size: 16px;">Buy Now</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p style="color: #dc3545; font-weight: bold; padding: 15px; background: #f8d7da; border-radius: 4px;">This product is currently out of stock.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="padding: 15px; background: #fff3cd; border-radius: 4px;">
                                <a href="login.php" class="button_1">Login to Purchase</a> or 
                                <a href="register.php" class="button_1" style="background: #6c757d;">Register</a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="product-meta" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <p><strong>Product ID:</strong> <?php echo $product->id; ?></p>
                        <p><strong>Added:</strong> <?php echo date('F j, Y', strtotime($product->created_at)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php
            $related_products = $product->getByCategory($product->category_id);
            if($related_products->rowCount() > 1): // More than just this product
            ?>
            <div class="related-products" style="margin-top: 50px; padding-top: 30px; border-top: 2px solid #eee;">
                <h2>Related Products</h2>
                <div class="products-grid" style="margin-top: 20px;">
                    <?php 
                    $count = 0;
                    while ($related = $related_products->fetch(PDO::FETCH_ASSOC)): 
                        if($related['id'] != $product->id && $count < 4): // Exclude current product and limit to 4
                            $related_primary_image = $product->getPrimaryImage($related['id']);
                            $related_image_src = $related_primary_image ? $related_primary_image['image_path'] : ($related['image_url'] ?: 'assets/images/placeholder.jpg');
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $related_image_src; ?>" alt="<?php echo $related['name']; ?>">
                        </div>
                        <div class="product-info">
                            <h3><?php echo $related['name']; ?></h3>
                            <p class="product-price">$<?php echo number_format($related['price'], 2); ?></p>
                            <a href="product.php?id=<?php echo $related['id']; ?>" class="button_1">View Details</a>
                        </div>
                    </div>
                    <?php 
                        $count++;
                        endif;
                    endwhile; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Image Modal -->
    <div id="imageModal" class="image-gallery-modal">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <div class="modal-nav">
            <button onclick="changeModalImage(-1)">❮</button>
            <button onclick="changeModalImage(1)">❯</button>
        </div>
        <img class="modal-content" id="modalImage">
    </div>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>

    <script>
        // Image gallery functionality
        function changeMainImage(thumb) {
            // Update main image
            const mainImage = document.getElementById('main-product-image');
            mainImage.src = thumb.getAttribute('data-full-image');
            mainImage.setAttribute('onclick', `openImageModal('${thumb.getAttribute('data-full-image')}')`);
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }
        
        // Modal functionality
        let currentImageIndex = 0;
        let imageArray = [];
        
        function openImageModal(imageSrc) {
            // Collect all product images
            imageArray = [];
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                imageArray.push(thumb.getAttribute('data-full-image'));
            });
            
            // Find current image index
            currentImageIndex = imageArray.indexOf(imageSrc);
            
            // Show modal with current image
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'flex';
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        function changeModalImage(direction) {
            currentImageIndex += direction;
            
            // Loop around
            if (currentImageIndex >= imageArray.length) {
                currentImageIndex = 0;
            } else if (currentImageIndex < 0) {
                currentImageIndex = imageArray.length - 1;
            }
            
            document.getElementById('modalImage').src = imageArray[currentImageIndex];
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach((thumb, index) => {
                thumb.classList.toggle('active', index === currentImageIndex);
            });
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>