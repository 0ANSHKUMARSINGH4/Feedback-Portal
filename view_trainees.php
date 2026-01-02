<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include 'db_connection.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registered Trainees</title>
    <style>
        body { font-family: Arial; background: #EAF6FF; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        h2 { color: #333; }
        .btn-download {
            margin: 10px 0;
            padding: 10px 15px;
            background: #57B9FF;
            border: none;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<h2>Registered Trainees</h2>
<form method="post" action="download_trainees.php">
    <button class="btn-download" type="submit">Download Details</button>
</form>
<table>
    <tr>
        <th>User ID</th>
        <th>Full Name</th>
        <th>Address</th>
        <th>DOB</th>
        <th>Contact</th>
        <th>Created At</th>
    </tr>
    <?php
    $result = $conn->query("SELECT user_id, full_name, address, dob, contact_number, created_at FROM complete_info");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['user_id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['address']}</td>
                <td>{$row['dob']}</td>
                <td>{$row['contact_number']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }
    ?>
</table>
</body>
</html>
