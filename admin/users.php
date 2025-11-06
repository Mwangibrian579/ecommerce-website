<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt;

// Get user stats
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$today_users = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TechStore Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #e8491d;
            display: block;
        }
        
        .users-table {
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
        
        .btn-sm {
            padding: 5px 10px;
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
                <li><a href="products.php">Products</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="users.php" class="active">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Users</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_users; ?></span>
                    <span>Total Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $today_users; ?></span>
                    <span>New Today</span>
                </div>
            </div>
            
            <?php if($users->rowCount() > 0): ?>
                <div class="users-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $user['username']; ?></strong>
                                    <?php if($user['username'] == 'admin'): ?>
                                        <span style="color: #e8491d; font-size: 12px;">(Admin)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <?php if($user['first_name'] || $user['last_name']): ?>
                                        <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['phone'] ?: '-'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="button_1 btn-sm" style="background: #17a2b8;">View</a>
                                    <?php if($user['username'] != 'admin'): ?>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="button_1 btn-sm" style="background: #dc3545;" onclick="return confirm('Are you sure?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; background: white; border-radius: 8px;">
                    <h3>No users found</h3>
                    <p>No users have registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>