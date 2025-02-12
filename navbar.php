<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_config.php");

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT name, role FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
?>

<style>
    /* Navbar Styling */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #333;
        padding: 10px 20px;
        color: white;
    }
    
    .navbar .logo {
        font-size: 22px;
        font-weight: bold;
    }

    .profile {
        position: relative;
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        padding: 10px;
        border-radius: 5px;
        width: 180px;
        text-align: left;
    }

    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
    }

    .dropdown a:hover {
        background: #f2f2f2;
    }

    .dropdown.show {
        display: block;
    }
</style>

<div class="navbar">
    <div class="logo">Make-it-all</div>

    <div class="profile" onclick="toggleDropdown()">
        <img src="profile_icon.png" alt="Profile">
        <span><?php echo htmlspecialchars($user['name']); ?></span>
        <div class="dropdown" id="profileDropdown">
            <a href="#" onclick="openProfileModal()">⚙️ Manage Profile</a>
            <a href="logout.php">🚪 Sign Out</a>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeProfileModal()">&times;</span>
        <h2>Change Password</h2>
        <form id="changePasswordForm">
            <label>New Password:</label>
            <input type="password" name="new_password" required>

            <button type="submit">Update Password</button>
        </form>
    </div>
</div>

<script>
    function toggleDropdown() {
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    function openProfileModal() {
        document.getElementById("profileModal").style.display = "block";
    }

    function closeProfileModal() {
        document.getElementById("profileModal").style.display = "none";
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest(".profile")) {
            document.getElementById("profileDropdown").classList.remove("show");
        }
    };

    // Handle password change via AJAX
    document.getElementById("changePasswordForm").onsubmit = function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        fetch("update_password.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            alert(result);
            closeProfileModal();
        });
    };
</script>

<style>
    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        z-index: 1000;
    }

    .modal-content {
        position: relative;
        width: 300px;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
</style>
