<?php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

if (isset($_POST['project_id'])) {
    $project_id = $_POST['project_id'];
    $user_id = $_SESSION["user_id"];
    
    // Check if user is either the manager or team leader of this project
    $check_stmt = $conn->prepare("
        SELECT 1 
        FROM projects 
        WHERE id = ? 
        AND (manager_id = ? OR team_leader_id = ?)
    ");
    $check_stmt->bind_param("iii", $project_id, $user_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        die(json_encode(['error' => 'Project not found or access denied']));
    }

    // Get all employees with their task counts, excluding those already assigned to this project
    $query = "
        SELECT 
            u.id,
            u.name,
            COUNT(DISTINCT ta.task_id) as total_tasks
        FROM users u
        LEFT JOIN task_assignments ta ON u.id = ta.employee_id
        WHERE u.role = 'employee'
        AND u.id NOT IN (
            SELECT employee_id 
            FROM project_assignments 
            WHERE project_id = ?
        )
        GROUP BY u.id, u.name
        ORDER BY total_tasks ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    $recommended = [];
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'total_tasks' => (int)$row['total_tasks']
        ];
        
        // Get first two employees with least tasks for recommendations
        if ($count < 2) {
            $recommended[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'total_tasks' => (int)$row['total_tasks']
            ];
            $count++;
        }
    }
    
    echo json_encode([
        'all_employees' => $employees,
        'recommended' => $recommended
    ]);
} else {
    echo json_encode(['error' => 'No project ID provided']);
}
?>