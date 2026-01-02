<?php
session_start();
include 'db_connection.php';

function generateUserId($prefix = "USR_", $length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $user_id = '';
    for ($i = 0; $i < $length; $i++) {
        $user_id .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $prefix . $user_id;
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $created_at = date("Y-m-d H:i:s");

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Email already exists!";
        } else {
            $check_stmt->close();

            do {
                $user_id = generateUserId();
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $stmt->store_result();
            } while ($stmt->num_rows > 0);
            $stmt->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (user_id, password, name, email, created_at, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $user_id, $hashed_password, $name, $email, $created_at, $role);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;
                $_SESSION['created_at'] = $created_at;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;

                header("Location: " . ($role === "admin" ? "admin_dashboard.php" : "user_dashboard.php"));
                exit();
            } else {
                $message = "Signup failed. Please try again.";
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup - Feedback Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
            margin: 0;
            padding: 0;
        }

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

        .signup-container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 360px;
            margin: 80px auto;
        }

        .signup-container h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #517891;
        }

        .signup-container label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #517891;
        }

        .signup-container input[type="text"],
        .signup-container input[type="password"],
        .signup-container input[type="email"],
        .signup-container select {
            width: 100%;
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .signup-container input[type="submit"] {
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

        .signup-container input[type="submit"]:hover {
            background: #517891;
        }

        .signup-container .login-link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .signup-container .login-link a {
            color: #57B9FF;
            text-decoration: none;
            font-weight: bold;
        }

        .signup-container .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .signup-container {
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

<!-- Navbar -->
<div class="navbar">
    <img src="dbb52941-0433-4d12-be04-f76574e028cd.png" alt="Tata Logo">
    <h1>SNTI Feedback Portal</h1>
</div>

<!-- Signup Form -->
<div class="signup-container">
    <h2>Create Account</h2>
    <form method="POST" action="">
        <label for="name">Full Name</label>
        <input type="text" name="name" required>

        <label for="email">Email</label>
        <input type="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" required>

        <label for="role">Register As</label>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <input type="submit" value="Sign Up">
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<?php if (!empty($message)): ?>
<script>
    alert("<?= htmlspecialchars($message) ?>");
</script>
<?php endif; ?>

</body>
</html>
