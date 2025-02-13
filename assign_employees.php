<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = $_POST['project_id'];
    $employee_ids = $_POST['employee_ids'];
    $user_id = $_SESSION["user_id"];
    
    // Verify user is manager or team leader of this project
    $check_stmt = $conn->prepare("
        SELECT 1 
        FROM projects 
        WHERE id = ? 
        AND (manager_id = ? OR team_leader_id = ?)
    ");
    $check_stmt->bind_param("iii", $project_id, $user_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        die(json_encode(['error' => 'Not authorized to modify this project']));
    }
    
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