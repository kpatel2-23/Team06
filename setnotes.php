<?php
session_start();
include("db_config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = $_POST['notes'];
    $user_id = $_SESSION['user_id'];
    
    // Update the notes in the database
    $sql = "UPDATE user_notes SET notes = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $notes, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['notes'] = $notes;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>