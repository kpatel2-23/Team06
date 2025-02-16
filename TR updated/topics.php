<?php
require 'db_config.php';
require 'topics_functions.php';

session_start();

// Simulating logged-in user for testing (remove when integrating authentication)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Temporary user ID for testing
}

$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$topics = getFilteredTopics($categoryFilter, $searchQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Topics & Categories</title>
    <link rel="stylesheet" href="topics_style.css">
    <script src="topics.js" defer></script> <!-- Enables live search -->
</head>
<body>
    <nav>
        <a href="dashboard.php">Back to Dashboard</a>
    </nav>

    <header>
        <h1>Knowledge Management System - Topics & Categories</h1>
    </header>

    <section>
        <h2>Search Topics</h2>
        <form action="topics.php" method="GET">
            <input type="text" id="search" name="search" placeholder="Search for topics..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>
        <div id="searchResults"></div> <!-- Live search results -->
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

            <textarea name="description" placeholder="Describe the topic..." required></textarea>
            <button type="submit" name="create_topic">Create Topic</button>
        </form>
    </section>

    <section>
        <h2>Existing Topics</h2>
        <ul id="topicsList">
            <?php foreach ($topics as $topic): ?>
                <?php $username = isset($topic['username']) ? htmlspecialchars($topic['username']) : 'Unknown User'; ?>
                <li>
                    <a href="topic.php?topic_id=<?php echo $topic['id']; ?>">
                        <strong><?php echo htmlspecialchars($topic['title']); ?></strong>
                    </a> - Created by: <?php echo $username; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</body>
</html>
