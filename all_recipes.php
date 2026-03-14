<?php
    session_start();
    $is_logged_in = isset($_SESSION['user_id']);
    require_once 'includes/db.php';

    // Fetch all recipes from the database
    $stmt = $pdo->query("SELECT * FROM recipes ORDER BY title ASC");
    $all_recipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Recipes - ProCook</title>
    <link rel="stylesheet" href="css/indexcss.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
<body>

    <!-- Header -->
    <?php include 'landingpage_header.php'; ?>

    <section class="section all-recipes-hero">
        <div class="container">
            <h1>Explore All Recipes</h1>
            <p>From local favorites to international delights.</p>
            
            <div class="search-bar-container">
                <div class="search-input-wrapper">
                    <ion-icon name="search-outline" class="search-icon"></ion-icon>
                    <input type="text" placeholder="Search for a specific dish..." id="gallery-search">
                </div>
            </div>
        </div>
    </section>

    <section class="section gallery">
        <div class="menu-grid">
            <?php if(empty($all_recipes)): ?>
                <div style="grid-column: 1 / -1; text-align: center;">
                    <ion-icon name="restaurant-outline" style="font-size: 3rem; color: #ccc;"></ion-icon>
                    <p class="empty-msg" style="grid-column: 1 / -1; text-align: center;">No recipes found.</p>
                </div>
            <?php else: ?>
                <?php foreach($all_recipes as $recipe): ?>
                    <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>&from=landing" class="recipe-card-link" style="text-decoration: none; color: inherit;">
                        <div class="recipe-card">
                            <div class="recipe-img-container">
                                <img src="uploads/<?php echo htmlspecialchars($recipe['image_url']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                            </div>
                            <div class="recipe-info">
                                <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                                <p class="recipe-price">Price: ₱<?php echo number_format($recipe['price'], 2); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

     <!-- Footer -->
     <?php include 'footer.php'; ?>
    <script>
        const searchInput = document.getElementById('gallery-search');
        // Change selector to .recipe-card-link
        const recipeLinks = document.querySelectorAll('.recipe-card-link');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            recipeLinks.forEach(link => {
                const title = link.querySelector('.recipe-info h4').textContent.toLowerCase();
                if (title.includes(query)) {
                    link.style.display = '';
                } else {
                    link.style.display = 'none';
                }
            });
        });
    </script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
</body>
</html>