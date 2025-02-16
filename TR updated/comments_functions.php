<?php

session_start();
$_SESSION['user_id'] = 1;  // Simulate a logged-in user with ID 1

require 'db_config.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to comment.");
}

$user_id = $_SESSION['user_id'];

// Function to add a comment
if (isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $query = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    
    if ($stmt->execute()) {
        header("Location: post.php?post_id=$post_id&success=Comment added successfully");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
