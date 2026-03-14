<?php
    session_start();
    // Check if the user is logged in
    $is_logged_in = isset($_SESSION['user_id']);

    require_once 'includes/db.php';
    // Fetch the latest 6 recipes for the homepage
    $stmt = $pdo->query("SELECT * FROM recipes ORDER BY created_at DESC LIMIT 6");
    $latest_recipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook - Personalized Recipe Organizer</title>
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/indexcss.css">
</head>
<body>

    <!-- Header -->
    <?php include 'landingpage_header.php'; ?>

    <section id="home" class="hero">
        <h1>Hand-Picked Recipes <br>& Local Flavors.</h1>
        <p>Your seamless gateway to discovering, ordering, and managing the best meals in your community.</p>
        <a href="#menu" class="btn-nav" style="background: var(--primary-orange); color: white; padding: 15px 40px; font-size: 1rem;">Explore the Menu</a>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="section menu">
        <div class="menu-container">
            <h2>Latest Recipes You Should Try</h2>
            <p class="section-subtitle">Discover delicious meals from top local restaurants.</p>
            
            <div class="menu-grid">
                <?php foreach($latest_recipes as $recipe): ?>
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
            </div>

            <div class="load-more-container">
                <a href="all_recipes.php" class="btn-load-more">Explore All Recipes</a>
            </div>
        </div>
    </section>

    <section id="about" class="section about">
        <div class="about-content">
            <h2>Our Story</h2>
            <p>We are a group of passionate students who believe that the best food is often hidden in our own backyards. Founded in 2025, ProCook was built to give local barangay gems the digital stage they deserve.</p>
            <p>Whether you're a hungry neighbor, a kitchen visionary, or a delivery hero, you have a place at our table.</p>
        </div>
    </section>

    <section id="social-contact" class="section social-contact">
    <div class="footer-grid">
        <div class="footer-column">
            <h2>Follow Us</h2>
            <div class="social-links">
                <a href="#" class="social-icon">Facebook</a>
                <a href="#" class="social-icon">Instagram</a>
                <a href="#" class="social-icon">Twitter</a>
            </div>
        </div>

        <div class="footer-column">
            <h2>Contact Us</h2>
            <div class="contact-info">
                <p><strong>Email:</strong> support@foodenterprise.com</p>
                <p><strong>Phone:</strong> +63 900 123 4567</p>
                <p><strong>Address:</strong> Barangay 123, Davao City</p>
            </div>
        </div>
    </div>
</section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
</body>
</html>