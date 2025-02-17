<?php
session_start();
include("db_config.php");

header('Content-Type: application/json'); // Ensure JSON response

$pass_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[_!"£$%^&*-])[A-Za-z\d_!"£$%^&*-]{8,32}$/';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if passwords are empty
    if (empty($new_password) || empty($confirm_password)) {
        echo json_encode(["status" => "error", "message" => "Password fields cannot be empty."]);
        exit;
    }

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode(["status" => "error", "message" => "Passwords do not match."]);
        exit;
    }

    // Validate password pattern
    if (!preg_match($pass_pattern, $new_password)) {
        echo json_encode(["status" => "error", "message" => "Password must be 8-32 characters, contain at least one uppercase letter, one lowercase letter, one number, and one special character (_!\"£$%^&*-)."]);
        exit;
    }

    $hashed_password = md5($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>


<style>
    /* Notification Styling */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        display: none;
        font-size: 16px;
        z-index: 1000;
    }

    .notification.error {
        background: #dc3545;
    }
</style>


<div id="notification" class="notification"></div>

<script>
document.getElementById("changePasswordForm").addEventListener("submit", function(event) {
    event.preventDefault();

    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    // Client-side validation
    if (newPassword !== confirmPassword) {
        showNotification("Passwords do not match.", "error");
        return;
    }

    let formData = new FormData(this);

    fetch("update_password.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.status === "success" ? "success" : "error");

        if (data.status === "success") {
            closeProfileModal();
            // Clear form
            this.reset();
        }
    })
    .catch(error => {
        console.error("Error updating password:", error);
        showNotification("An error occurred. Please try again.", "error");
    });
});

function showNotification(message, type) {
    let notification = document.getElementById("notification");
    notification.textContent = message;
    notification.classList.remove("error", "success");
    notification.classList.add(type);
    notification.style.display = "block";

    setTimeout(() => {
        notification.style.display = "none";
    }, 3000);
}
</script>