<?php
include("db_config.php");

$sql = "SELECT id, name FROM users WHERE role = 'employee'";
$result = $conn->query($sql);

$employees = [];

while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
?>
