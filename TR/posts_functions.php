<?php
require 'db_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Fix: Handle Post Creation
if (isset($_POST['create_post'])) {
    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : '';
    $content = isset($_POST['content']) ? mysqli_real_escape_string($conn, $_POST['content']) : '';

    if ($topic_id <= 0 || empty($title) || empty($content)) {
        die("Error: Missing required fields.");
    }

    $attachment_path = NULL;

    // Handle file upload if a file is provided
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $upload_dir = "uploads/";
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'];
        $file_name = basename($_FILES["attachment"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($file_ext, $allowed_types)) {
            die("Error: Invalid file type. Allowed types: pdf, doc, docx, jpg, png, jpeg.");
        }

        // Move file to upload directory
        $new_file_name = uniqid() . "." . $file_ext;
        $attachment_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $attachment_path)) {
            die("Error: File upload failed.");
        }
    }

    // Insert into database
    $query = "INSERT INTO posts (topic_id, title, content, attachment) VALUES ('$topic_id', '$title', '$content', '$attachment_path')";

    if ($conn->query($query)) {
        $new_post_id = $conn->insert_id;
        header("Location: post.php?post_id=$new_post_id&success=Post created successfully");
        exit();
    } else {
        die("Database Error: " . $conn->error);
    }
}

// ✅ Fix: Handle Post Editing
if (isset($_POST['edit_post'])) {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : '';
    $content = isset($_POST['content']) ? mysqli_real_escape_string($conn, $_POST['content']) : '';
    
    if ($post_id <= 0 || empty($title) || empty($content)) {
        die("Error: Missing required fields.");
    }

    // Handle file upload if a new file is provided
    $attachment_path = NULL;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['size'] > 0) {
        $upload_dir = "uploads/";
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'];
        $file_name = basename($_FILES["attachment"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($file_ext, $allowed_types)) {
            die("Error: Invalid file type. Allowed types: pdf, doc, docx, jpg, png, jpeg.");
        }

        // Move file to upload directory
        $new_file_name = uniqid() . "." . $file_ext;
        $attachment_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $attachment_path)) {
            die("Error: File upload failed.");
        }

        // Update post with new attachment
        $query = "UPDATE posts SET title='$title', content='$content', attachment='$attachment_path' WHERE id='$post_id'";
    } else {
        // Update post without changing attachment
        $query = "UPDATE posts SET title='$title', content='$content' WHERE id='$post_id'";
    }

    if ($conn->query($query)) {
        header("Location: post.php?post_id=$post_id&success=Post updated successfully");
        exit();
    } else {
        die("Database Error: " . $conn->error);
    }
}

// ✅ Fix: Handle Post Deletion
if (isset($_POST['delete_post'])) {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        die("Error: Invalid post ID.");
    }

    // Fetch the post to check if it has an attachment
    $query = "SELECT attachment FROM posts WHERE id = $post_id";
    $result = $conn->query($query);
    $post = $result->fetch_assoc();

    if (!$post) {
        die("Error: Post not found.");
    }

    // Delete the attachment file if it exists
    if (!empty($post['attachment']) && file_exists($post['attachment'])) {
        unlink($post['attachment']);
    }

    // Delete the post from the database
    $delete_query = "DELETE FROM posts WHERE id = $post_id";

    if ($conn->query($delete_query)) {
        header("Location: topics.php?success=Post deleted successfully");
        exit();
    } else {
        die("Database Error: " . $conn->error);
    }
}
?>
