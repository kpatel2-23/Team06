<?php
require 'db_config.php';
session_start();
$_SESSION['user_id'] = 1;  // Simulate a logged-in user with ID 1


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to create a topic.");
}

$user_id = $_SESSION['user_id']; // Get logged-in user ID

// Handle topic creation
if (isset($_POST['create_topic'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Insert the new topic into the database
    $query = "INSERT INTO topics (title, category, description, user_id, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $category, $description, $user_id);

    if ($stmt->execute()) {
        // ✅ Redirect back to topics.php after successful topic creation
        header("Location: topics.php?success=Topic created successfully");
        exit();
    } else {
        // Display an error message if insertion fails
        die("Error creating topic: " . $conn->error);
    }
}

// If no form was submitted, redirect back to topics.php
header("Location: topics.php");
exit();
?>
