<?php
session_start();
include 'db_connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, role, created_at, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($db_user_id, $db_password, $role, $created_at, $name);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            $_SESSION['user_id'] = $db_user_id;
            $_SESSION['role'] = $role;
            $_SESSION['created_at'] = $created_at;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;

            header("Location: " . ($role === "admin" ? "admin_dashboard.php" : "user_dashboard.php"));
            exit();
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "No user found with this email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Feedback Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background-color: #517891;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar img {
            height: 40px;
            margin-right: 15px;
        }

        .navbar h1 {
            font-size: 22px;
            margin: 0;
            font-weight: bold;
        }

        /* Login Form */
        .login-container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 350px;
            margin: 80px auto;
        }

        .login-container h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #517891;
        }

        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .login-container input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #57B9FF;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-container input[type="submit"]:hover {
            background: #517891;
        }

        .signup-link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .signup-link a {
            color: #57B9FF;
            text-decoration: none;
            font-weight: bold;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                width: 90%;
                margin: 60px auto;
            }

            .navbar h1 {
                font-size: 18px;
            }

            .navbar img {
                height: 35px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar with logo and title -->
<div class="navbar">
    <img src="dbb52941-0433-4d12-be04-f76574e028cd.png" alt="Tata Prashikshan Logo">
    <h1>SNTI Feedback Portal</h1>
</div>

<!-- Login Form -->
<div class="login-container">
    <h2>Login</h2>
    <form method="post" action="">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <input type="submit" value="Login">
    </form>

    <div class="signup-link">
        Don't have an account? <a href="signup.html">Sign up here</a>
    </div>
</div>

<!-- Alert for login errors -->
<?php if (!empty($message)): ?>
<script>
    alert("<?= htmlspecialchars($message) ?>");
</script>
<?php endif; ?>

</body>
</html>
