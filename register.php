<?php
session_start();

const _EMAIL = '/^[a-zA-Z0-9]+@make-it-all.co.uk$/';
const _PASS = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!"£$%^&*-])[a-zA-Z0-9_!"£$%^&*-]{8,32}$/';

include("db_config.php");

$errors = ['name' => '', 'email' => '', 'password' => '', 'mgrpass' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
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
                <input type="password" name="password" placeholder="Password" required>
                <small class="error"><?php echo $errors['password'] ?? ''; ?></small>
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