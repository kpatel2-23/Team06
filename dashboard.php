<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION["role"] == "manager") {
    header("Location: manager.php");
} else {
    header("Location: employee.php");
}
exit();
?>
