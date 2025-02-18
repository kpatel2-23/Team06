<?php
require 'db_config.php';

session_start();

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topic_id <= 0) {
    die("Invalid Topic");
}

// Fetch topic details
$query = "SELECT t.*, u.name as username 
          FROM topics t
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE t.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();
$topic = $result->fetch_assoc();
if (!$topic) {
    die("Topic not found");
}

// Sorting method
$sort_order = "created_at DESC"; // Default: Most recent
if (isset($_GET['sort']) && $_GET['sort'] == 'upvotes') {
    $sort_order = "upvotes DESC, created_at DESC";
}

// Fetch posts under this topic with usernames
$posts_query = "SELECT p.*, u.name as username 
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.topic_id = ? 
                ORDER BY $sort_order";
$stmt = $conn->prepare($posts_query);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);
?>
<?php include("navbar1.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Posts</title>
    <link rel="stylesheet" href="topics_style.css">
    <?php include 'loader.php'; ?> <!-- Include Loader -->
</head>

<body>

    <!-- Title and Back to Knowledge Forum Button -->
    <section style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1><?php echo htmlspecialchars($topic['title']); ?></h1>
            <p><?php echo htmlspecialchars($topic['description']); ?></p>
        </div>
        <a href="topics.php"
            style="text-decoration: none; background-color: #007bff; color: white; padding: 8px 12px; border-radius: 5px; font-weight: bold;">
            🔙 Back to Knowledge Forum
        </a>
    </section>

    <section>
        <h2>Create a New Post</h2>
        <form action="posts_functions.php" method="post">
            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
            <label for="title">Post Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="content">Content:</label>
            <textarea id="content" name="content" required></textarea>

            <button type="submit" name="create_post">Create Post</button>
        </form>
    </section>

    <section>
        <h2>Posts</h2>

        <form method="GET" action="topic.php">
            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
            <label for="sort">Sort By:</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
                <option value="recent" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'recent') ? 'selected' : ''; ?>>Most Recent</option>
                <option value="upvotes" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'upvotes') ? 'selected' : ''; ?>>Most Upvoted</option>
            </select>
        </form>

        <ul id="postsList">
            <?php if (empty($posts)): ?>
                <li>No posts yet in this topic.</li>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <li>
                        <a href="post.php?post_id=<?php echo $post['id']; ?>">
                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                        </a>
                        <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...</p>
                        <div class="post-meta">
                            By: <?php echo htmlspecialchars($post['username'] ?? 'Unknown User'); ?> |
                            Upvotes: <?php echo $post['upvotes']; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </section>

</body>

</html>