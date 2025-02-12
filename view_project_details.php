<?php
// view_project_details.php
session_start();
include("db_config.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "manager") {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["project_id"])) {
    $project_id = $_POST["project_id"];
    $manager_id = $_SESSION["user_id"];
    
    // Verify this project belongs to the current manager
    $project_check = $conn->prepare("SELECT title FROM projects WHERE id = ? AND manager_id = ?");
    $project_check->bind_param("ii", $project_id, $manager_id);
    $project_check->execute();
    $project_result = $project_check->get_result();
    
    if ($project_result->num_rows === 0) {
        die(json_encode(['error' => 'Project not found']));
    }
    
    $project_data = $project_result->fetch_assoc();
    
    // Get employee details and their tasks for this project
    $query = "
        SELECT 
            u.id,
            u.name,
            COUNT(DISTINCT t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status != 'Completed' THEN 1 ELSE 0 END) as pending_tasks
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
        'employees' => $employees
    ]);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>