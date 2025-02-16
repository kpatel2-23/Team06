<?php
require 'db_config.php';

session_start();


if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to perform this action.");
}

$user_id = $_SESSION['user_id'];

// Create a new post
if (isset($_POST['create_post'])) {
    $topic_id = intval($_POST['topic_id']);
    $user_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $query = "INSERT INTO posts (topic_id, user_id, title, content, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $topic_id, $user_id, $title, $content);

    if ($stmt->execute()) {
        header("Location: topic.php?topic_id=$topic_id&success=Post created successfully");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Edit a post
if (isset($_POST['edit_post'])) {
    $post_id = intval($_POST['post_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    // Handle file upload if a new file is provided
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $attachment = $new_filename;
        }
    }

    // Update query with or without attachment
    if ($attachment) {
        $update_query = "UPDATE posts SET title = ?, content = ?, attachment = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $title, $content, $attachment, $post_id);
    } else {
        $update_query = "UPDATE posts SET title = ?, content = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $title, $content, $post_id);
    }

    if ($stmt->execute()) {
        header("Location: post.php?post_id=$post_id&success=Post updated successfully");
        exit();
    } else {
        die("Error: " . $conn->error);
    }
}

// Delete a post
if (isset($_POST['delete_post'])) {
    $post_id = intval($_POST['post_id']);

    // Get the topic_id before deleting the post (so we can redirect back to topic page)
    $topic_query = "SELECT topic_id FROM posts WHERE id = ?";
    $stmt = $conn->prepare($topic_query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    if (!$post) {
        die("Post not found");
    }
    
    $topic_id = $post['topic_id'];

    // Delete the post
    $delete_query = "DELETE FROM posts WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $post_id);

    if ($stmt->execute()) {
        header("Location: topic.php?topic_id=$topic_id&success=Post deleted successfully");
        exit();
    } else {
        die("Error: " . $conn->error);
    }
}

// Upvote a post
if (isset($_GET['post_id']) && isset($_GET['upvote'])) {
    $post_id = intval($_GET['post_id']);
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if user has already upvoted
        $check_query = "SELECT 1 FROM post_upvotes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            // Insert upvote record
            $insert_query = "INSERT INTO post_upvotes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();

            // Update post upvote count
            $upvote_query = "UPDATE posts SET upvotes = upvotes + 1 WHERE id = ?";
            $stmt = $conn->prepare($upvote_query);
            $stmt->bind_param("i", $post_id);
            $stmt->execute();

            $_SESSION['upvoted_posts'][$post_id] = true;
            
            $conn->commit();
            header("Location: post.php?post_id=$post_id&success=Upvote successful");
            exit();
        } else {
            $conn->rollback();
            header("Location: post.php?post_id=$post_id&error=Already upvoted");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}
?>