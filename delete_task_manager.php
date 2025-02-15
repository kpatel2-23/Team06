<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

if (isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $manager_id = $_SESSION['user_id'];

    // Check if the task belongs to a project managed by the logged-in manager
    $check_query = $conn->prepare("
        SELECT p.id 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        WHERE t.id = ? AND p.manager_id = ?
    ");
    $check_query->bind_param("ii", $task_id, $manager_id);
    $check_query->execute();
    $check_query->store_result();

    if ($check_query->num_rows > 0) {
        // Delete the task
        $delete_query = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $delete_query->bind_param("i", $task_id);
        if ($delete_query->execute()) {
            echo json_encode(["success" => true, "message" => "Task deleted successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error deleting task."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Task not found or you don't have permission to delete it."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>