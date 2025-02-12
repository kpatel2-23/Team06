<?php
session_start(); // Ensure session is started
include("db_config.php");

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    die("Error: User session not found. Please log in again.");
}

$created_by = $_SESSION["user_id"]; // Get the logged-in Team Leader ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST["project_id"];
    $title = $_POST["title"];
    $description = $_POST["description"];
    $deadline = $_POST["deadline"];
    $priority = $_POST["priority"];
    $employees = isset($_POST["employees"]) ? $_POST["employees"] : [];

    // Ensure required fields are not empty
    if (empty($title) || empty($description) || empty($project_id) || empty($deadline) || empty($priority)) {
        die("Error: All fields are required.");
    }

    // Insert the task into the database
    $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description, status, priority, deadline, created_by) VALUES (?, ?, ?, 'Not Started', ?, ?, ?)");
    $stmt->bind_param("issssi", $project_id, $title, $description, $priority, $deadline, $created_by);

    if ($stmt->execute()) {
        $task_id = $stmt->insert_id;

        // Assign employees to the task
        if (!empty($employees)) {
            foreach ($employees as $emp_id) {
                $assign_stmt = $conn->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (?, ?)");
                $assign_stmt->bind_param("ii", $task_id, $emp_id);
                $assign_stmt->execute();
                $assign_stmt->close();
            }
        }

        echo "Task added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
