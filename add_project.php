<?php
session_start();
include("db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
        echo "Unauthorized access!";
        exit();
    }

    $title = $_POST["title"];
    $description = $_POST["description"];
    $team_leader = $_POST["team_leader"];
    $employees = $_POST["employees"]; // Array of employee IDs
    $deadline = $_POST["deadline"];
    $priority = $_POST["priority"];
    $status = "Not Started"; // Default status
    $manager_id = $_SESSION["user_id"]; // Get logged-in manager's ID

    // Insert project into projects table with manager_id
    $stmt = $conn->prepare("INSERT INTO projects (title, description, team_leader_id, deadline, priority, status, manager_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssi", $title, $description, $team_leader, $deadline, $priority, $status, $manager_id);

    if ($stmt->execute()) {
        $project_id = $stmt->insert_id; // Get the ID of the newly inserted project

        // Assign employees to the project
        if (!empty($employees) && is_array($employees)) {
            foreach ($employees as $emp_id) {
                $assign_stmt = $conn->prepare("INSERT INTO project_assignments (project_id, employee_id) VALUES (?, ?)");
                $assign_stmt->bind_param("ii", $project_id, $emp_id);
                $assign_stmt->execute();
                $assign_stmt->close();
            }
        }

        echo "Project added successfully!";
    } else {
        echo "Error adding project.";
    }

    $stmt->close();
}
?>
