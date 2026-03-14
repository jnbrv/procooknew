<?php
require_once 'includes/db.php';
session_start();

// --- SWEETALERT LOGIC START ---
$error_msg = [];
$success_msg = [];

if (isset($_GET['status']) && $_GET['status'] === 'error') {
    $error_msg[] = "Failed to update recipe. Please try again.";
}
// --- SWEETALERT LOGIC END ---

$recipe_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$recipe_id || !$user_id) {
    header("Location: userdashboard.php");
    exit;
}

// 1. Fetch the recipe and check ownership
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
$stmt->execute([$recipe_id, $user_id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header("Location: userdashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProCook | Update Recipe</title>
    <link rel="stylesheet" href="css/userdashboard.css">
    <link rel="stylesheet" href="css/view_recipe.css">
    <link rel="icon" type="image/png" href="images/procooklogo.png">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="dashboard-body">

    <?php include 'sidebar.php'; ?>

    <main class="content">
    <div class="update-recipe-container">
        <h1 class="page-title">Edit Your Recipe</h1>

        <form action="process_update.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
            
            <div class="update-grid-top">
                <div class="form-fields">
                    <div class="dashboard-input-group">
                        <label>Recipe Name:</label>
                        <input type="text" name="recipe_title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required>
                    </div>
                    <div class="dashboard-input-group">
                        <label>Description:</label>
                        <textarea name="recipe_desc" rows="4"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                    </div>
                </div>

                <div class="image-upload-section">
                    <label>Recipe Image:</label>
                    <div id="edit-drop-zone" class="universal-drop-zone" style="cursor: pointer;">
                        <img src="uploads/<?php echo $recipe['image_url']; ?>" alt="Current Recipe Image" class="img-preview-el">
                        <input type="file" name="recipe_image" id="edit-recipe-input" hidden>
                    </div>
                    <p class="upload-hint">Click the image or drag a new one to replace it.</p>
                </div>
            </div>

            <div class="dashboard-input-group full-width">
                <label>Ingredients:</label>
                <textarea name="ingredients" rows="6"><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea>
            </div>

            <div class="dashboard-input-group full-width">
                <label>Procedure:</label>
                <textarea name="procedure" rows="8"><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
            </div>

            <div class="update-actions">
                <div class="action-group-left">
                    <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-secondary">Cancel</a>
                    <button type="submit" class="orange-btn">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    // Quick function to preview the image before uploading
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<script src="js/userdashboard.js"></script>
<?php include 'includes/alerts.php'; ?>
</body>
</html>