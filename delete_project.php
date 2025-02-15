<?php
session_start();
include("db_config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "manager") {
        echo "Unauthorized access!";
        exit();
    }

    $manager_id = $_SESSION["user_id"];
    $project_id = $_POST["project_id"];

    // Check if the manager owns this project
    $check_stmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND manager_id = ?");
    $check_stmt->bind_param("ii", $project_id, $manager_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo "You are not authorized to delete this project.";
        exit();
    }

    // Begin transaction 
    $conn->begin_transaction();

    try {
        // Delete all tasks related to the project
        $stmt = $conn->prepare("DELETE FROM tasks WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Delete all project assignments
        $stmt = $conn->prepare("DELETE FROM project_assignments WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Delete the project itself
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        echo "success"; // Send success response for AJAX
    } catch (Exception $e) {
        $conn->rollback(); // Rollback if any error occurs
        echo "Failed to delete project: " . $e->getMessage();
    }
}
?>
