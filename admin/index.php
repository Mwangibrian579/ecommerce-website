<?php
session_start();
// Simple admin check - in production, use proper authentication
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";
require_once "../includes/Order.php";
require_once "../includes/Product.php";

$database = new Database();
$db = $database->getConnection();

// Get stats
$order = new Order($db);
$product = new Product($db);

// Count pending orders
$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$pending_stmt = $db->prepare($pending_orders_query);
$pending_stmt->execute();
$pending_orders = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Count total products
$products_query = "SELECT COUNT(*) as count FROM products";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$total_products = $products_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent orders
$recent_orders = $order->getUserOrders(0, 5); // Get latest 5 orders
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechStore</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #35424a;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #e8491d;
        }
        
        .recent-orders {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <h1>Admin Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <div class="stat-number"><?php echo $pending_orders; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php 
                        $users_query = "SELECT COUNT(*) as count FROM users";
                        $users_stmt = $db->prepare($users_query);
                        $users_stmt->execute();
                        echo $users_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?></div>
                </div>
            </div>
            
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <?php if($recent_orders->rowCount() > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Order #</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Customer</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Amount</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order_row = $recent_orders->fetch(PDO::FETCH_ASSOC)): 
                                $user_query = "SELECT username FROM users WHERE id = ?";
                                $user_stmt = $db->prepare($user_query);
                                $user_stmt->bindParam(1, $order_row['user_id']);
                                $user_stmt->execute();
                                $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <td style="padding: 12px; border-bottom: 1px solid #dee2e6;"><?php echo $order_row['order_number']; ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #dee2e6;"><?php echo date('M j, Y', strtotime($order_row['created_at'])); ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #dee2e6;"><?php echo $user['username']; ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">$<?php echo number_format($order_row['total_amount'], 2); ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                                    <span class="status-badge status-<?php echo $order_row['status']; ?>">
                                        <?php echo ucfirst($order_row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>