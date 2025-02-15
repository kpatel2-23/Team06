<?php
require 'db_config.php'; // Ensure database connection

// Function to fetch topics with optional filtering by category and search query
function getFilteredTopics($category = '', $search = '') {
    global $conn;
    $topics = [];
    
    $query = "SELECT * FROM topics WHERE 1";
    
    if (!empty($category)) {
        $query .= " AND category = '" . mysqli_real_escape_string($conn, $category) . "'";
    }
    
    if (!empty($search)) {
        $query .= " AND (title LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $topics[] = $row;
        }
    }
    
    return $topics;
}

// Function to create a new topic
if (isset($_POST['create_topic'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "INSERT INTO topics (title, category, description) VALUES ('$title', '$category', '$description')";
    
    if ($conn->query($query)) {
        header("Location: topics.php?success=Topic created successfully");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
