<?php
session_start();
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

    $errors = [];
    if (empty($project_id)) $errors[] = "Project ID is missing";
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($deadline)) $errors[] = "Deadline is required";
    if (empty($priority)) $errors[] = "Priority is required";
    if (empty($employees)) $errors[] = "At least one employee must be assigned";

    if (!empty($errors)) {
        die("Error: " . implode(", ", $errors));
    }


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
            $assign_stmt = $conn->prepare("INSERT INTO task_assignments (task_id, employee_id) VALUES (?, ?)");
            foreach ($employees as $emp_id) {
                $assign_stmt->bind_param("ii", $task_id, $emp_id);
                $assign_stmt->execute();
            }
            $assign_stmt->close();
        }

        echo json_encode(["success" => true, "message" => "Task added successfully!"]);
    } else {
        echo json_encode(["error" => "Error: " . $stmt->error]);
    }

    $stmt->close();
}
?>

<!-- ✅ JavaScript: Automatically Refresh Assignable Employees After Adding a Task -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // When project is selected, update the employee dropdown
    $("#projectSelect").on("change", function () {
        let projectId = $(this).val();
        if (projectId) {
            loadProjectEmployees(projectId);
        }
    });

    // Function to fetch and update employees dynamically
    function loadProjectEmployees(projectId) {
        $.ajax({
            url: "fetch_project_employees.php",
            type: "POST",
            data: { project_id: projectId },
            cache: false,
            success: function (response) {
                let employees = JSON.parse(response);
                $("#taskEmployees").empty(); // Clear previous options
                employees.forEach(emp => {
                    $("#taskEmployees").append(new Option(emp.name, emp.id));
                });

                // Reinitialize Select2 if used
                $("#taskEmployees").trigger("change");
            },
            error: function () {
                alert("Failed to load employees.");
            }
        });
    }

    // Ensure employee dropdown updates after assigning new employees
    $("#assignEmployeesForm").submit(function (e) {
        e.preventDefault();
        let selectedProjectId = $("#projectSelect").val();
        let selectedEmployees = $("#employeeSelect").val();

        if (!selectedProjectId || selectedEmployees.length === 0) {
            alert("Please select a project and at least one employee.");
            return;
        }

        $.ajax({
            url: "assign_employees.php",
            type: "POST",
            data: { project_id: selectedProjectId, employee_ids: selectedEmployees },
            success: function (response) {
                let data = JSON.parse(response);
                if (data.success) {
                    alert("Employees added successfully!");
                    loadProjectEmployees(selectedProjectId); // Refresh employee dropdown
                } else {
                    alert("Failed: " + data.message);
                }
            }
        });
    });
});
</script>