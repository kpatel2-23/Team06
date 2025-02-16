<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Initialize upvoted posts array if not exists
if (!isset($_SESSION['upvoted_posts'])) {
    $_SESSION['upvoted_posts'] = array();
}

require 'db_config.php';

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($post_id <= 0) {
    die("Invalid Post");
}

try {
    // Check if user is logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    // Modify query based on login status
    if ($user_id > 0) {
        // User is logged in - include upvote check
        $query = "SELECT posts.*, users.name as username,
                  CASE WHEN pu.id IS NOT NULL THEN 1 ELSE 0 END as has_upvoted
                  FROM posts 
                  JOIN users ON posts.user_id = users.id 
                  LEFT JOIN post_upvotes pu ON posts.id = pu.post_id AND pu.user_id = ?
                  WHERE posts.id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $user_id, $post_id);
    } else {
        // User is not logged in - simpler query without upvote check
        $query = "SELECT posts.*, users.name as username
                  FROM posts 
                  JOIN users ON posts.user_id = users.id 
                  WHERE posts.id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("i", $post_id);
    }

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if (!$post) {
        throw new Exception("Post not found");
    }

    // Set upvote status
    $has_upvoted = false;
    if ($user_id > 0) {
        $has_upvoted = isset($post['has_upvoted']) ? (bool) $post['has_upvoted'] : false;
        if ($has_upvoted) {
            $_SESSION['upvoted_posts'][$post_id] = true;
        }
    }

    // Fetch comments along with usernames
    $comments_query = "SELECT comments.*, users.name as username 
                      FROM comments 
                      JOIN users ON comments.user_id = users.id 
                      WHERE post_id = ? 
                      ORDER BY created_at ASC";
    $stmt = $conn->prepare($comments_query);
    if ($stmt === false) {
        throw new Exception("Comment query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $post_id);

    if (!$stmt->execute()) {
        throw new Exception("Comment query execution failed: " . $stmt->error);
    }

    $comments_result = $stmt->get_result();
    $comments = $comments_result->fetch_all(MYSQLI_ASSOC);

    // Check if the logged-in user owns this post
    $is_owner = ($user_id > 0) && ($user_id == $post['user_id']);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include("navbar1.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="topics_style.css">
</head>

<body>

    <section class="post-details">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <h1 style="margin-right: 10px;"><?php echo htmlspecialchars($post['title']); ?></h1>
            <a href="topic.php?topic_id=<?php echo $post['topic_id']; ?>"
                style="text-decoration: none; background-color: #007bff; color: white; padding: 8px 12px; border-radius: 5px;">
                🔙 Back to Topic
            </a>
        </div>

        <p>By: <?php echo htmlspecialchars($post['username']); ?></p>
        <p><?php echo htmlspecialchars($post['content']); ?></p>
        <span>Upvotes: <?php echo $post['upvotes']; ?></span>

        <?php if ($user_id > 0): ?>
            <?php if (!$has_upvoted): ?>
                <a href="posts_functions.php?post_id=<?php echo $post_id; ?>&upvote=true">⬆ Upvote</a>
            <?php else: ?>
                <span>✅ You already upvoted this post.</span>
            <?php endif; ?>
        <?php else: ?>
            <span>(Log in to upvote)</span>
        <?php endif; ?>

        <!-- Edit and Delete Buttons -->
        <!-- Edit and Delete Buttons -->
        <?php if ($is_owner): ?>
            <br>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                <a href="edit_post.php?post_id=<?php echo $post_id; ?>"
                    style="text-decoration: none; background-color: #28a745; color: white; padding: 8px 12px; border-radius: 5px; display: inline-block;">
                    ✏ Edit
                </a>
                <form action="posts_functions.php" method="post" style="margin: 0; display: inline;">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <!-- Replace your existing delete button with this -->
                    <button type="button" onclick="showDeleteModal()"
                        style="background-color: #dc3545; color: white; border: none; padding: 10px 12px; border-radius: 5px; cursor: pointer; display: inline-block;">
                        🗑 Delete
                    </button>
                </form>
            </div>
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

        <?php if ($user_id > 0): ?>
            <h3>Add a Comment</h3>
            <form action="comments_functions.php" method="post">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <textarea name="content" required></textarea>
                <button type="submit" name="add_comment">Submit</button>
            </form>
        <?php else: ?>
            <p>Please log in to add comments.</p>
        <?php endif; ?>
    </section>

    <div id="deleteModal" class="modal"
        style="display: none; position: fixed; z-index: 1000; left: 50%; top: 50%; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content"
            style="background-color: #fefefe; margin: 15% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 500px;">
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this post? This action cannot be undone.</p>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button onclick="closeDeleteModal()" style="background-color: #6c757d;">Cancel</button>
                <form id="deleteForm" method="post" action="posts_functions.php" style="margin: 0;">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <input type="hidden" name="delete_post" value="1">
                    <button type="submit" style="background-color: #dc3545;">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add this notification div after your navbar -->
    <div id="notification"
        style="display: none; position: fixed; top: 20px; right: 20px; background-color: #28a745; color: white; padding: 15px; border-radius: 5px; z-index: 1000;">
    </div>

    <script>
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Function to show notification
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Check for success parameter in URL
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            if (success) {
                showNotification(success);
            }
        }
    </script>
</body>


</html>