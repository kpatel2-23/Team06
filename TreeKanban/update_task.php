<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId      = $_POST['task_id'] ?? null;
    $newStatus   = $_POST['status']   ?? null;
    $newEmployee = $_POST['employee_id'] ?? null;

    if ($taskId !== null && $newStatus !== null && $newEmployee !== null) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id'     => $taskId
        ]);

        $stmt2 = $pdo->prepare("UPDATE task_assignments SET employee_id = :empId WHERE task_id = :taskId");
        $stmt2->execute([
            ':empId'  => $newEmployee,
            ':taskId' => $taskId
        ]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
