<?php
require_once 'db_connect.php';

$projectId = 1;

$stmtTasks = $pdo->prepare("SELECT * FROM tasks WHERE project_id = :projectId");
$stmtTasks->execute([':projectId' => $projectId]);
$tasks = $stmtTasks->fetchAll();

$taskIds = array_column($tasks, 'id');
$dependencies = [];
if (!empty($taskIds)) {
    $taskIdsList = implode(',', $taskIds);
    $stmtDeps = $pdo->prepare("SELECT * FROM task_dependencies WHERE task_id IN ($taskIdsList)");
    $stmtDeps->execute();
    $dependencies = $stmtDeps->fetchAll();
}

$assignments = [];
$employees = [];
if (!empty($taskIds)) {
    $stmtAssign = $pdo->prepare("
        SELECT ta.task_id, ta.employee_id, u.name 
        FROM task_assignments ta
        JOIN users u ON ta.employee_id = u.id
        WHERE ta.task_id IN ($taskIdsList)
    ");
    $stmtAssign->execute();
    $assignments = $stmtAssign->fetchAll();

    $uniqueEmpIds = [];
    foreach ($assignments as $row) {
        $uniqueEmpIds[$row['employee_id']] = $row['name'];
    }
    foreach ($uniqueEmpIds as $id => $name) {
        $employees[] = ['id' => $id, 'name' => $name];
    }
}

$data = [
    'tasks' => $tasks,
    'dependencies' => $dependencies,
    'employees' => $employees,
    'assignments' => $assignments,
];

header('Content-Type: application/json');
echo json_encode($data);
?>
