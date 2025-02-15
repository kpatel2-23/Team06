<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    header("Location: index.php");
    exit();
}

$project_id = $_POST['project_id'];

// Fetch project details
$project_query = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project = $project_query->get_result()->fetch_assoc();

// Fetch employees assigned to the project
$employees_query = $conn->prepare("
    SELECT u.id, u.name 
    FROM users u 
    JOIN project_assignments pa ON u.id = pa.employee_id 
    WHERE pa.project_id = ?
");
$employees_query->bind_param("i", $project_id);
$employees_query->execute();
$employees = $employees_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch tasks for the project
$tasks_query = $conn->prepare("
    SELECT t.*, ta.employee_id 
    FROM tasks t 
    LEFT JOIN task_assignments ta ON t.id = ta.task_id 
    WHERE t.project_id = ?
");
$tasks_query->bind_param("i", $project_id);
$tasks_query->execute();
$tasks = $tasks_query->get_result()->fetch_all(MYSQLI_ASSOC);

$response = [
    'project' => $project,
    'employees' => $employees,
    'tasks' => $tasks
];

echo json_encode($response);
?>