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
require_once '../includes/db.php';

// --- SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit();
}

// --- HANDLE ACTIONS ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reportId = (int)$_GET['id'];

    if ($_GET['action'] === 'suspend' && isset($_GET['recipe_id'])) {
        $recipeId = (int)$_GET['recipe_id'];
        
        // 1. Change status to 'pending' to hide it from the public feed
        $stmt = $pdo->prepare("UPDATE recipes SET status = 'pending' WHERE id = ?");
        $stmt->execute([$recipeId]);
        
        // 2. Remove the report from the list as it's now "handled"
        $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        $stmt->execute([$reportId]);
        
        echo "<script>alert('Recipe status set to pending. It is now hidden from users.'); window.location='admin_reports.php';</script>";
    } 
    elseif ($_GET['action'] === 'dismiss') {
        // Simply remove the report flag because the recipe is fine
        $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        $stmt->execute([$reportId]);
        echo "<script>alert('Report dismissed. Recipe remains published.'); window.location='admin_reports.php';</script>";
    }
}

// --- FETCH REPORTS (Fixed Query) ---
$stmt = $pdo->query("
    SELECT 
        rep.id as report_id, 
        rep.reason, 
        rep.created_at as report_date,
        rec.id as recipe_id, 
        rec.title as recipe_title,
        u.username as reporter_name
    FROM reports rep
    LEFT JOIN recipes rec ON rep.recipe_id = rec.id
    LEFT JOIN users u ON rep.reporter_id = u.id
    ORDER BY rep.created_at DESC
");
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook Admin | Reports & Flags</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-dark: #2c3e50;
            --accent-red: #e74c3c;
            --accent-orange: #f39c12;
            --bg-light: #f8f9fa;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-light); display: flex; }

        /* --- Sidebar --- */
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--primary-dark); color: white; position: fixed; }
        .sidebar-header { padding: 30px 20px; text-align: center; font-size: 1.5rem; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; color: #bdc3c7; text-decoration: none; }
        .menu-item.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #18bc9c; }
        .menu-item i { margin-right: 15px; }

        /* --- Main Content --- */
        .main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); padding: 40px; }
        .content-header { margin-bottom: 30px; }
        .content-header h1 { margin: 0; color: var(--primary-dark); }

        /* --- Report Cards --- */
        .report-card {
            background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px; padding: 20px; border-left: 5px solid var(--accent-red);
            display: flex; justify-content: space-between; align-items: center;
        }

        .report-info h4 { margin: 0 0 5px 0; color: var(--primary-dark); }
        .report-info p { margin: 0; font-size: 0.9rem; color: #7f8c8d; }

        .reason-tag {
            display: inline-block; background: #fff5f5; color: var(--accent-red);
            padding: 4px 10px; border-radius: 4px; font-size: 0.8rem;
            font-weight: bold; margin-top: 10px; text-transform: uppercase;
        }

        .reporter-meta { font-size: 0.8rem; color: #95a5a6; margin-top: 5px; }

        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 10px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-dismiss { background: #ecf0f1; color: #7f8c8d; }
        .btn-take-action { background: var(--accent-red); color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ProCook Admin</div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_recipe.php" class="menu-item"><i class="fas fa-utensils"></i> Recipe</a>
            <a href="admin_users.php" class="menu-item"><i class="fas fa-users"></i> User Management</a>
            <a href="admin_reports.php" class="menu-item active"><i class="fas fa-flag"></i> Reports</a>
            <a href="admin_logout.php" class="menu-item" style="margin-top: 50px; color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1>Reports & Flags</h1>
            <p>Review content flagged by the community for violations of platform policies.</p>
        </div>

        <?php if (count($reports) > 0): ?>
            <?php foreach ($reports as $row): ?>
            <div class="report-card">
                <div class="report-info">
                    <h4>Recipe: "<?php echo htmlspecialchars($row['recipe_title']); ?>"</h4>
                    <p>Reported for: <strong><?php echo htmlspecialchars($row['reason']); ?></strong></p>
                    <div class="reason-tag">Violation Flagged</div>
                    <div class="reporter-meta">
                        Reported by: <?php echo htmlspecialchars($row['reporter_name']); ?> | 
                        Date: <?php echo date('M d, Y', strtotime($row['report_date'])); ?>
                    </div>
                </div>
                <div class="btn-group">
                    <a href="admin_reports.php?action=dismiss&id=<?php echo $row['report_id']; ?>" 
                    class="btn btn-dismiss">Dismiss</a>
                    
                    <a href="admin_reports.php?action=suspend&id=<?php echo $row['report_id']; ?>&recipe_id=<?php echo $row['recipe_id']; ?>" 
                    class="btn btn-take-action" 
                    style="background-color: #f39c12;" 
                    onclick="return confirm('Hide this recipe and set status to pending?')">
                    Hide Recipe
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; color: #7f8c8d;">
                <i class="fas fa-check-double" style="font-size: 3rem; color: #27ae60; margin-bottom: 15px;"></i>
                <h3>No pending reports</h3>
                <p>The community is looking clean! All reported content has been handled.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>