<?php
session_start();
include 'db_connection.php';
$message = "";

// Generate random user_id function
function generateUserId($length = 10) {
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $branch = trim($_POST['branch']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $phone = trim($_POST['phone']);
    $college = trim($_POST['college']);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Check if email already registered in complete_info
    $check = $conn->prepare("SELECT id FROM complete_info WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "<p class='error'>You have already registered.</p>";
    } else {
        // Handle photo upload
        $photoPath = "";
        $photoFile = "";

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir);
            $photoFile = time() . "_" . basename($_FILES["photo"]["name"]);
            $photoPath = $targetDir . $photoFile;
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) {
                $message = "<p class='error'>Photo upload failed.</p>";
            }
        } else {
            $message = "<p class='error'>Invalid photo upload.</p>";
        }

        if (empty($message)) {
            // Generate unique user_id
            $user_id = generateUserId();
            $checkUID = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $checkUID->bind_param("s", $user_id);
            $checkUID->execute();
            $checkUID->store_result();
            while ($checkUID->num_rows > 0) {
                $user_id = generateUserId();
                $checkUID->bind_param("s", $user_id);
                $checkUID->execute();
                $checkUID->store_result();
            }
            $checkUID->close();

            // Insert into complete_info
            $stmt = $conn->prepare("INSERT INTO complete_info 
                (user_id, name, branch, gender, dob, phone, college_name, address, pincode, photo, email, password, is_registered, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->bind_param("ssssssssssss", $user_id, $name, $branch, $gender, $dob, $phone, $college, $address, $pincode, $photoPath, $email, $password);

            if ($stmt->execute()) {
                // Insert into users table
                $userStmt = $conn->prepare("INSERT INTO users 
                    (user_id, name, email, password, profile_image, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())");
                $userStmt->bind_param("sssss", $user_id, $name, $email, $password, $photoFile);

                if ($userStmt->execute()) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $conn->query("DELETE FROM complete_info WHERE email = '$email'");
                    $message = "<p class='error'>Error saving user account.</p>";
                }
            } else {
                $message = "<p class='error'>Registration failed. Try again.</p>";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Trainee Registration</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; }
        label { font-weight: bold; margin-top: 15px; display: block; }
        input, select, textarea {
            width: 100%; padding: 10px; margin-top: 6px;
            border: 1px solid #ccc; border-radius: 6px;
        }
        button {
            margin-top: 20px; padding: 12px; width: 100%;
            background-color: #007BFF; color: white;
            border: none; font-size: 16px; border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background-color: #0056b3; }
        .error {
            background: #f8d7da; color: #842029;
            padding: 10px; border-radius: 6px; margin-top: 15px;
        }
        #preview {
            margin-top: 10px; max-width: 100px; display: none;
        }
    </style>
</head>
<body>

<?php 
$pageTitle = "Trainee Registration";
include 'navbar.php'; 
?>

<div class="container">
    <h2>Trainee Registration</h2>
    <?php if (!empty($message)) echo $message; ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="name">Trainee Name</label>
        <input type="text" name="name" id="name" required>

        <label for="branch">Branch</label>
        <select name="branch" id="branch" required>
            <option value="">--Select Branch--</option>
            <option value="CSE">CSE</option>
            <option value="EE">EE</option>
            <option value="ECE">ECE</option>
            <option value="MECHANICAL">MECHANICAL</option>
            <option value="AUTOMOBILE">AUTOMOBILE</option>
        </select>

        <label for="gender">Gender</label>
        <select name="gender" id="gender" required>
            <option value="">--Select Gender--</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>

        <label for="dob">Date of Birth</label>
        <input type="date" name="dob" id="dob" required>

        <label for="phone">Phone Number</label>
        <input type="tel" name="phone" id="phone" pattern="[0-9]{10}" required>

        <label for="college">College Name</label>
        <input type="text" name="college" id="college" required>

        <label for="address">Address</label>
        <textarea name="address" id="address" required></textarea>

        <label for="pincode">Pincode</label>
        <input type="text" name="pincode" id="pincode" pattern="[0-9]{6}" required>

        <label for="photo">Upload Photo</label>
        <input type="file" name="photo" id="photo" accept="image/*" required>
        <img id="preview" src="#" alt="Preview" />

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Register</button>
    </form>
</div>

<script>
document.getElementById('photo').addEventListener('change', function () {
    const preview = document.getElementById('preview');
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>
