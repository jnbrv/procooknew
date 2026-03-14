<?php 
    $current_file = basename($_SERVER['PHP_SELF']); 
    // Default to 'my-recipe' if no section is set
    $current_section = isset($_GET['section']) ? $_GET['section'] : 'my-recipe';
?>

<style>
    /* This ensures the logo and text always stack vertically and center perfectly */
    .sidebar-header {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 30px;
    }

    .brand-logo {
        display: flex !important;
        flex-direction: column !important; /* THE MAGIC FIX */
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        width: 100% !important;
    }

    .brand-logo img {
        width: 70px !important;
        height: 70px !important;
        border-radius: 50% !important;
        border: 3px solid white !important;
        margin-bottom: 10px !important;
        object-fit: cover !important;
    }

    .brand-logo h2 {
        font-family: 'Playfair Display', serif !important;
        font-size: 1.5rem !important;
        color: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Fix for the back arrow positioning */
    .auth-image {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .back-btn {
        color: white;
        font-size: 1.8rem;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
    }
</style>

<aside class="sidebar">
    <div class="auth-image">
        <a href="index.php" class="back-btn" title="Back to Home">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
    </div>
    <div class="sidebar-header">
        <div class="brand-logo">
            <img src="images/procooklogo.png" alt="Logo">
            <h2>ProCook</h2>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="userdashboard.php?section=my-recipe" 
           class="nav-item <?php echo ($current_section == 'my-recipe') ? 'active' : ''; ?>">
            <ion-icon name="restaurant-outline" class="nav-icon"></ion-icon>
            <span>My Recipe</span>
        </a>

        <a href="userdashboard.php?section=create" 
           class="nav-item <?php echo ($current_section == 'create') ? 'active' : ''; ?>">
            <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
            <span>Create Recipe</span>
        </a>

        <a href="userdashboard.php?section=purchased" 
           class="nav-item <?php echo ($current_section == 'purchased') ? 'active' : ''; ?>">
            <ion-icon name="cart-outline" class="nav-icon"></ion-icon>
            <span>Purchased Recipes</span>
        </a>

        <a href="userdashboard.php?section=earnings" 
           class="nav-item <?php echo ($current_section == 'earnings') ? 'active' : ''; ?>">
            <ion-icon name="cash-outline" class="nav-icon"></ion-icon>
            <span>Earnings</span>
        </a>

        <a href="userdashboard.php?section=settings" 
           class="nav-item <?php echo ($current_section == 'settings') ? 'active' : ''; ?>">
            <ion-icon name="settings-outline" class="nav-icon"></ion-icon>
            <span>Profile Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>LOG OUT</span>
        </a>
    </div>
</aside>