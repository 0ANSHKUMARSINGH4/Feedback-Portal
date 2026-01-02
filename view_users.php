<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

// Get all users with role 'user' that have a record in complete_info
$query = "
    SELECT 
        u.user_id, 
        c.name, c.branch, c.gender, c.dob, c.phone, 
        c.college_name, c.address, c.pincode, c.photo
    FROM users u
    INNER JOIN complete_info c ON u.email = c.email
    WHERE u.role = 'user'
";

$result = $conn->query($query);
$pageTitle = "Trainee List";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #EAF6FF;
            margin: 0;
        }

        .container {
            padding: 30px;
        }

        .download-button {
            display: inline-block;
            background-color: #57B9FF;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 15px;
        }

        .download-button:hover {
            background-color: #517891;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ccc;
        }

        th {
            background-color: #517891;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Trainee Details</h2>

    <a href="download_trainees.php" class="download-button">Download CSV</a>

    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Branch</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Phone</th>
                <th>College</th>
                <th>Address</th>
                <th>Pincode</th>
                <th>Photo Filename</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['branch']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['college_name']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= htmlspecialchars($row['pincode']) ?></td>
                <td><?= htmlspecialchars($row['photo']) ?></td>
                <td>Yes</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
