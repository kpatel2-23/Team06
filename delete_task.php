<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$employee_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["task_id"])) {
    $task_id = intval($_POST["task_id"]);

    // Ensure the task was created by the logged-in user (team leader/manager)
    $check_query = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ?");
    $check_query->bind_param("ii", $task_id, $employee_id);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        // Delete task assignments first (foreign key constraint handling)
        $conn->query("DELETE FROM task_assignments WHERE task_id = $task_id");

        // Delete the task
        $delete_query = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $delete_query->bind_param("i", $task_id);
        if ($delete_query->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to delete task."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "You are not authorized to delete this task."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
