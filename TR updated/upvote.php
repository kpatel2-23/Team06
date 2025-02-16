<?php
require 'db_config.php'; // Database connection

if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);

    // Update the upvotes in the database
    $query = "UPDATE posts SET upvotes = upvotes + 1 WHERE id = $post_id";

    if ($conn->query($query)) {
        header("Location: post.php?post_id=$post_id&success=Upvoted successfully");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
