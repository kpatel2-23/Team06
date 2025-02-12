<?php
session_start();

const _EMAIL = '/^[a-zA-Z0-9]+@make-it-all.co.uk$/';
const _PASS = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[_!"£$%^&*-])[a-zA-Z0-9_!"£$%^&*-]{8,32}$/';

include("db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);

    if($password == '' || $email == '' || $name == '') 
    {
        echo '<script type="text/javascript"> alert("Please fill out all fields."); window.location="register.php"; </script>'; 
        exit;
    }
    
    if(!preg_match(_EMAIL, $email))
    {
        echo '<script type="text/javascript"> alert("Please ensure you are using a valid company email when registering."); window.location="register.php"; </script>'; 
        exit;
    }

    if(!preg_match(_PASS, $password))
    {
        echo '<script type="text/javascript"> alert("Please ensure your password meets the following requirements:\nbetween 8 and 32 characters,\ncontains at least one lowercase character,\ncontains at least one uppercase character,\ncontains at least one number,\nand contains at least one symbol ( _!\"£$%^&* )"); window.location="register.php"; </script>'; 
        exit;
    }

    $sql = "SELECT `email` FROM `users` WHERE `email`='$email'";
    $result = mysqli_query($conn, $sql);

    if($result->num_rows==1){
        echo '<script type="text/javascript"> alert("There is already an account associated with this email. Try logging in instead."); window.location="register.php"; </script>'; 
    }

    $enc_password = md5($_POST['name']);
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
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Register</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="name" name="name" placeholder="Forename" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input id="mgrpasstoggle" onchange="toggleMgrPass()" type="checkbox">
            <label for="mgrpasstoggle">Enter Manager Password</label><br>
            <input type="password" id="mgrpass" name="mgrpass" placeholder="Manager Password" style="display: none;"><br>
            <button type="submit">Register</button>
        </form>
        <a href="index.php">
            <button>Login</button>
        </a>
    </div>
</body>
</html>
<script>
    function toggleMgrPass()
    {
        var mgrDDW = document.getElementById("mgrpass");

        if(mgrDDW.style.display === 'none') {
            mgrDDW.style.display = 'block';
        } else {
            mgrDDW.style.display = 'none';
        }
    }
</script>