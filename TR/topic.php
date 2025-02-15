<?php
require 'db_config.php'; // Database connection

// Get topic ID from URL
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($topic_id <= 0) {
    die("Invalid Topic");
}

// Fetch topic details
$query = "SELECT * FROM topics WHERE id = $topic_id";
$result = $conn->query($query);
$topic = $result->fetch_assoc();
if (!$topic) {
    die("Topic not found");
}

// Determine sorting order
$sort_order = "created_at DESC"; // Default: Most recent
if (isset($_GET['sort']) && $_GET['sort'] == 'upvotes') {
    $sort_order = "upvotes DESC, created_at DESC"; // Sort by upvotes first, then recency
}

// Fetch sorted posts
$posts_query = "SELECT * FROM posts WHERE topic_id = $topic_id ORDER BY $sort_order";
$posts_result = $conn->query($posts_query);
$posts = [];
while ($row = $posts_result->fetch_assoc()) {
    $posts[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - Posts</title>
    <link rel="stylesheet" href="topics_style.css">
</head>
<body>
    <nav>
        <a href="topics.php">Back to Topics</a>
    </nav>

    <!-- Topic Title and Description -->
    <section class="topic-details">
        <h1><?php echo htmlspecialchars($topic['title']); ?></h1>
        <p><?php echo htmlspecialchars($topic['description']); ?></p>
    </section>

    <!-- Post Creation Form -->
    <section>
        <h2>Create a New Post</h2>
        <form action="posts_functions.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
            <label for="title">Post Title:</label>
            <input type="text" id="title" name="title" required>
            <label for="content">Content:</label>
            <textarea id="content" name="content" required></textarea>

            <!-- File Upload Input -->
            <label for="attachment">Attach a File:</label>
            <input type="file" id="attachment" name="attachment">

            <button type="submit" name="create_post">Create Post</button>
        </form>
    </section>

    <!-- Posts List -->
    <section>
        <h2>Posts</h2>

        <!-- Sorting Dropdown -->
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
                    <span>Upvotes: <?php echo $post['upvotes']; ?> </span>
                    <a href="upvote.php?post_id=<?php echo $post['id']; ?>">⬆ Upvote</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</body>
</html>
