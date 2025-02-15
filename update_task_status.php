<?php
session_start();
include("db_config.php");

// Function to update project status
function updateProjectStatus($conn, $project_id) {
    // Check tasks status for this project
    $task_status_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks
        FROM tasks 
        WHERE project_id = ?
    ");
    
    $task_status_query->bind_param("i", $project_id);
    $task_status_query->execute();
    $task_counts = $task_status_query->get_result()->fetch_assoc();
    
    // Determine project status
    $new_status = 'Not Started';
    if ($task_counts['total_tasks'] > 0) {
        if ($task_counts['completed_tasks'] == $task_counts['total_tasks']) {
            $new_status = 'Completed';
        } elseif ($task_counts['in_progress_tasks'] > 0) {
            $new_status = 'In Progress';
        }
    }
    
    // Update project status
    $update_project = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $update_project->bind_param("si", $new_status, $project_id);
    $update_project->execute();
}

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "employee") {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    $employee_id = $_SESSION["user_id"];

    // Verify this employee is assigned to this task AND get project_id
    $check_stmt = $conn->prepare("
        SELECT t.project_id 
        FROM tasks t
        LEFT JOIN task_assignments ta ON t.id = ta.task_id
        WHERE t.id = ? AND (ta.employee_id = ? OR t.created_by = ?)
    ");
    $check_stmt->bind_param("iii", $task_id, $employee_id, $employee_id);
    $check_stmt->execute();
    
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        die(json_encode(['error' => 'Not authorized to update this task']));
    }
    
    // Get the project_id
    $project_data = $result->fetch_assoc();
    $project_id = $project_data['project_id'];

    // Update task status
    $update_stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $task_id);
    
    if ($update_stmt->execute()) {
        // Update project status if task update was successful
        if ($project_id) {
            updateProjectStatus($conn, $project_id);
        }
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['error' => 'Failed to update task status']);
    }
}
?>