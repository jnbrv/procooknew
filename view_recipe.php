<?php
    require_once 'includes/db.php';
    session_start();

    // --- SWEETALERT LOGIC START ---
    $success_msg = [];
    $error_msg = [];
    $info_msg = [];

    // 1. Handle the "status" keywords from our process files
    if (isset($_GET['status'])) {
        switch ($_GET['status']) {
            case 'purchased':
                $success_msg[] = "Payment Successful! You now have full access to this recipe.";
                break;
            case 'already_owned':
                $info_msg[] = "You already own this recipe. Enjoy!";
                break;
            case 'own_recipe':
                $info_msg[] = "This is your own recipe! You have full access.";
                break;
            case 'error':
                $error_msg[] = "An error occurred with the transaction. Please try again.";
                break;
            case 'recipe_updated':
                $success_msg[] = "Changes saved successfully!";
                break;
            
        }
    }

    // 2. Keep your existing individual checks (Optional: you can move these to the switch later)
    if (isset($_GET['unlocked']) && $_GET['unlocked'] === 'true') {
        $success_msg[] = "Recipe unlocked successfully! Enjoy cooking.";
    }

    if (isset($_GET['saved'])) {
        if ($_GET['saved'] === 'true') {
            $success_msg[] = "Recipe added to your favorites!";
        } elseif ($_GET['saved'] === 'removed') {
            $info_msg[] = "Recipe removed from your favorites.";
        }
    }
    

    // Determine the source of the visit
    $source = $_GET['from'] ?? 'dashboard';

    $recipe_id = $_GET['id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    $is_logged_in = isset($_SESSION['user_id']);

    if (!$recipe_id) {
        header("Location: userdashboard.php");
        exit;
    }

    // 1. Fetch Recipe Data
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        die("Recipe not found.");
    }

    // 2. System Checks (User Flow)
    $is_owner = ($user_id == $recipe['user_id']);
    $is_free = ($recipe['is_paid'] == 0);

    // Check if user already purchased it
    $has_purchased = false;
    if ($user_id && !$is_owner && !$is_free) {
        $p_stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND recipe_id = ?");
        $p_stmt->execute([$user_id, $recipe_id]);
        if ($p_stmt->fetch()) { $has_purchased = true; }
    }

    // Check if user already saved it
    $is_saved = false;
    if ($user_id) {
        $s_stmt = $pdo->prepare("SELECT id FROM saved_recipes WHERE user_id = ? AND recipe_id = ?");
        $s_stmt->execute([$user_id, $recipe_id]);
        if ($s_stmt->fetch()) { 
            $is_saved = true; 
        }
    }

    // FINAL FLAG: Do we show the full content?
    $show_full_content = ($is_free || $is_owner || $has_purchased);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook | View Recipe</title>
    <link rel="stylesheet" href="css/indexcss.css">
    <link rel="stylesheet" href="css/userdashboard.css">
    <link rel="stylesheet" href="css/view_recipe.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="<?php echo (isset($_GET['from']) && $_GET['from'] === 'landing') ? 'landing-layout' : 'dashboard-body'; ?>">
    <?php 
        // Check if the user came from the landing page/gallery
        if (isset($_GET['from']) && $_GET['from'] === 'landing') {
            include 'landingpage_header.php'; 
        } else {
            // Otherwise, they are in the "App" mode (Dashboard)
            include 'sidebar.php'; 
        }
    ?>

