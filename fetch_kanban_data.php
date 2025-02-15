<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$project_id = $_POST['project_id'];

// Check if the user is either the manager or the team leader of this project
$auth_check = $conn->prepare("
    SELECT 1 FROM projects 
    WHERE id = ? 
    AND (manager_id = ? OR team_leader_id = ?)
");
$auth_check->bind_param("iii", $project_id, $_SESSION["user_id"], $_SESSION["user_id"]);
$auth_check->execute();
if ($auth_check->get_result()->num_rows === 0) {
    header("Location: index.php");
    exit();
}

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