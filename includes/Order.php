<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $id;
    public $user_id;
    public $order_number;
    public $total_amount;
    public $status;
    public $payment_method;
    public $mpesa_receipt_number;
    public $phone_number;
    public $shipping_address;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Generate unique order number
    private function generateOrderNumber() {
        return 'ORD' . date('Ymd') . strtoupper(uniqid());
    }

    // Create new order
    public function create() {
        $this->order_number = $this->generateOrderNumber();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, order_number=:order_number, 
                      total_amount=:total_amount, payment_method=:payment_method,
                      phone_number=:phone_number, shipping_address=:shipping_address";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->phone_number = htmlspecialchars(strip_tags($this->phone_number));
        $this->shipping_address = htmlspecialchars(strip_tags($this->shipping_address));

        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":order_number", $this->order_number);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":phone_number", $this->phone_number);
        $stmt->bindParam(":shipping_address", $this->shipping_address);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Add items to order
    public function addOrderItem($product_id, $quantity, $price) {
        $query = "INSERT INTO order_items 
                  SET order_id=:order_id, product_id=:product_id, 
                      quantity=:quantity, price=:price";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_id", $this->id);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":price", $price);

        return $stmt->execute();
    }

    // Get order by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->user_id = $row['user_id'];
            $this->order_number = $row['order_number'];
            $this->total_amount = $row['total_amount'];
            $this->status = $row['status'];
            $this->payment_method = $row['payment_method'];
            $this->mpesa_receipt_number = $row['mpesa_receipt_number'];
            $this->phone_number = $row['phone_number'];
            $this->shipping_address = $row['shipping_address'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Update order status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Update M-Pesa receipt number
    public function updateMpesaReceipt($receipt_number) {
        $query = "UPDATE " . $this->table_name . " 
                  SET mpesa_receipt_number = :receipt_number, status = 'paid'
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":receipt_number", $receipt_number);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Get user's orders
    public function getUserOrders($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get order items
    public function getOrderItems() {
        $query = "SELECT oi.*, p.name, p.image_url 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        return $stmt;
    }
}
?>