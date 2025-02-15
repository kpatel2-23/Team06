<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    header("Location: index.php");
    exit();
}

$task_id = $_POST['task_id'];
$new_employee_id = $_POST['employee_id'];

// Fetch the current employee assignment for the task
$current_assignment_query = $conn->prepare("SELECT employee_id FROM task_assignments WHERE task_id = ?");
$current_assignment_query->bind_param("i", $task_id);
$current_assignment_query->execute();
$current_assignment = $current_assignment_query->get_result()->fetch_assoc();

$old_employee_id = $current_assignment['employee_id'];

// Remove the old assignment
if ($old_employee_id) {
    $delete_query = $conn->prepare("DELETE FROM task_assignments WHERE task_id = ? AND employee_id = ?");
    $delete_query->bind_param("ii", $task_id, $old_employee_id);
    $delete_query->execute();
}

// Create the new assignment
$insert_query = $conn->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (?, ?)");
$insert_query->bind_param("ii", $task_id, $new_employee_id);
$insert_query->execute();

echo json_encode(['success' => true]);
?>