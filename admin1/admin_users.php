<?php
session_start();

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

// Add this below session_start()
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     
    session_destroy();   
    header("Location: adminlogin.php?status=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
require_once '../includes/db.php';

// --- SECURITY CHECK ---
// Note: Ensure $_SESSION['user_id'] is set during your admin login
$current_admin_id = $_SESSION['user_id'] ?? null; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminlogin.php");
    exit();
}

// --- HANDLE ACTIONS ---
if (isset($_GET['id']) && isset($_GET['action'])) {
    $targetId = (int)$_GET['id'];
    $action = $_GET['action'];

    // PREVENT SELF-ACTION: Admin cannot delete or suspend themselves
    if ($targetId === $current_admin_id) {
        echo "<script>alert('Error: You cannot suspend or delete your own admin account.'); window.location='admin_users.php';</script>";
        exit();
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$targetId]);
        echo "<script>alert('User deleted successfully.'); window.location='admin_users.php';</script>";
    } 
    elseif ($action === 'toggle_status') {
        $stmt = $pdo->prepare("SELECT is_suspended, role FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $targetUser = $stmt->fetch();
        
        // Prevent suspending other admins as well (optional but recommended)
        if ($targetUser['role'] === 'admin') {
            echo "<script>alert('Security Error: Admin accounts cannot be suspended.'); window.location='admin_users.php';</script>";
        } else {
            $newStatus = ($targetUser['is_suspended'] == 1) ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
            echo "<script>alert('User status updated.'); window.location='admin_users.php';</script>";
        }
    }
}

// --- COUNT REGULAR USERS (Excluding Admins) ---
$countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$regularUserCount = $countStmt->fetchColumn();

// --- FETCH ALL USERS ---
$searchQuery = "";
if (isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (full_name LIKE ? OR email LIKE ?) ORDER BY id DESC");
    $stmt->execute([$search, $search]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProCook Admin | User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #2c3e50;
            --accent-blue: #3498db;
            --accent-red: #e74c3c;
            --accent-yellow: #f1c40f;
            --bg-light: #f8f9fa;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-light); display: flex; }

        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--primary-dark); color: white; position: fixed; }
        .sidebar-header { padding: 30px 20px; text-align: center; font-size: 1.5rem; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; color: #bdc3c7; text-decoration: none; }
        .menu-item.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #18bc9c; }
        .menu-item i { margin-right: 15px; }

        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .content-header h1 { margin: 0; color: var(--primary-dark); }

        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .search-form { margin-bottom: 20px; }
        .search-bar { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 6px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f1f2f6; color: var(--primary-dark); }
        td { padding: 15px; border-bottom: 1px solid #eee; }

        .user-profile { display: flex; align-items: center; }
        .user-avatar { 
            width: 40px; height: 40px; border-radius: 50%; 
            background: #34495e; color: white; display: flex; 
            align-items: center; justify-content: center; 
            margin-right: 15px; font-weight: bold; 
        }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-suspended { background: #ffebee; color: #c62828; }

        .btn-action { padding: 8px; border-radius: 4px; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; color: white; }
        .btn-suspend { background: var(--accent-yellow); color: #856404; }
        .btn-delete { background: var(--accent-red); margin-left: 5px; }
        .btn-reactivate { background: #27ae60; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ProCook Admin</div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_recipe.php" class="menu-item"><i class="fas fa-utensils"></i> Recipes</a>
            <a href="admin_users.php" class="menu-item active"><i class="fas fa-users"></i> User Management</a>
            <a href="admin_reports.php" class="menu-item"><i class="fas fa-flag"></i> Reports</a>
            <a href="admin_logout.php" class="menu-item" style="margin-top: 50px; color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <div>
                <h1>User Management</h1>
                <p>Monitor account activities and enforce community policies.</p>
            </div>
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-bar" placeholder="Search users..." value="<?php echo $_GET['search'] ?? ''; ?>">
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $row): ?>
                    <tr>
                        <td>
                            <div class="user-profile">
                                <div class="user-avatar"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span style="text-transform: capitalize;"><?php echo $row['role']; ?></span></td>
                        <td>
                            <?php if(isset($row['is_suspended']) && $row['is_suspended'] == 1): ?>
                                <span class="badge status-suspended">Suspended</span>
                            <?php else: ?>
                                <span class="badge status-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="admin_users.php?id=<?php echo $row['id']; ?>&action=toggle_status" 
                               class="btn-action <?php echo ($row['is_suspended'] ?? 0) == 1 ? 'btn-reactivate' : 'btn-suspend'; ?>" 
                               title="<?php echo ($row['is_suspended'] ?? 0) == 1 ? 'Reactivate' : 'Suspend'; ?>">
                               <i class="fas <?php echo ($row['is_suspended'] ?? 0) == 1 ? 'fa-check' : 'fa-ban'; ?>"></i>
                            </a>

                            <?php if($row['role'] !== 'admin'): ?>
                            <a href="admin_users.php?id=<?php echo $row['id']; ?>&action=delete" 
                               class="btn-action btn-delete" 
                               onclick="return confirm('Are you sure you want to PERMANENTLY delete this user?')" 
                               title="Delete Account">
                               <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>