<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'];
    $employee_ids = $_POST['employee_ids'];
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO project_assignments (project_id, employee_id) VALUES (?, ?)");
        
        foreach ($employee_ids as $emp_id) {
            $stmt->bind_param("ii", $project_id, $emp_id);
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Failed to assign employees']);
    }
}
?>