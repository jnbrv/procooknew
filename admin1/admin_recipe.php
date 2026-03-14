<?php
// Include your database connection
include '../includes/db.php'; 

// --- 1. SESSION TIMEOUT (20-30 Minutes) ---
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     
    session_destroy();   
    header("Location: adminlogin.php?status=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// --- 2. STRICT SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminlogin.php"); 
    exit();
}

// Handle Delete Logic
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM recipes WHERE id = $id";
    mysqli_query($conn, $delete_query);
    header("Location: admin_recipe.php?msg=deleted");
    exit();
}

// Fetch recipes
$query = "SELECT * FROM recipes ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook Admin - Recipe Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #ff6600;
            --dark-bg: #1e1e1e;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            display: flex;
        }

        /* --- SIDEBAR STYLE --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--dark-bg);
            color: white;
            position: fixed;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 25px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            background: #111;
            color: var(--primary-color);
            border-bottom: 1px solid #333;
        }

        .sidebar-menu {
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .menu-item {
            padding: 15px 25px;
            color: #bbb;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-item i { width: 20px; }

        .menu-item:hover {
            background: #2d2d2d;
            color: white;
        }

        .menu-item.active {
            background: var(--primary-color);
            color: white;
        }

        /* --- MAIN CONTENT STYLE --- */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 30px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recipe-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .recipe-table th, .recipe-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .recipe-table th { background: #f8f9fa; color: #555; font-weight: 600; }

        .btn-view { background: #4a90e2; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .btn-delete { background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }

        /* --- MODAL STYLE --- */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.7); 
            align-items: center; justify-content: center; 
        }
        .modal-content { 
            background: white; width: 90%; max-width: 700px; 
            padding: 30px; border-radius: 15px; position: relative; 
            max-height: 85vh; overflow-y: auto; 
        }
        .close-modal { position: absolute; right: 25px; top: 20px; font-size: 1.8rem; cursor: pointer; color: #999; }
        .recipe-full-img { width: 100%; height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 20px; }
        .detail-label { font-weight: bold; color: var(--primary-color); display: block; margin-top: 15px; text-transform: uppercase; font-size: 0.8rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ProCook Admin</div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_recipe.php" class="menu-item active"><i class="fas fa-utensils"></i> Recipes</a>
            <a href="admin_users.php" class="menu-item"><i class="fas fa-users"></i> User Management</a>
            <a href="admin_reports.php" class="menu-item"><i class="fas fa-flag"></i> Reports</a>
            <a href="admin_logout.php" class="menu-item" style="margin-top: 50px; color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h2>Recipe Verification</h2>
            <p>Manage and review user-submitted recipes</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div style="padding:15px; background:#d4edda; color:#155724; border-radius:5px; margin-bottom:20px;">
                Recipe record updated successfully.
            </div>
        <?php endif; ?>

        <table class="recipe-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Recipe Title</th>
                    <th>Author</th>
                    <th>Posted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><img src="uploads/<?php echo $row['image_url']; ?>" width="60" height="45" style="border-radius:4px; object-fit:cover;"></td>
                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <button class="btn-view" onclick='viewRecipe(<?php echo json_encode($row); ?>)'>
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="admin_recipe.php?delete_id=<?php echo $row['id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Delete this recipe permanently?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="recipeModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <img id="m-image" src="" class="recipe-full-img">
            <h2 id="m-title" style="margin-top:0;"></h2>
            <p style="color:#666;"><i class="fas fa-user-circle"></i> <span id="m-author"></span></p>
            <hr>
            
            <span class="detail-label">Short Description</span>
            <p id="m-desc"></p>
            
            <span class="detail-label">Ingredients</span>
            <p id="m-ingredients" style="white-space: pre-wrap; background: #f9f9f9; padding: 15px; border-radius: 8px;"></p>
            
            <span class="detail-label">Cooking Instructions</span>
            <p id="m-instructions" style="white-space: pre-wrap; background: #f9f9f9; padding: 15px; border-radius: 8px;"></p>
        </div>
    </div>

    <script>
        const modal = document.getElementById('recipeModal');

        function viewRecipe(data) {
            document.getElementById('m-title').innerText = data.title;
            document.getElementById('m-author').innerText = "Author: " + data.author_name;
            document.getElementById('m-desc').innerText = data.description;
            document.getElementById('m-ingredients').innerText = data.ingredients;
            document.getElementById('m-instructions').innerText = data.instructions;
            document.getElementById('m-image').src = "uploads/" + data.image_url;
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }
    </script>

</body>
</html>