<main class="content">
    <div class="recipe-view-container">
        <div class="view-recipe-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 class="page-title" style="margin: 0;">View Recipe</h1>
            
            <?php if ($is_logged_in): ?>
                <div class="recipe-menu-container" style="position: relative;">
                    <button id="menuTrigger" class="menu-dots-btn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #333; padding: 5px;">
                        <ion-icon name="ellipsis-vertical"></ion-icon>
                    </button>
                    
                    <div id="recipeMenu" class="recipe-dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; min-width: 150px; overflow: hidden;">
                        
                        <a href="save_recipe.php?id=<?php echo $recipe_id; ?>" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: #333; gap: 10px; border-bottom: 1px solid #eee;">
                            <ion-icon name="<?php echo $is_saved ? 'bookmark' : 'bookmark-outline'; ?>"></ion-icon>
                            <?php echo $is_saved ? 'Unsave' : 'Save'; ?>
                        </a>

                        <?php if ($is_owner): ?>
                            <a href="update_recipe.php?id=<?php echo $recipe['id']; ?>" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: #333; gap: 10px; border-bottom: 1px solid #eee;">
                                <ion-icon name="create-outline"></ion-icon> Edit
                            </a>
                            <button type="button" id="delete-link" style="display: flex; align-items: center; width: 100%; padding: 12px 16px; background: none; border: none; cursor: pointer; color: #d9534f; gap: 10px; text-align: left; font-family: inherit; font-size: inherit;">
                                <ion-icon name="trash-outline"></ion-icon> Delete
                            </button>
                        <?php else: ?>
                            <button type="button" id="report-link-trigger" style="display: flex; align-items: center; width: 100%; padding: 12px 16px; background: none; border: none; cursor: pointer; color: #333; gap: 10px; text-align: left; font-family: inherit; font-size: inherit;">
                                <ion-icon name="flag-outline"></ion-icon> Report
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="recipe-detail-card">
            <div class="recipe-hero">
                <div class="recipe-main-img">
                    <img src="uploads/<?php echo $recipe['image_url']; ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <div class="recipe-meta">
                        <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <p class="recipe-author">By: <?php 
                            $u_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                            $u_stmt->execute([$recipe['user_id']]);
                            $author = $u_stmt->fetch();
                            echo htmlspecialchars($author['full_name'] ?? 'Unknown'); 
                        ?></p>
                        <span class="price-tag <?php echo $is_free ? 'free' : 'paid'; ?>">
                            <?php echo $is_free ? 'Free' : '₱' . number_format($recipe['price'], 2); ?>
                        </span>
                    </div>
                </div>
                <div class="recipe-description">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                </div>
            </div>

            <hr class="view-divider">

            <div class="recipe-content <?php echo !$show_full_content ? 'content-locked' : ''; ?>">
                <section class="recipe-section-content">
                    <h4>Ingredients:</h4>
                    <div class="<?php echo !$show_full_content ? 'blur-text' : ''; ?>">
                        <?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?>
                    </div>
                </section>

                <hr class="view-divider">

                <section class="recipe-section-content">
                    <h4>Instructions:</h4>
                    <div class="<?php echo !$show_full_content ? 'blur-text' : ''; ?>">
                        <?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?>
                    </div>
                </section>

                <?php if (!$show_full_content): ?>
                    <div class="lock-overlay">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <p>This is a Premium Recipe</p>
                        <?php if (!$user_id): ?>
                            <button class="btn-unlock" onclick="location.href='login.php'">Login to Unlock</button>
                        <?php else: ?>
                            <form action="process_purchase.php" method="POST">
                                <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                <button type="submit" class="btn-unlock">
                                    Unlock for ₱<?php echo number_format($recipe['price'], 2); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="view-actions" style="margin-top: 20px;">
                <a href="<?php echo (isset($_GET['from']) && $_GET['from'] === 'landing') ? 'all_recipes.php' : 'userdashboard.php'; ?>" class="btn-secondary">Back</a>
            </div>
        </div>
    </div>
</main>

   <div id="reportModal" class="modal-overlay" style="display: none;">
    <div class="modal-content report-modal-card">
        <div class="modal-header">
            <h3 style="margin-bottom: 5px; color: #333;">Report Recipe</h3>
            <p style="font-size: 0.9rem; color: #666;">Help us understand what's wrong with this post.</p>
        </div>
        
        <form id="reportForm">
            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
            
            <div class="report-options-container">
                <label class="report-option">
                    <input type="radio" name="reason" value="Inappropriate Content" required>
                    <span class="custom-radio"></span>
                    <span class="label-text">Inappropriate Content</span>
                </label><br>
                <label class="report-option">
                    <input type="radio" name="reason" value="Spam or Misleading">
                    <span class="custom-radio"></span>
                    <span class="label-text">Spam or Misleading</span>
                </label><br>
                <label class="report-option">
                    <input type="radio" name="reason" value="Copyright Infringement">
                    <span class="custom-radio"></span>
                    <span class="label-text">Copyright Infringement</span>
                </label><br>
                <label class="report-option">
                    <input type="radio" name="reason" value="Incorrect Ingredients/Steps">
                    <span class="custom-radio"></span>
                    <span class="label-text">Incorrect Ingredients/Steps</span>
                </label><br>
                <label class="report-option">
                    <input type="radio" name="reason" value="Other">
                    <span class="custom-radio"></span>
                    <span class="label-text">Other</span>
                </label>
            </div>

            <div class="modal-actions" style="margin-top: 25px; display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn-cancel" id="closeReportModal" style="background: #eee; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn-confirm-report" style="background: #ff4757; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<div id="reportSuccessModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="text-align: center; padding: 30px;">
        <ion-icon name="checkmark-circle-outline" style="font-size: 60px; color: #2ed573;"></ion-icon>
        <h3>Report Submitted</h3>
        <p>Thank you for helping us keep ProCook safe. Our team will review this shortly.</p>
        <button onclick="document.getElementById('reportSuccessModal').style.display='none'" style="margin-top: 15px; padding: 10px 25px; border-radius: 8px; border: none; background: #333; color: white; cursor: pointer;">Close</button>
    </div>
