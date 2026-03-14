    <header>
       <a href="index.php" class="logo-link" style="text-decoration: none; color: inherit;">
            <div class="logo-container">
                <img src="images/procooklogo.png" alt="ProCook Logo" class="header-logo">
                <span class="logo-text">ProCook</span>
            </div>
        </a>
        <nav>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#menu">Recipes</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#social-contact">Contact</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <?php if ($is_logged_in): ?>
                <a href="userdashboard.php" class="profile-link" title="Go to Dashboard">
                    <ion-icon name="person-circle-outline" style="font-size: 2.5rem; color: white;"></ion-icon>
                </a>
            <?php else: ?>
                <a href="sign-up.php" class="btn-nav">Sign Up</a>
                <a href="login.php" class="btn-nav">Login</a>
            <?php endif; ?>
        </div>
    </header>