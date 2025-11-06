<?php
session_start();
require_once "config/database.php";
require_once "includes/Product.php";
require_once "includes/Cart.php";

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt;

// Get price range for filter
$price_range = $product->getPriceRange();

// Get filter parameters
$search_keywords = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;

// Apply filters
if(!empty($search_keywords) || !empty($category_id) || !empty($min_price) || !empty($max_price)) {
    $products_result = $product->searchWithFilters($search_keywords, $category_id, $min_price, $max_price, $sort_by, $sort_order);
    $total_products = $products_result->rowCount();
    $total_pages = 1;
    $current_page = 1;
} else {
    // Use pagination for all products
    $products_result = $product->getProductsPaginated($page, $per_page, $category_id);
    $products = $products_result['products'];
    $total_products = $products_result['total'];
    $total_pages = $products_result['total_pages'];
    $current_page = $products_result['page'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TechStore</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <li class="current"><a href="products.php">Products</a></li>
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

    <section id="products-header">
        <div class="container">
            <h1>Our Products</h1>
            <form method="GET" action="products.php" class="search-form">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_keywords); ?>">
                <button type="submit" class="button_1">Search</button>
                <?php if($search_keywords || $category_id || $min_price || $max_price): ?>
                    <a href="products.php" class="button_1" style="background: #666; margin-left: 10px;">Clear All</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <section id="products-list">
        <div class="container">
            <div class="products-container">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <div class="filter-section">
                        <h3>Filters</h3>
                        
                        <div class="filter-group">
                            <h4>Category</h4>
                            <select name="category" onchange="this.form.submit()" form="filters-form">
                                <option value="">All Categories</option>
                                <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <h4>Price Range</h4>
                            <div class="price-inputs">
                                <input type="number" name="min_price" placeholder="Min" 
                                       value="<?php echo $min_price; ?>" form="filters-form">
                                <span>to</span>
                                <input type="number" name="max_price" placeholder="Max" 
                                       value="<?php echo $max_price; ?>" form="filters-form">
                            </div>
                            <small>Price range: $<?php echo number_format($price_range['min_price'], 2); ?> - $<?php echo number_format($price_range['max_price'], 2); ?></small>
                        </div>

                        <div class="filter-group">
                            <h4>Sort By</h4>
                            <select name="sort_by" onchange="this.form.submit()" form="filters-form">
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="price" <?php echo $sort_by == 'price' ? 'selected' : ''; ?>>Price</option>
                                <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Newest</option>
                                <option value="stock_quantity" <?php echo $sort_by == 'stock_quantity' ? 'selected' : ''; ?>>Stock</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <h4>Order</h4>
                            <select name="sort_order" onchange="this.form.submit()" form="filters-form">
                                <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                                <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            </select>
                        </div>

                        <form id="filters-form" method="GET" style="display: none;">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_keywords); ?>">
                            <button type="submit">Apply Filters</button>
                        </form>
                    </div>
                </aside>

                <!-- Products Grid -->
                <main class="products-main">
                    <div class="products-header">
                        <div class="results-info">
                            <h2>
                                <?php if($search_keywords): ?>
                                    Search Results for "<?php echo htmlspecialchars($search_keywords); ?>"
                                <?php elseif($category_id): ?>
                                    <?php 
                                    $cat_name = "All Categories";
                                    $categories_stmt->execute();
                                    while ($cat = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        if($cat['id'] == $category_id) {
                                            $cat_name = $cat['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php echo $cat_name; ?>
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h2>
                            <p><?php echo $total_products; ?> product(s) found</p>
                        </div>
                        
                        <div class="view-options">
                            <span>View:</span>
                            <button class="view-btn active" data-view="grid">Grid</button>
                            <button class="view-btn" data-view="list">List</button>
                        </div>
                    </div>

                    <div class="products-grid" id="products-view">
                        <?php 
                        $products_to_display = isset($products) ? $products : $products_result;
                        if($products_to_display->rowCount() > 0):
                            while ($row = $products_to_display->fetch(PDO::FETCH_ASSOC)): 
                                $primary_image = $product->getPrimaryImage($row['id']);
                                $image_src = $primary_image ? $primary_image['image_path'] : ($row['image_url'] ?: 'assets/images/placeholder.jpg');
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo $image_src; ?>" alt="<?php echo $row['name']; ?>">
                                <?php if($row['featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3><?php echo $row['name']; ?></h3>
                                <p class="product-category"><?php echo $row['category_name']; ?></p>
                                <p class="product-price">$<?php echo number_format($row['price'], 2); ?></p>
                                <p class="product-stock <?php echo $row['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $row['stock_quantity'] > 0 ? 'In Stock: ' . $row['stock_quantity'] : 'Out of Stock'; ?>
                                </p>
                                <p class="product-description"><?php echo substr($row['description'], 0, 100); ?>...</p>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $row['id']; ?>" class="button_1">View Details</a>
                                    <?php if(isset($_SESSION['user_id']) && $row['stock_quantity'] > 0): ?>
                                        <form method="POST" action="cart.php" class="add-to-cart-form">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" name="add_to_cart" class="button_1" style="background: #28a745;">Add to Cart</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <div class="no-products">
                            <h3>No products found.</h3>
                            <p>Try adjusting your search terms or filters.</p>
                            <a href="products.php" class="button_1">Browse All Products</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if(isset($total_pages) && $total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($current_page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="page-link">Previous</a>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if($current_page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="page-link">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </section>

    <footer>
        <p>TechStore, Copyright &copy; <?php echo date("Y"); ?></p>
    </footer>

    <script>
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const viewBtns = document.querySelectorAll('.view-btn');
            const productsView = document.getElementById('products-view');
            
            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active button
                    viewBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update view
                    productsView.className = 'products-' + this.dataset.view;
                });
            });

            // Auto-submit price filters when both are filled
            const minPrice = document.querySelector('input[name="min_price"]');
            const maxPrice = document.querySelector('input[name="max_price"]');
            
            function checkPriceFilters() {
                if(minPrice.value && maxPrice.value) {
                    document.getElementById('filters-form').submit();
                }
            }
            
            minPrice.addEventListener('change', checkPriceFilters);
            maxPrice.addEventListener('change', checkPriceFilters);
        });
    </script>
</body>
</html>