<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle category actions
if($_POST) {
    if(isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $parent_id = $_POST['parent_id'] ?: NULL;
        
        $query = "INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$name, $description, $parent_id])) {
            $message = "Category added successfully!";
        } else {
            $message = "Failed to add category.";
        }
    }
}

// Get all categories
$categories_query = "SELECT c.*, p.name as parent_name 
                    FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id 
                    ORDER BY c.name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - TechStore Admin</title>
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
        
        .add-category-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        
        .categories-table {
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
                <li><a href="categories.php" class="active">Categories</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Categories</h1>
            </div>
            
            <?php if($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="add-category-form">
                <h2>Add New Category</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Parent Category</label>
                        <select name="parent_id">
                            <option value="">No Parent (Main Category)</option>
                            <?php 
                            // Reset categories for dropdown
                            $categories_stmt->execute();
                            while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)): 
                                if(!$category['parent_id']): // Only show main categories as parents
                            ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php 
                                endif;
                            endwhile; 
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_category" class="button_1">Add Category</button>
                </form>
            </div>
            
            <h2>All Categories (<?php echo $categories->rowCount(); ?>)</h2>
            
            <?php if($categories->rowCount() > 0): ?>
                <div class="categories-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Parent Category</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset categories for display
                            $categories_stmt->execute();
                            while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                            <tr>
                                <td><strong><?php echo $category['name']; ?></strong></td>
                                <td><?php echo $category['description']; ?></td>
                                <td><?php echo $category['parent_name'] ?: '-'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="button_1 btn-sm">Edit</a>
                                    <a href="delete_category.php?id=<?php echo $category['id']; ?>" class="button_1 btn-sm" style="background: #dc3545;" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; background: white; border-radius: 8px;">
                    <h3>No categories found</h3>
                    <p>Add your first category using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>