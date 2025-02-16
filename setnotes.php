<?php
session_start();

include("db_config.php");

$user_id = $_SESSION['user_id'];
$notes_raw = file_get_contents("php://input");

$sql = "UPDATE user_notes SET notes = '$notes_raw' WHERE user_id = $user_id;";
$result = $conn->query($sql);

echo $result;