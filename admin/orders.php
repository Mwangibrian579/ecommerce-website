<?php
session_start();
// Simple admin check
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../includes/Order.php";

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);

$message = '';

// Handle status updates
if($_POST && isset($_POST['update_status'])) {
    $order->id = $_POST['order_id'];
    $order->status = $_POST['status'];
    
    if($order->updateStatus()) {
        $message = "Order status updated successfully!";
    } else {
        $message = "Failed to update order status.";
    }
}

// Get all orders
$query = "SELECT o.*, u.username, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$orders = $stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - TechStore Admin</title>
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
        
        .orders-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #35424a;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .status-form select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form select, .filter-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="orders.php" class="active">Orders</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Orders</h1>
            </div>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="filters">
                <form method="GET" class="filter-form">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input type="text" name="search" placeholder="Search order number...">
                    <button type="submit" class="button_1 btn-sm">Filter</button>
                    <a href="orders.php" class="button_1 btn-sm" style="background: #6c757d;">Clear</a>
                </form>
            </div>
            
            <div class="orders-table">
                <?php if($orders->rowCount() > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order_row = $orders->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $order_row['order_number']; ?></td>
                                <td>
                                    <div><?php echo $order_row['username']; ?></div>
                                    <small><?php echo $order_row['email']; ?></small>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order_row['created_at'])); ?></td>
                                <td>$<?php echo number_format($order_row['total_amount'], 2); ?></td>
                                <td><?php echo strtoupper($order_row['payment_method']); ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order_row['id']; ?>">
                                        <select name="status">
                                            <option value="pending" <?php echo $order_row['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $order_row['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="processing" <?php echo $order_row['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order_row['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order_row['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order_row['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="button_1 btn-sm">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="../order_details.php?order_id=<?php echo $order_row['id']; ?>" class="button_1 btn-sm" style="background: #17a2b8;">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center;">
                        <h3>No orders found</h3>
                        <p>There are no orders in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>