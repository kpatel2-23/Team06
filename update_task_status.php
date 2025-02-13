<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "employee") {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    $employee_id = $_SESSION["user_id"];

    // Verify this employee is assigned to this task OR is the creator
    $check_stmt = $conn->prepare("
        SELECT 1 
        FROM tasks t
        LEFT JOIN task_assignments ta ON t.id = ta.task_id
        WHERE t.id = ? AND (ta.employee_id = ? OR t.created_by = ?)
    ");
    $check_stmt->bind_param("iii", $task_id, $employee_id, $employee_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        die(json_encode(['error' => 'Not authorized to update this task']));
    }

    // Update task status
    $update_stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $task_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['error' => 'Failed to update task status']);
    }
}
?>