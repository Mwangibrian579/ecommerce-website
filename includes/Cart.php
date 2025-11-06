<?php
class Cart {
    private $conn;
    private $table_name = "cart";

    public $id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add item to cart
    public function addToCart() {
        // Check if item already exists in cart
        $query = "SELECT id, quantity FROM " . $this->table_name . " 
                  WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            // Update quantity if item exists
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $this->quantity;
            
            $query = "UPDATE " . $this->table_name . " 
                      SET quantity = ? 
                      WHERE user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $new_quantity);
            $stmt->bindParam(2, $this->user_id);
            $stmt->bindParam(3, $this->product_id);
        } else {
            // Insert new item
            $query = "INSERT INTO " . $this->table_name . " 
                      SET user_id=:user_id, product_id=:product_id, quantity=:quantity";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":quantity", $this->quantity);
        }

        return $stmt->execute();
    }

    // Get cart items for user
    public function getCartItems() {
        $query = "SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
                  FROM " . $this->table_name . " c 
                  LEFT JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    // Update cart item quantity
    public function updateQuantity() {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantity = ? 
                  WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->quantity);
        $stmt->bindParam(2, $this->user_id);
        $stmt->bindParam(3, $this->product_id);

        return $stmt->execute();
    }

    // Remove item from cart
    public function removeFromCart() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);

        return $stmt->execute();
    }

    // Clear user's cart
    public function clearCart() {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);

        return $stmt->execute();
    }

    // Get cart total and item count
    public function getCartSummary() {
        $query = "SELECT SUM(c.quantity) as total_items, 
                         SUM(c.quantity * p.price) as total_price 
                  FROM " . $this->table_name . " c 
                  LEFT JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>