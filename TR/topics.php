<?php
require 'db_config.php'; // Database connection
require 'topics_functions.php'; // Backend functions

$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$topics = getFilteredTopics($categoryFilter, $searchQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topics & Categories</title>
    <link rel="stylesheet" href="topics_style.css"> <!-- Custom CSS -->
    <script src="js/topics.js" defer></script> <!-- JavaScript for interactivity -->
</head>
<body>
    <nav>
        <a href="dashboard.php">Back to Dashboard</a>
    </nav>
    <header>
        <h1>Knowledge Management System - Topics & Categories</h1>
    </header>
    
    <!-- Search Bar and Category Dropdown -->
    <section>
        <h2>Search Topics</h2>
        <form action="topics.php" method="GET">
            <input type="text" id="search" name="search" placeholder="Search for topics..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            
            <label for="categoryFilter"><strong>Filter by Categories:</strong></label>
            <select id="categoryFilter" name="category">
                <option value="" <?php echo $categoryFilter === '' ? 'selected' : ''; ?>>All Categories</option>
                <option value="Software Development" <?php echo $categoryFilter === 'Software Development' ? 'selected' : ''; ?>>Software Development</option>
                <option value="Software Issues" <?php echo $categoryFilter === 'Software Issues' ? 'selected' : ''; ?>>Software Issues</option>
                <option value="Admin Queries" <?php echo $categoryFilter === 'Admin Queries' ? 'selected' : ''; ?>>Admin Queries</option>
            </select>
            <button type="submit">Search</button>
        </form>
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
            <textarea id="description" name="description" required></textarea>
            
            <button type="submit" name="create_topic">Create Topic</button>
        </form>
    </section>

    <section>
        <h2>Existing Topics</h2>
        <ul id="topicsList">
            <?php foreach ($topics as $topic): ?>
                <li>
                    <a href="topic.php?topic_id=<?php echo $topic['id']; ?>">
                        <strong><?php echo htmlspecialchars($topic['title']); ?></strong>
                    </a> - <?php echo htmlspecialchars($topic['category']); ?>
                    <br><?php echo htmlspecialchars($topic['description']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <footer>
        <p>&copy; 2025 Knowledge Management System</p>
    </footer>
</body>
</html>
