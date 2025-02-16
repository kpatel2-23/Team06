<?php
require 'db_config.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to create a topic.");
}

$user_id = $_SESSION['user_id'];

// Handle topic creation
if (isset($_POST['create_topic'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $query = "INSERT INTO topics (title, category, description, user_id, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $category, $description, $user_id);

    if ($stmt->execute()) {
        header("Location: topics.php?success=Topic created successfully");
        exit();
    } else {
        die("Error creating topic: " . $conn->error);
    }
}

// Handle search requests (for AJAX)
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = "SELECT * FROM topics WHERE title LIKE '%$search%' OR description LIKE '%$search%'";
    $result = $conn->query($query);
    $topics = $result->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($topics);
    exit();
}
?>