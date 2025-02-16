<?php
require 'db_config.php';

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($post_id <= 0) {
    die("Invalid Post");
}

// Fetch post details
$query = "SELECT * FROM posts WHERE id = $post_id";
$result = $conn->query($query);
$post = $result->fetch_assoc();
if (!$post) {
    die("Post not found");
}
?>

<?php include("navbar1.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="stylesheet" href="topics_style.css">
</head>
<body>

    
    <header>
        <h1>Edit Post</h1>
    </header>

    <section>
        <form action="posts_functions.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            
            <label for="content">Content:</label>
            <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

            <!-- File Upload -->
            <label for="attachment">Change Attachment (Optional):</label>
            <input type="file" id="attachment" name="attachment">
            
            <button type="submit" name="edit_post">Save Changes</button>
        </form>
    </section>
</body>
</html>