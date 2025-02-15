<?php
require 'db_config.php'; // Ensure database connection

// Function to add a comment
if (isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    
    $query = "INSERT INTO comments (post_id, content) VALUES ('$post_id', '$content')";
    
    if ($conn->query($query)) {
        header("Location: post.php?post_id=$post_id&success=Comment added successfully");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
