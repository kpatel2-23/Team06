<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];
    $user_id = $_SESSION["user_id"];
    $user_role = $_SESSION["role"];

    // Different access checks based on role
    if ($user_role == "manager") {
        $check_stmt = $conn->prepare("
            SELECT 1 FROM projects 
            WHERE id = ? AND manager_id = ?
        ");
        $check_stmt->bind_param("ii", $project_id, $user_id);
    } else {
        $check_stmt = $conn->prepare("
            SELECT 1 FROM projects p 
            WHERE p.id = ? AND (p.team_leader_id = ? OR EXISTS (
                SELECT 1 FROM project_assignments pa 
                WHERE pa.project_id = p.id AND pa.employee_id = ?
            ))
        ");
        $check_stmt->bind_param("iii", $project_id, $user_id, $user_id);
    }
    
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        die(json_encode(['error' => 'Project not found or access denied']));
    }

    // Get employees assigned to this project
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.name
        FROM users u
        JOIN project_assignments pa ON u.id = pa.employee_id
        WHERE pa.project_id = ?
        ORDER BY u.name
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($employees);
} else {
    echo json_encode(['error' => 'No project ID provided']);
}
?>