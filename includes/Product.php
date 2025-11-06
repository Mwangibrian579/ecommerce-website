<?php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $price;
    public $category_id;
    public $image_url;
    public $stock_quantity;
    public $featured;
    public $specifications;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all products
    public function read() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read single product
    public function readOne() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ? 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->category_id = $row['category_id'];
            $this->category_name = $row['category_name'];
            $this->image_url = $row['image_url'];
            $this->stock_quantity = $row['stock_quantity'];
            $this->featured = $row['featured'];
            $this->specifications = $row['specifications'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Read featured products
    public function readFeatured() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.featured = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT 6";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Search products
    public function search($keywords) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? 
                  ORDER BY p.created_at DESC";

        $keywords = "%{$keywords}%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->execute();

        return $stmt;
    }

    // Create new product
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, description=:description, price=:price, 
                      category_id=:category_id, image_url=:image_url, 
                      stock_quantity=:stock_quantity, featured=:featured";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->featured = htmlspecialchars(strip_tags($this->featured));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":featured", $this->featured);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Update product
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, description=:description, price=:price, 
                      category_id=:category_id, image_url=:image_url, 
                      stock_quantity=:stock_quantity, featured=:featured 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->stock_quantity = htmlspecialchars(strip_tags($this->stock_quantity));
        $this->featured = htmlspecialchars(strip_tags($this->featured));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Delete product
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    // =========================================================================
    // IMAGE MANAGEMENT METHODS
    // =========================================================================

    // Add product image
    public function addImage($product_id, $image_path, $is_primary = false) {
        // If this is primary, update all other images to not primary
        if ($is_primary) {
            $this->setPrimaryImage($product_id, null);
        }
        
        $query = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$product_id, $image_path, $is_primary ? 1 : 0]);
    }

    // Set primary image
    public function setPrimaryImage($product_id, $image_id) {
        // First, set all images for this product to not primary
        $query = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        // If image_id is provided, set it as primary
        if ($image_id) {
            $query = "UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$image_id, $product_id]);
        }
        
        return true;
    }

    // Get product images
    public function getImages($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        return $stmt;
    }

    // Get primary image
    public function getPrimaryImage($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no primary image, get the first one
        if (!$image) {
            $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY created_at ASC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$product_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $image;
    }

    // Delete product image
    public function deleteImage($image_id) {
        // Get image info before deleting
        $query = "SELECT * FROM product_images WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Delete from database
            $query = "DELETE FROM product_images WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $deleted = $stmt->execute([$image_id]);
            
            // If this was primary, set another image as primary
            if ($deleted && $image['is_primary']) {
                $this->setPrimaryImage($image['product_id'], null);
            }
            
            return $image; // Return image info for file deletion
        }
        
        return false;
    }

    // Update product with image handling
    public function updateWithImage($id, $name, $description, $price, $category_id, $stock_quantity, $featured, $image_url = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, description = ?, price = ?, category_id = ?, 
                      stock_quantity = ?, featured = ?, image_url = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($name));
        $this->description = htmlspecialchars(strip_tags($description));
        $this->price = htmlspecialchars(strip_tags($price));
        $this->category_id = htmlspecialchars(strip_tags($category_id));
        $this->stock_quantity = htmlspecialchars(strip_tags($stock_quantity));
        $this->featured = $featured ? 1 : 0;
        $this->image_url = $image_url;
        $this->id = $id;
        
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->price);
        $stmt->bindParam(4, $this->category_id);
        $stmt->bindParam(5, $this->stock_quantity);
        $stmt->bindParam(6, $this->featured);
        $stmt->bindParam(7, $this->image_url);
        $stmt->bindParam(8, $this->id);
        
        return $stmt->execute();
    }

    // =========================================================================
    // ADVANCED SEARCH AND FILTERING METHODS
    // =========================================================================

    // Advanced search with filters
    public function searchWithFilters($keywords, $category_id = null, $min_price = null, $max_price = null, $sort_by = 'name', $sort_order = 'ASC') {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE 1=1";
        
        $params = array();
        
        // Keyword search
        if(!empty($keywords)) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
            $keywords = "%{$keywords}%";
            $params[] = $keywords;
            $params[] = $keywords;
            $params[] = $keywords;
        }
        
        // Category filter
        if(!empty($category_id)) {
            $query .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        // Price range filter
        if(!empty($min_price)) {
            $query .= " AND p.price >= ?";
            $params[] = $min_price;
        }
        
        if(!empty($max_price)) {
            $query .= " AND p.price <= ?";
            $params[] = $max_price;
        }
        
        // Sorting
        $allowed_sort = array('name', 'price', 'created_at', 'stock_quantity');
        $sort_by = in_array($sort_by, $allowed_sort) ? $sort_by : 'name';
        $sort_order = $sort_order == 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY p." . $sort_by . " " . $sort_order;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt;
    }

    // Get products by category
    public function getByCategory($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = ? 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get products with pagination
    public function getProductsPaginated($page = 1, $per_page = 12, $category_id = null) {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.id";
        
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " p";
        
        $where = "";
        $params = array();
        
        if(!empty($category_id)) {
            $where = " WHERE p.category_id = ?";
            $params[] = $category_id;
        }
        
        $query .= $where . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $count_query .= $where;
        
        // Get total count
        $count_stmt = $this->conn->prepare($count_query);
        if(!empty($params)) {
            $count_stmt->execute($params);
        } else {
            $count_stmt->execute();
        }
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return array(
            'products' => $stmt,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }

    // Get price range for filters
    public function getPriceRange() {
        $query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>