<?php
require 'db_config.php';

session_start();

// Get filters from URL
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Get topics from database
$query = "SELECT t.*, u.name as username 
          FROM topics t 
          LEFT JOIN users u ON t.user_id = u.id";

// Add filters if present
if ($categoryFilter || $searchQuery) {
    $query .= " WHERE ";
    if ($categoryFilter) {
        $query .= "t.category = '" . mysqli_real_escape_string($conn, $categoryFilter) . "'";
    }
    if ($categoryFilter && $searchQuery) {
        $query .= " AND ";
    }
    if ($searchQuery) {
        $query .= "(t.title LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%' 
                   OR t.description LIKE '%" . mysqli_real_escape_string($conn, $searchQuery) . "%')";
    }
}

$query .= " ORDER BY t.created_at DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$topics = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include("navbar1.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Topics & Categories</title>
    <link rel="stylesheet" href="topics_style.css">
    <script src="topics.js" defer></script>
</head>
<body>

    <header>
        <h1>Knowledge Management System - Topics & Categories</h1>
    </header>

    <section>
        <h2>Search Topics</h2>
        <form action="topics.php" method="GET">
            <input type="text" id="search" name="search" placeholder="Search for topics..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>
        <div id="searchResults"></div>
    </section>

    <section>
        <h2>Create a New Topic</h2>
        <form action="topics_functions.php" method="post">
            <label for="title">Topic Title:</label>
            <input type="text" id="title" name="title" required>
            
            <label for="category">Select Category:</label>
            <select id="category" name="category">
                <option value="Software Development">Software Development</option>
                <option value="Software Issues">Software Issues</option>
                <option value="Admin Queries">Admin Queries</option>
            </select>

            <label for="description">Description:</label>
            <textarea id="description" name="description" placeholder="Describe the topic..." required></textarea>
            <button type="submit" name="create_topic">Create Topic</button>
        </form>
    </section>

    <section>
        <h2>Existing Topics</h2>
        <ul id="topicsList">
            <?php if (empty($topics)): ?>
                <li>No topics found.</li>
            <?php else: ?>
                <?php foreach ($topics as $topic): ?>
                    <li>
                        <a href="topic.php?topic_id=<?php echo $topic['id']; ?>">
                            <strong><?php echo htmlspecialchars($topic['title']); ?></strong>
                        </a>
                        <?php if (isset($topic['username'])): ?>
                            - Created by: <?php echo htmlspecialchars($topic['username']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </section>
</body>
</html>