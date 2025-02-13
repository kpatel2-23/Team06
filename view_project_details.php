<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["project_id"])) {
    $project_id = $_POST["project_id"];
    $user_id = $_SESSION["user_id"];
    $user_role = $_SESSION["role"];
    
    // Different access checks based on role
    if ($user_role == "manager") {
        $project_check = $conn->prepare("
            SELECT p.title, u.name as team_leader_name 
            FROM projects p
            JOIN users u ON p.team_leader_id = u.id 
            WHERE p.id = ? AND p.manager_id = ?
        ");
        $project_check->bind_param("ii", $project_id, $user_id);
    } else {
        $project_check = $conn->prepare("
            SELECT p.title, u.name as team_leader_name 
            FROM projects p
            JOIN users u ON p.team_leader_id = u.id 
            WHERE p.id = ? AND (p.team_leader_id = ? OR EXISTS (
                SELECT 1 FROM project_assignments pa 
                WHERE pa.project_id = p.id AND pa.employee_id = ?
            ))
        ");
        $project_check->bind_param("iii", $project_id, $user_id, $user_id);
    }
    
    $project_check->execute();
    $project_result = $project_check->get_result();
    
    if ($project_result->num_rows === 0) {
        die(json_encode(['error' => 'Project not found or access denied']));
    }
    
    $project_data = $project_result->fetch_assoc();
    
    // Get employee details and their tasks for this project
    $query = "
        SELECT 
            u.id,
            u.name,
            COUNT(DISTINCT t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status != 'Completed' OR t.status IS NULL THEN 1 ELSE 0 END) as pending_tasks
        FROM users u
        JOIN project_assignments pa ON u.id = pa.employee_id
        LEFT JOIN task_assignments ta ON u.id = ta.employee_id
        LEFT JOIN tasks t ON ta.task_id = t.id AND t.project_id = ?
        WHERE pa.project_id = ?
        GROUP BY u.id, u.name
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $project_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'name' => $row['name'],
            'total_tasks' => (int)$row['total_tasks'],
            'completed_tasks' => (int)$row['completed_tasks'],
            'pending_tasks' => (int)$row['pending_tasks']
        ];
    }
    
    echo json_encode([
        'title' => $project_data['title'],
        'team_leader_name' => $project_data['team_leader_name'],
        'employees' => $employees
    ]);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>