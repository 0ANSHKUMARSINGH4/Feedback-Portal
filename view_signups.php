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
    <title>View Signups</title>
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
<h2>Signed-Up Users</h2>
<form method="post" action="download_signups.php">
    <button class="btn-download" type="submit">Download Details</button>
</form>
<table>
    <tr>
        <th>User ID</th>
        <th>Role</th>
        <th>Created At</th>
    </tr>
    <?php
    $result = $conn->query("SELECT user_id, role, created_at FROM users");
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['user_id']}</td><td>{$row['role']}</td><td>{$row['created_at']}</td></tr>";
    }
    ?>
</table>
</body>
</html>
