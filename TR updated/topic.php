<?php


require 'db_config.php';

session_start();
$_SESSION['user_id'] = 1;  // Simulate a logged-in user with ID 1

$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topic_id <= 0) {
    die("Invalid Topic");
}

// Fetch topic details
$query = "SELECT * FROM topics WHERE id = ?";
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
$posts_query = "SELECT posts.*, users.username FROM posts 
                JOIN users ON posts.user_id = users.id 
                WHERE topic_id = ? ORDER BY $sort_order";
$stmt = $conn->prepare($posts_query);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Posts</title>
    <link rel="stylesheet" href="topics_style.css">
</head>
<body>
    <nav>
        <a href="topics.php">Back to Topics</a>
    </nav>

    <section>
        <h1><?php echo htmlspecialchars($topic['title']); ?></h1>
        <p><?php echo htmlspecialchars($topic['description']); ?></p>
    </section>

    <section>
        <h2>Create a New Post</h2>
        <form action="posts_functions.php" method="post">
            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
            <label for="title">Post Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="content">Content:</label>
            <textarea id="content" name="content" required></textarea>

            <label for="attachment">Attach a File:</label>
            <input type="file" id="attachment" name="attachment">

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

        <ul>
            <?php foreach ($posts as $post): ?>
                <li>
                    <a href="post.php?post_id=<?php echo $post['id']; ?>">
                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                    </a>
                    <br>
                    <?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>...
                    <br>
                    <span>By: <?php echo htmlspecialchars($post['username']); ?></span> |
                    <span>Upvotes: <?php echo $post['upvotes']; ?></span>
                    <a href="upvote.php?post_id=<?php echo $post['id']; ?>">⬆ Upvote</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</body>
</html>
