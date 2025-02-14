<?php
session_start();
include("db_config.php");

// Initialize response array
$response = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the user is authorized
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
        $response["message"] = "Unauthorized access!";
        echo json_encode($response);
        exit();
    }

    // Retrieve form data
    $title = $_POST["title"];
    $description = $_POST["description"];
    $team_leader = $_POST["team_leader"];
    $employees = $_POST["employees"]; // Array of employee IDs
    $deadline = $_POST["deadline"];
    $priority = $_POST["priority"];
    $status = "Not Started"; // Default status
    $manager_id = $_SESSION["user_id"]; // Get logged-in manager's ID

    // Validate required fields
    if (empty($title) || empty($description) || empty($team_leader) || empty($deadline) || empty($priority)) {
        $response["message"] = "All fields are required!";
        echo json_encode($response);
        exit();
    }

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
                if (!$assign_stmt->execute()) {
                    $response["message"] = "Error assigning employees to the project.";
                    echo json_encode($response);
                    exit();
                }
                $assign_stmt->close();
            }
        }

        // Success response
        $response["success"] = true;
        $response["message"] = "Project added successfully!";
    } else {
        $response["message"] = "Error adding project: " . $stmt->error;
    }

    $stmt->close();
} else {
    $response["message"] = "Invalid request method.";
}

// Close the database connection
$conn->close();

// Return JSON response
echo json_encode($response);
?>