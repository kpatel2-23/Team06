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
        z-index: 1000;
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
        z-index: 1000;
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

    /* Error message */
    .error-message {
        color: red;
        font-size: 14px;
        margin-top: 5px;
    }

    /* Toast Notification */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        display: none;
        z-index: 2000;
    }

    .toast.error {
        background: #28a745;
    }

    .password-container {
        position: relative;
        width: 90%;
        margin: 10px auto;
    }

    .password-toggle {
        position: absolute;
        right: 2px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none !important;
        cursor: pointer;
        padding: 5px;
        height: 100%;
        display: flex;
        align-items: center;
        z-index: 2;
        width: 40px;
    }

    .password-toggle:hover {
        opacity: 0.7;
        background: none !important;
        transform: translateY(-50%) !important;
        box-shadow: none !important;
    }

    .eye-icon {
        width: 20px;
        height: 20px;
        fill: #666;
    }

    .password-container .input-field {
        width: 100%;
        padding-right: 45px;
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
            <a href="topics.php">📚 Go to Knowledge</a>
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
            <div class="password-container">
                <input type="password" class="input-field" name="new_password" id="newPassword"
                    placeholder="New Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                    <svg class="eye-icon" viewBox="0 0 24 24">
                        <path
                            d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                    </svg>
                </button>
            </div>
            <div class="password-container">
                <input type="password" class="input-field" name="confirm_password" id="confirmPassword"
                    placeholder="Confirm Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                    <svg class="eye-icon" viewBox="0 0 24 24">
                        <path
                            d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                    </svg>
                </button>
            </div>
            <div id="passwordError" class="error-message"></div>
            <button type="submit" class="save-btn">Update Password</button>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

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

    function showToast(message, type = "success") {
        let toast = document.getElementById("toast");
        toast.textContent = message;
        toast.classList.remove("error");
        if (type === "error") toast.classList.add("error");
        toast.style.display = "block";

        setTimeout(() => {
            toast.style.display = "none";
        }, 3000);
    }


    document.getElementById("changePasswordForm").addEventListener("submit", function (event) {
        event.preventDefault();

        const newPassword = document.getElementById("newPassword").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        let passwordError = document.getElementById("passwordError");

        // Check if passwords match
        if (newPassword !== confirmPassword) {
            passwordError.textContent = "Passwords do not match";
            return;
        }

        // Check password format (you can adjust these requirements)
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!"£$%^&*-])[a-zA-Z0-9_!"£$%^&*-]{8,32}$/;
        if (!passwordRegex.test(newPassword)) {
            passwordError.textContent = "Password must be 8-32 characters, include uppercase, lowercase, a number, and a symbol";
            return;
        }

        let formData = new FormData(this);

        fetch("update_password.php", {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.json();
            })
            .then(data => {
                if (data.status === "success") {
                    passwordError.textContent = ""; // Clear any previous errors
                    showToast("✅ " + data.message); // Show success toast
                    closeProfileModal();
                } else {
                    passwordError.textContent = data.message; // Show error under input field
                }
            })
            .catch(error => {
                console.error("Error updating password:", error);
                passwordError.textContent = "Password Update Successful";
            });
    });

    function showToast(message, type = "success") {
        let toast = document.getElementById("toast");
        toast.textContent = message;
        toast.classList.remove("error");
        if (type === "error") toast.classList.add("error");
        toast.style.display = "block";

        setTimeout(() => {
            toast.style.display = "none";
        }, 3000);
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('.eye-icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = `<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>`;
        } else {
            input.type = 'password';
            icon.innerHTML = `<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>`;
        }
    }


</script>