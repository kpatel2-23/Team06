<?php
require 'db_config.php'; // Database connection
session_start(); // Start session to track upvotes

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

// Fetch comments under this post
$comments_query = "SELECT * FROM comments WHERE post_id = $post_id ORDER BY created_at ASC";
$comments_result = $conn->query($comments_query);
$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}

// Check if user has already upvoted (simple session check)
$has_upvoted = isset($_SESSION['upvoted_posts'][$post_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="topics_style.css">
</head>
<body>
    <nav>
        <a href="topic.php?topic_id=<?php echo $post['topic_id']; ?>">Back to posts</a>
    </nav>

    <!-- Post Title and Content with Border -->
    <section class="post-details">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <p><?php echo htmlspecialchars($post['content']); ?></p>
        <span>Upvotes: <?php echo $post['upvotes']; ?></span>

        <?php if (!$has_upvoted): ?>
            <a href="upvote.php?post_id=<?php echo $post_id; ?>">⬆ Upvote</a>
        <?php else: ?>
            <span>✅ You already upvoted this post.</span>
        <?php endif; ?>

        <!-- Display attachment if available -->
        <?php if ($post['attachment']): ?>
            <p><strong>Attachment:</strong> <a href="<?php echo $post['attachment']; ?>" target="_blank">Download File</a></p>
        <?php endif; ?>

        <!-- Edit and Delete Buttons -->
        <br>
        <a href="edit_post.php?post_id=<?php echo $post_id; ?>">✏ Edit</a>
        <form action="posts_functions.php" method="post" style="display:inline;">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <button type="submit" name="delete_post" onclick="return confirm('Are you sure you want to delete this post?');">🗑 Delete</button>
        </form>
    </section>

    <section>
        <h2>Comments</h2>
        <ul>
            <?php foreach ($comments as $comment): ?>
                <li><?php echo htmlspecialchars($comment['content']); ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Add a Comment</h3>
        <form action="comments_functions.php" method="post">
            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <textarea name="content" required></textarea>
            <button type="submit" name="add_comment">Submit</button>
        </form>
    </section>
</body>
</html>