</div>

    <div id="deleteModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
                <h2>Delete Recipe?</h2>
            </div>
            <p>Are you sure you want to delete <strong>"<?php echo htmlspecialchars($recipe['title']); ?>"</strong>? This action cannot be undone and you will lose all ingredients and steps.</p>
            <div class="modal-actions">
                <button class="btn-cancel" id="closeModal">No, Keep it</button>
                <form action="process_delete.php" method="POST">
                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                    <button type="submit" class="btn-confirm-delete">Yes, Delete Recipe</button>
                </form>
            </div>
        </div>
    </div>

    <div id="auth-modal" class="modal-overlay" style='display: none;'>
        <div class="modal-content auth-modal-card">
            <button class="close-btn" onclick="closeAuthModal()">&times;</button>
            
            <div class="auth-modal-header">
                <img src="images/procooklogo.png" alt="Logo" class="modal-logo">
                <h2>Ready to start cooking?</h2>
                <p>You need an account to purchase and view this full recipe.</p>
            </div>

            <div class="auth-modal-buttons">
                <a href="login.php" class="btn-modal-login">Login to Purchase</a>
                <a href="sign-up.php" class="btn-modal-signup">Create Free Account</a>
            </div>
            
            <p class="modal-footer-text">Secure payments powered by ProCook</p>
        </div>
    </div>

    <?php 
        // Show the footer ONLY if they are viewing from the landing/public side
        if (isset($_GET['from']) && $_GET['from'] === 'landing') {
            include 'footer.php'; 
        } 
    ?>


    <script>
        const deleteBtn = document.getElementById('delete-link');
        const deleteModal = document.getElementById('deleteModal');
        const closeModal = document.getElementById('closeModal');

        // ONLY run this if deleteBtn actually exists (i.e., the user is the owner)
        if (deleteBtn) {
            // Open Modal
            deleteBtn.onclick = () => {
                deleteModal.style.display = 'flex';
            };

            // Close Modal
            closeModal.onclick = () => {
                deleteModal.style.display = 'none';
            };

            // Close if clicking outside the white box
            window.onclick = (event) => {
                if (event.target == deleteModal) {
                    deleteModal.style.display = 'none';
                }
            };
        }

        const authModal = document.getElementById('auth-modal');

            function openAuthModal() {
                authModal.style.display = 'flex';
            }

            function closeAuthModal() {
                authModal.style.display = 'none';
            }

            // Close auth modal if clicking outside the white box
            window.addEventListener('click', (event) => {
                if (event.target == authModal) {
                    closeAuthModal();
                }
            });

        // Toggle Dropdown Menu
    const menuTrigger = document.getElementById('menuTrigger');
    const recipeMenu = document.getElementById('recipeMenu');

    if (menuTrigger) {
        menuTrigger.onclick = (e) => {
            e.stopPropagation();
            recipeMenu.style.display = recipeMenu.style.display === 'block' ? 'none' : 'block';
        };
    }

    // Report Modal Logic
    const reportLink = document.getElementById('report-link-trigger');
    const reportModal = document.getElementById('reportModal');
    const closeReportModal = document.getElementById('closeReportModal');

    if (reportLink) {
        reportLink.onclick = () => {
            reportModal.style.display = 'flex';
            recipeMenu.style.display = 'none'; // Close menu
        };
    }

    if (closeReportModal) {
        closeReportModal.onclick = () => {
            reportModal.style.display = 'none';
        };
    }

    // Close menu when clicking outside
    window.addEventListener('click', (event) => {
        if (recipeMenu && !recipeMenu.contains(event.target) && event.target !== menuTrigger) {
            recipeMenu.style.display = 'none';
        }
        if (event.target == reportModal) {
            reportModal.style.display = 'none';
        }
    });

    document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch('process_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('reportModal').style.display = 'none';
            document.getElementById('reportSuccessModal').style.display = 'flex';
            this.reset();
        } else {
            alert('Something went wrong: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});
    </script>



    <?php include 'includes/alerts.php'; ?>
</body>
</html>