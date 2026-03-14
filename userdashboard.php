<?php
    require_once 'includes/db.php';
    session_start();


    // 1. Access Control Guard
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // --- SWEETALERT LOGIC START ---
    $success_msg = [];
    $error_msg = [];
    $info_msg = [];

    if (isset($_GET['status'])) {
        switch ($_GET['status']) {
            case 'verified':
                $success_msg[] = "Account Verified! Welcome to ProCook.";
                break;
            case 'profile_updated':
                $success_msg[] = "Your profile has been updated successfully.";
                break;
            case 'recipe_deleted':
                $info_msg[] = "The recipe has been removed.";
                break;
            case 'recipe_posted':
                $success_msg[] = "Your new recipe is now live!";
                break;
            case 'error':
                $error_msg[] = "An error occurred. Please try again.";
                break;
            case 'recipe_saved':
                $success_msg[] = "Recipe added to your favorites!";
                break;
            case 'recipe_unsaved':
                $info_msg[] = "Recipe removed from your favorites.";
                break;
        }
    }

    // --- ADDITIONAL EARNINGS LOGIC ---

    // 1. Get Wallet Balance (from users table)
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch();
    $current_balance = $wallet['wallet_balance'] ?? 0;

    // 2. Get Recent Sales (Transactions)
    $sales_stmt = $pdo->prepare("
        SELECT e.*, r.title as recipe_title 
        FROM earnings e 
        JOIN recipes r ON e.recipe_id = r.id 
        WHERE e.user_id = ? 
        ORDER BY e.earned_at DESC LIMIT 5
    ");
    $sales_stmt->execute([$user_id]);
    $recent_sales = $sales_stmt->fetchAll();

    // 2. Fetch User Profile & Settings
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 3. Fetch Own Recipes
    $own_stmt = $pdo->prepare("
        SELECT r.*, 
        (SELECT COUNT(*) FROM saved_recipes s WHERE s.recipe_id = r.id AND s.user_id = ?) AS is_saved 
        FROM recipes r 
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $own_stmt->execute([$user_id, $user_id]);
    $own_recipes = $own_stmt->fetchAll();

    // 4. Fetch Saved Recipes (via a Join table)
    $saved_stmt = $pdo->prepare("
        SELECT r.*, 1 AS is_saved 
        FROM recipes r 
        JOIN saved_recipes s ON r.id = s.recipe_id 
        WHERE s.user_id = ?
    ");
    $saved_stmt->execute([$user_id]);
    $saved_recipes = $saved_stmt->fetchAll();

    // 5. Fetch Total Earnings
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM earnings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $earnings = $stmt->fetch();
    $total_earned = $earnings['total'] ?? 0;

    // 6. Fetch Purchased Recipes
    $purchased_stmt = $pdo->prepare("
        SELECT r.* FROM recipes r 
        JOIN purchases p ON r.id = p.recipe_id 
        WHERE p.user_id = ?
        ORDER BY p.purchased_at DESC
    ");
    $purchased_stmt->execute([$user_id]);
    $purchased_recipes = $purchased_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook | Dashboard</title>
    <link rel="stylesheet" href="css/userdashboard.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>
    <main class="content">

        <div id="my-recipes-section" class="content-wrapper">
            <header class="content-header">
                <h1>My Recipe</h1>
                <div class="search-container">
                    <label for="recipe-search" style="display:none;">Search Recipes</label>
                    <ion-icon name="search-outline"></ion-icon>
                    <input type="text" id="recipe-search" placeholder="Search For Recipe" autocomplete="off">
                </div>
            </header>
            <section class="recipe-section">
                <h3 class="section-title">Own Recipes</h3>
                <div class="recipe-grid">
                    <?php if(empty($own_recipes)): ?>
                        <div class="empty-container">
                            <ion-icon name="restaurant-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                            <p class="empty-msg">You haven't created any recipes yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($own_recipes as $recipe): ?>
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" style="text-decoration: none; color: inherit;">
                                <div class="recipe-card">
                                    <div class="recipe-img" style="background-image: url('uploads/<?php echo htmlspecialchars($recipe['image_url']); ?>');">
                                        </div>
                                    <div class="recipe-info">
                                        <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                                        <small><?php echo $recipe['is_paid'] ? "₱" . number_format($recipe['price'], 2) : "Free"; ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <section class="recipe-section">
            <h3 class="section-title">Saved Recipes</h3>
            <div class="recipe-grid">
                <?php if(empty($saved_recipes)): ?>
                    <div class="empty-container">
                        <ion-icon name="bookmark-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                        <p class="empty-msg">You haven't saved any recipes yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($saved_recipes as $recipe): ?>
                        <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="recipe-card">
                                <div class="recipe-img" style="background-image: url('uploads/<?php echo htmlspecialchars($recipe['image_url']); ?>');">
                                    </div>
                                <div class="recipe-info">
                                    <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                                    <small><?php echo $recipe['is_paid'] ? "₱" . number_format($recipe['price'], 2) : "Free"; ?></small>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        </div>

        <div id="create-recipe-section" class="content-wrapper" style="display: none;">
            <h1 class="page-title" style="text-align: center; font-family: 'Playfair Display', serif; margin-bottom: 30px;">Create Recipe</h1>
            
            <form action="process_recipe.php" method="POST" enctype="multipart/form-data" id="recipe-upload-form">
                <div class="form-grid">
                    <div class="input-column">
                        <div class="dashboard-input-group">
                            <label for="recipeName">Recipe Name:</label>
                            <input type="text" id="recipeName" name="recipe_name" placeholder="Enter recipe name..." required autocomplete="one-time-code">
                        </div>

                        <div class="dashboard-input-group">
                            <label for="recipeDesc">Description:</label>
                            <textarea id="recipeDesc" name="description" placeholder="A brief hook for your recipe..."></textarea>
                        </div>

                        <div class="dashboard-input-group">
                            <label for="recipeIngredients">Ingredients:</label>
                            <textarea id="recipeIngredients" name="ingredients" class="rich-textarea" placeholder="List ingredients here..." required></textarea>
                        </div>

                        <div class="dashboard-input-group">
                            <label for="recipeProcedure">Procedure:</label>
                            <textarea id="recipeProcedure" name="procedure" class="rich-textarea" placeholder="Describe the steps..." required></textarea>
                        </div>
                    </div>

                    <div class="dashboard-input-group">
                        <label>Upload one image:</label>
                        <div id="create-drop-zone" class="universal-drop-zone">
                            <ion-icon name="cloud-upload-outline" class="drop-icon"></ion-icon>
                            <span class="drop-text">Drag & drop your best food shot, or click to browse.</span>
                            <input type="file" id="create-recipe-input" name="recipe_image" accept="image/png, image/jpeg" style="display: none;">
                        </div>
                        <div id="create-feedback" class="file-feedback"></div>
                    </div>
                </div>

                <div class="submit-container" style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
                    <button type="button" id="btn-clear-recipe" class="btn-clear">Clear All</button>
                    <button type="submit" class="dashboard-submit-btn">Submit</button>
                </div>

                <!-- modal moved here to remain inside form -->
                <div id="submit-modal" class="modal-overlay" style="display: none;">
                    <div class="modal-content">
                        <h3>Post your Recipe</h3>
                        <p>Would you like to sell this recipe to other chefs?</p>
                        
                        <div class="mfa-toggle-group" style="margin: 20px 0;">
                            <span>Recipe for sale</span>
                            <label class="switch">
                                <input type="checkbox" id="sell-toggle">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div id="price-input-container" style="display: none; margin-bottom: 20px;">
                            <div class="dashboard-input-group">
                                <label>Set Price (Pesos)</label>
                                <input type="number" id="recipe-price" name="recipe_price" placeholder="0.00" step="0.01" autocomplete="one-time-code">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-cancel" id="close-modal">Back</button>
                            <button type="submit" class="orange-btn" id="final-submit">Post Recipe</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="purchased-recipes-section" class="content-wrapper" style="display: none;">
            <header class="content-header">
                <h1>Purchased Recipes</h1>
            </header>
            <div class="recipe-grid">
                <?php if(empty($purchased_recipes)): ?>
                    <div class="empty-container">
                        <ion-icon name="cart-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                        <p class="empty-msg">You haven't purchased any recipes yet.</p>
                        <a href="all_recipes.php" class="orange-btn" style="width: fit-content; margin: 0 auto;">Browse Recipes</a>
                    </div>
                <?php else: ?>
                    <?php foreach($purchased_recipes as $recipe): ?>
                        <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="recipe-card">
                            <div class="recipe-img" style="background-image: url('uploads/<?php echo htmlspecialchars($recipe['image_url']); ?>');">
                                </div>
                            <div class="recipe-info">
                                <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                                <small>Purchased</small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="earnings-section" class="content-wrapper" style="display: none;">
            <header class="content-header">
                <h1>Financial Overview</h1>
            </header>

            <div class="earnings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                
                <div class="stat-card" style="background: linear-gradient(135deg, #ff6600, #ff9933); padding: 25px; border-radius: 15px; color: white; box-shadow: 0 4px 15px rgba(255,102,0,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Available Balance</h3>
                            <p style="font-size: 2rem; font-weight: bold; margin: 10px 0;">₱<?php echo number_format($current_balance, 2); ?></p>
                        </div>
                        <ion-icon name="wallet-outline" style="font-size: 3rem; opacity: 0.5;"></ion-icon>
                    </div>
                    <button class="withdraw-btn" style="background: white; color: #ff6600; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; width: 100%;">
                        Request Payout
                    </button>
                </div>

                <div class="stat-card" style="background: white; padding: 25px; border-radius: 15px; border-left: 5px solid #ff6600; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0; font-size: 0.9rem; color: #777;">Total Lifetime Sales</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: #333; margin: 10px 0;">₱<?php echo number_format($total_earned, 2); ?></p>
                    <small style="color: #27ae60;"><ion-icon name="trending-up-outline"></ion-icon> Across all recipes</small>
                </div>
            </div>

            <div class="transactions-container" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 20px;">Recent Sales</h3>
                <?php if(empty($recent_sales)): ?>
                    <p style="color: #999; text-align: center; padding: 20px;">No sales recorded yet. Keep sharing your recipes!</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #f4f4f4;">
                                <th style="padding: 12px;">Recipe</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px; text-align: right;">Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_sales as $sale): ?>
                            <tr style="border-bottom: 1px solid #f9f9f9;">
                                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($sale['recipe_title']); ?></strong></td>
                                <td style="padding: 12px; color: #666;"><?php echo date('M d, Y', strtotime($sale['earned_at'])); ?></td>
                                <td style="padding: 12px; text-align: right; color: #27ae60; font-weight: bold;">+₱<?php echo number_format($sale['amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div id="settings-section" class="content-wrapper" style="display: none;">
    <header class="content-header">
        <h1>Profile Settings</h1>
    </header>

    <div class="settings-container">
        <div class="settings-grid">
            <div class="personal-info">
                <h3>Personal Information</h3>
                <form action="process_settings.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="dashboard-input-group">
                        <label>Name</label>
                        <input type="text" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                               placeholder="Your Full Name">
                    </div>
                    <div class="dashboard-input-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="dashboard-input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               placeholder="+63 917 123 4567">
                    </div>
                    <div class="dashboard-input-group">
                        <label>Address</label>
                        <input type="text" name="address" 
                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" 
                               placeholder="123 Street, City">
                    </div>
                    <button type="submit" class="orange-btn">Update Profile</button>
                </form>
            </div>
        </div>

        <hr class="section-divider">

        <div class="password-info">
            <h3>Change Password</h3>
            
            <div class="dashboard-input-group">
                <label>Current Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="current-pass" class="pass-field" placeholder="Enter current password">
                    <ion-icon name="eye-off-outline" class="toggle-password" data-target="current-pass"></ion-icon>
                </div>
            </div>

            <div class="dashboard-input-group">
                <label>New Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="new-pass" class="pass-field" placeholder="Enter new password">
                    <ion-icon name="eye-off-outline" class="toggle-password" data-target="new-pass"></ion-icon>
                </div>
            </div>

            <div class="dashboard-input-group">
                <label>Confirm Password:</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm-pass" class="pass-field" placeholder="Re-type new password">
                    <ion-icon name="eye-off-outline" class="toggle-password" data-target="confirm-pass"></ion-icon>
                </div>
                <small id="password-match-error" style="color: #ff6600; display: none; margin-top: 5px; font-weight: bold;">
                    Passwords do not match!
                </small>
            </div>

            <button class="orange-btn" id="btn-confirm-password">Confirm</button>
        </div>
    </div>
</div>

    </main>

    <?php include 'includes/alerts.php'; ?>
    <script>
const recipeForm = document.getElementById('recipe-upload-form');
const submitModal = document.getElementById('submit-modal');
const finalSubmitBtn = document.getElementById('final-submit');
const closeModalBtn = document.getElementById('close-modal');
const sellToggle = document.getElementById('sell-toggle');
const priceContainer = document.getElementById('price-input-container');

// 1. Intercept the initial "Submit" click
recipeForm.addEventListener('submit', function(e) {
    // If the modal isn't visible yet, stop the form and show the modal
    if (submitModal.style.display === 'none' || submitModal.style.display === '') {
        e.preventDefault(); 
        submitModal.style.display = 'flex';
    }
    // If the modal IS visible, let the form submit normally (triggered by final-submit)
});

// 2. Toggle price input visibility
sellToggle.addEventListener('change', function() {
    priceContainer.style.display = this.checked ? 'block' : 'none';
    if(!this.checked) document.getElementById('recipe-price').value = '';
});

// 3. Close modal logic
closeModalBtn.addEventListener('click', () => {
    submitModal.style.display = 'none';
});

// 4. Ensure the Final Submit button actually submits the form
finalSubmitBtn.addEventListener('click', () => {
    // No e.preventDefault() here means the form will finally go to process_recipe.php
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Get the section from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section') || 'my-recipe'; // Default

    // 2. Hide all content wrappers
    document.querySelectorAll('.content-wrapper').forEach(wrapper => {
        wrapper.style.display = 'none';
    });

    // 3. Logic to show the correct section based on your specific IDs
    if (section === 'my-recipe') {
        document.getElementById('my-recipes-section').style.display = 'block';
    } 
    else if (section === 'create') {
        document.getElementById('create-recipe-section').style.display = 'block';
    } 
    else if (section === 'purchased') {
        document.getElementById('purchased-recipes-section').style.display = 'block';
    } 
    else if (section === 'earnings') {
        document.getElementById('earnings-section').style.display = 'block';
    } 
    else if (section === 'settings') {
        document.getElementById('settings-section').style.display = 'block';
    }
});
</script>
<?php include 'includes/alerts.php'; ?>
    <script src="js/userdashboard.js"></script>
   
</body>
</html>