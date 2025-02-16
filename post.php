<?php
session_start();
$_SESSION['user_id'] = 1;  // Simulate a logged-in user with ID 1


require 'db_config.php';
session_start();

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($post_id <= 0) {
    die("Invalid Post");
}

// Fetch post details along with username
$query = "SELECT posts.*, users.username FROM posts 
          JOIN users ON posts.user_id = users.id 
          WHERE posts.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
if (!$post) {
    die("Post not found");
}

// Fetch comments along with usernames
$comments_query = "SELECT comments.*, users.username FROM comments 
                   JOIN users ON comments.user_id = users.id 
                   WHERE post_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($comments_query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_result = $stmt->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);

// Check if the logged-in user owns this post
$is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id'];

// Check if user has upvoted
$has_upvoted = isset($_SESSION['upvoted_posts'][$post_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="topics_style.css">
</head>
<body>
    <nav>
        <a href="topic.php?topic_id=<?php echo $post['topic_id']; ?>">Back to Topic</a>
    </nav>

    <section class="post-details">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <p>By: <?php echo htmlspecialchars($post['username']); ?></p>
        <p><?php echo htmlspecialchars($post['content']); ?></p>
        <span>Upvotes: <?php echo $post['upvotes']; ?></span>

        <?php if (!$has_upvoted): ?>
            <a href="posts_functions.php?post_id=<?php echo $post_id; ?>&upvote=true">⬆ Upvote</a>
        <?php else: ?>
            <span>✅ You already upvoted this post.</span>
        <?php endif; ?>

        <!-- Edit and Delete Buttons -->
        <?php if ($is_owner): ?>
            <br>
            <a href="edit_post.php?post_id=<?php echo $post_id; ?>">✏ Edit</a>
            <form action="posts_functions.php" method="post" style="display:inline;">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <button type="submit" name="delete_post" onclick="return confirm('Are you sure you want to delete this post?');">🗑 Delete</button>
            </form>
        <?php endif; ?>
    </section>

    <section>
        <h2>Comments</h2>
        <ul>
            <?php foreach ($comments as $comment): ?>
                <li>
                    <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                    <?php echo htmlspecialchars($comment['content']); ?>
                </li>
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