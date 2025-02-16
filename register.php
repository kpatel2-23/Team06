<?php
session_start();

const _EMAIL = '/^[a-zA-Z0-9]+@make-it-all.co.uk$/';
const _PASS = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!"£$%^&*-])[a-zA-Z0-9_!"£$%^&*-]{8,32}$/';

include("db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);

    if ($password == '' || $email == '' || $name == '') {
        echo '<script type="text/javascript"> alert("Please fill out all fields."); window.location="register.php"; </script>';
        exit;
    }

    if (!preg_match(_EMAIL, $email)) {
        echo '<script type="text/javascript"> alert("Please ensure you are using a valid company email when registering."); window.location="register.php"; </script>';
        exit;
    }

    if (!preg_match(_PASS, $password)) {
        echo '<script type="text/javascript"> alert("Please ensure your password meets the following requirements:\nbetween 8 and 32 characters,\ncontains at least one lowercase character,\ncontains at least one uppercase character,\ncontains at least one number,\nand contains at least one symbol ( _!\"£$%^&* )"); window.location="register.php"; </script>';
        exit;
    }

    $sql = "SELECT `email` FROM `users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $sql);

    if ($result->num_rows == 1) {
        echo '<script type="text/javascript"> alert("There is already an account associated with this email. Try logging in instead."); window.location="register.php"; </script>';
    }

    $enc_password = md5($_POST['password']);
    $permissions = trim($_POST['mgrpass']) == "1234" ? 'manager' : 'employee';

    $sql = "INSERT INTO users (name, email, password, role)
            VALUES ('$name', '$email', '$enc_password', '$permissions');";
    $result = $conn->query($sql);
    //ERROR TRAPPING HERE
    echo '<script type="text/javascript">window.location = index.php;</script>'; //this fucking sucks
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

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #F8CE08;
            box-shadow: 0 0 10px rgba(74, 144, 226, 0.1);
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

        button {
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

        button:hover {
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
                <input type="text" name="name" placeholder="Full Name" required>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="Company Email" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="mgrpasstoggle" onchange="toggleMgrPass()">
                <label for="mgrpasstoggle">I am a Manager</label>
            </div>

            <div class="input-group">
                <input type="password" id="mgrpass" name="mgrpass" placeholder="Manager Password"
                    style="display: none;">
            </div>

            <button type="submit">Register</button>
        </form>

        <a href="index.php" class="login-link">Already have an account? Log in</a>
    </div>

    <script>
        function toggleMgrPass() {
            var mgrDDW = document.getElementById("mgrpass");
            if (mgrDDW.style.display === 'none') {
                mgrDDW.style.display = 'block';
            } else {
                mgrDDW.style.display = 'none';
            }
        }
    </script>
</body>

</html>