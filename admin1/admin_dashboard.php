<?php
session_start();

// --- 1. SESSION TIMEOUT LOGIC (20-30 Minutes) ---
// If the admin is inactive for 1800 seconds (30 mins), log them out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     
    session_destroy();   
    header("Location: adminlogin.php?status=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// --- 2. STRICT SECURITY CHECK ---
// This prevents someone from just typing the URL to get in.
// If the role is not 'admin', they are sent to the login page.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: adminlogin.php?status=unauthorized"); 
    exit();
}

require_once '../includes/db.php'; 

// --- FETCH STATS ---
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$recipeCount = $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
$totalSales = $pdo->query("SELECT SUM(amount_paid) FROM purchases")->fetchColumn() ?: 0;

// --- FETCH RECENT PENDING RECIPES ---
$stmt = $pdo->prepare("
    SELECT r.id, r.title, r.created_at, u.username as creator_name 
    FROM recipes r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentRecipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook Admin | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #2c3e50;
            --accent-color: #18bc9c;
            --bg-light: #f8f9fa;
            --text-muted: #7f8c8d;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            display: flex;
        }

        /* --- Sidebar Style --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            color: white;
            position: fixed;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            flex-grow: 1;
            padding-top: 20px;
        }

        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            color: #bdc3c7;
            text-decoration: none;
            transition: 0.3s;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--accent-color);
        }

        .menu-item i {
            margin-right: 15px;
            width: 20px;
        }

        /* --- Main Content Style --- */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 40px;
        }

        .header-title h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--primary-dark);
        }

        .header-title p {
            color: var(--text-muted);
            margin: 5px 0 30px 0;
        }

        /* --- Stat Cards --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 20px;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary-dark);
        }

        .stat-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .bg-blue { background: #e3f2fd; color: #1976d2; }
        .bg-green { background: #e8f5e9; color: #388e3c; }
        .bg-orange { background: #fff3e0; color: #f57c00; }

        .content-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .content-box h2 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #eee;
            color: var(--text-muted);
            font-weight: 600;
        }

        table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-pending { background: #fff9db; color: #f08c00; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ProCook Admin</div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="menu-item active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_recipe.php" class="menu-item"><i class="fas fa-utensils"></i> Recipes</a>
            <a href="admin_users.php" class="menu-item"><i class="fas fa-users"></i> User Management</a>
            <a href="admin_reports.php" class="menu-item"><i class="fas fa-flag"></i> Reports</a>
            <a href="admin_logout.php" class="menu-item" style="margin-top: 50px; color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-title">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, Administrator. System status is normal.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($userCount); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-book-open"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($recipeCount); ?></h3>
                    <p>Total Recipes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3>₱<?php echo number_format($totalSales, 2); ?></h3>
                    <p>Total Sales</p>
                </div>
            </div>
        </div>

        <div class="content-box">
            <h2>Recent Recipe Submissions (Pending Approval)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Recipe Title</th>
                        <th>Creator</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentRecipes) > 0): ?>
                        <?php foreach ($recentRecipes as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><span class="badge badge-pending">Pending</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">No pending recipes at the moment.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>