<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

// Filter by role
$role_filter = $_GET['role'] ?? 'all';
$role_condition = '';
if ($role_filter === 'user') {
    $role_condition = "WHERE role = 'user'";
} elseif ($role_filter === 'admin') {
    $role_condition = "WHERE role = 'admin'";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signed Up List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #90D5FF, #77B1D4);
            margin: 0;
        }

        table {
            width: 90%;
            margin: 30px auto;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #517891;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        h2 {
            text-align: center;
            margin-top: 30px;
            color: #333;
        }

        .controls {
            text-align: center;
            margin: 20px;
        }

        .controls form {
            display: inline-block;
            margin: 0 10px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #57B9FF;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #517891;
        }

        .filter-select {
            padding: 8px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

    </style>
</head>
<body>

<?php 
$pageTitle = "Total Signups";
include 'navbar.php'; 
?>

<h2>Signed Up List</h2>

<div class="controls">
    <form method="get">
        <label for="role">Filter by Role:</label>
        <select name="role" id="role" class="filter-select" onchange="this.form.submit()">
            <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Users</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
        </select>
    </form>

    <a href="download_signedup_users.php" class="btn">Download Details</a>
</div>

<table>
    <tr>
        <th>User ID</th>
        <th>Name</th>
        <th>Role</th>
        <th>Account Created</th>
        <?php if ($role_filter !== 'admin') echo '<th>Registered</th>'; ?>
    </tr>
    <?php
    $result = $conn->query("SELECT user_id, name, role, created_at FROM users $role_condition ORDER BY created_at DESC");

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";

        if ($row['role'] !== 'admin') {
            $check = $conn->prepare("SELECT id FROM complete_info WHERE user_id = ?");
            $check->bind_param("s", $row['user_id']);
            $check->execute();
            $check->store_result();
            $is_registered = $check->num_rows > 0 ? "Yes" : "No";
            $check->close();
            echo "<td>$is_registered</td>";
        }

        echo "</tr>";
    }

    $conn->close();
    ?>
</table>

</body>
</html>
