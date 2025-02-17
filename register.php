<?php
session_start();

const _EMAIL = '/^[a-zA-Z0-9]+@make-it-all.co.uk$/';
const _PASS = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!"£$%^&*-])[a-zA-Z0-9_!"£$%^&*-]{8,32}$/';

include("db_config.php");

$errors = ['name' => '', 'email' => '', 'password' => '', 'confirm_password' => '', 'mgrpass' => ''];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $name = trim($_POST['name']);
    $mgrpass = trim($_POST['mgrpass']);

    if ($name == '') {
        $errors['name'] = "Please enter your full name.";
    }

    if ($email == '') {
        $errors['email'] = "Please enter your company email.";
    } elseif (!preg_match(_EMAIL, $email)) {
        $errors['email'] = "Invalid company email format. Must be '@make-it-all.co.uk'.";
    }

    if ($password == '') {
        $errors['password'] = "Please enter a password.";
    } elseif (!preg_match(_PASS, $password)) {
        $errors['password'] = "Password must be 8-32 characters, include uppercase, lowercase, a number, and a symbol.";
    }

    if ($confirm_password == '') {
        $errors['confirm_password'] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    $sql = "SELECT `email` FROM `users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && $result->num_rows > 0) {
        $errors['email'] = "An account with this email already exists.";
    }

    if (array_filter($errors)) {
        // If there are errors, they will be displayed on the form.
    } else {
        $enc_password = md5($password);
        $permissions = $mgrpass == "1234" ? 'manager' : 'employee';

        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$enc_password', '$permissions');";
        $conn->query($sql);

        header("Location: index.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register - Make IT All</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .password-container {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            padding-right: 45px !important;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #F8CE08;
            box-shadow: 0 0 10px rgba(74, 144, 226, 0.1);
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

        .password-toggle:focus {
            outline: none;
        }

        .eye-icon {
            width: 20px;
            height: 20px;
            fill: #666;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        button[type="submit"] {
            background: #F8CE08;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 15px;
        }

        button[type="submit"]:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.2);
        }

        .login-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            display: block;
            margin-top: 20px;
        }

        .login-link:hover {
            color: #F8CE08;
        }

        #mgrpass {
            margin-top: 15px;
        }

        .error {
            color: #ff4444;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <img src="TP_LOGO.png" alt="Make IT All Logo" class="logo">
        <h2>Create Account</h2>
        <?php if (isset($error))
            echo "<p class='error'>$error</p>"; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="name" placeholder="Full Name"
                    value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                <small class="error"><?php echo $errors['name'] ?? ''; ?></small>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Company Email"
                    value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <small class="error"><?php echo $errors['email'] ?? ''; ?></small>
            </div>

            <div class="input-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <svg class="eye-icon" viewBox="0 0 24 24">
                            <path
                                d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                        </svg>
                    </button>
                </div>
                <small class="error"><?php echo $errors['password'] ?? ''; ?></small>
            </div>

            <div class="input-group">
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                        required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                        <svg class="eye-icon" viewBox="0 0 24 24">
                            <path
                                d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                        </svg>
                    </button>
                </div>
                <small class="error"><?php echo $errors['confirm_password'] ?? ''; ?></small>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="mgrpasstoggle" onchange="toggleMgrPass()">
                <label for="mgrpasstoggle">I am a Manager</label>
            </div>

            <div class="input-group">
                <input type="password" id="mgrpass" name="mgrpass" placeholder="Manager Password"
                    style="display: none;">
                <small class="error"><?php echo $errors['mgrpass'] ?? ''; ?></small>
            </div>

            <button type="submit">Register</button>
        </form>


        <a href="index.php" class="login-link">Already have an account? Log in</a>
    </div>

    <script>
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

        // Add this to your existing toggleMgrPass function
        function toggleMgrPass() {
            var mgrDDW = document.getElementById("mgrpass");
            if (mgrDDW.style.display === 'none') {
                mgrDDW.style.display = 'block';
            } else {
                mgrDDW.style.display = 'none';
                mgrDDW.value = ''; // Clear the manager password when hidden
            }
        }
    </script>
</body>

</html>