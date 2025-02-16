<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("db_config.php");

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT name, role, email FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

    /* Navbar Styling */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #333;
        padding: 15px 20px;
        color: white;
        font-family: 'Poppins', sans-serif;
        border-radius: 10px;
    }

    .navbar .left-section {
        display: flex;
        align-items: center;
        font-size: 1.2rem;
    }

    .navbar .left-section img {
        height: 60px;
        margin-right: 10px;
    }

    .navbar .center-section {
        flex-grow: 1;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        margin-right: 120px;
    }

    .navbar .profile {
        position: relative;
        cursor: pointer;
    }

    .navbar .profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    /* Dropdown Styling */
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

    /* Profile Modal Styling */
    .modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        width: 400px;
        max-width: 90%;
        font-family: 'Poppins', sans-serif;
        text-align: center;
    }

    .nav-modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 600px;
        max-width: 90%;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .profile-info {
        margin-bottom: 15px;
        width: 100%;
    }

    .profile-info p {
        margin: 5px 0;
        font-size: 16px;
    }

    .input-field {
        width: 90%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .save-btn {
        background-color: #333;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 90%;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 18px;
        cursor: pointer;
    }
</style>

<div class="navbar">
    <div class="left-section">
        <img src="TP_LOGO.png" alt="Logo">
        <span>Make-It-All</span>
    </div>

    <div class="center-section">
        Task Management System
    </div>

    <div class="profile" onclick="toggleDropdown()">
        <img src="TPPP.png" alt="Profile">
        <div class="dropdown" id="profileDropdown">
            <a href="#" onclick="openProfileModal()">⚙️ Manage Profile</a>
            <a href="dashboard.php">📚 Go to Dashboard</a>
            <a href="logout.php">🚪 Sign Out</a>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="nav-modal-content">
        <span class="close" onclick="closeProfileModal()">&times;</span>
        <h2>Manage Profile</h2>
        <div class="profile-info">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <form id="changePasswordForm">
            <input type="password" class="input-field" name="new_password" placeholder="New Password" required>
            <button type="submit" class="save-btn">Update Password</button>
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

    window.onclick = function (event) {
        if (!event.target.closest(".profile")) {
            document.getElementById("profileDropdown").classList.remove("show");
        }
    };
</script>