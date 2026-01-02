<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

$query = "SELECT user_id, department, feedback_message AS feedback, q1, q2, q3, q4, q5, submitted_at, created_at FROM feedback ORDER BY created_at DESC";
$result = $conn->query($query);

// CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Department', 'Feedback', 'Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Submitted At', 'Created At']);
    $csvResult = $conn->query($query);
    while ($row = $csvResult->fetch_assoc()) {
        fputcsv($output, [
            $row['user_id'], $row['department'], $row['feedback'],
            $row['q1'], $row['q2'], $row['q3'], $row['q4'], $row['q5'],
            $row['submitted_at'], $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback Responses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #EAF6FF;
            margin: 0;
            padding: 0;
        }

        .content {
            padding: 20px 20px 30px; /* Offset for navbar */
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .btn-download {
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #57B9FF;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        .btn-download:hover {
            background-color: #517891;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
            font-size: 14px;
        }

        th {
            background-color: #517891;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        @media screen and (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<?php 
$pageTitle = "Feedback Responses";
include 'navbar.php'; 
?>

<div class="content">
    <h2>All Feedback Responses</h2>

    <form method="get" action="view_feedbacks.php">
        <button class="btn-download" type="submit" name="download" value="csv">Download as CSV</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Department</th>
                <th>Feedback</th>
                <th>Q1</th>
                <th>Q2</th>
                <th>Q3</th>
                <th>Q4</th>
                <th>Q5</th>
                <th>Submitted At</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['department']) ?></td>
                <td><?= htmlspecialchars($row['feedback']) ?></td>
                <td><?= htmlspecialchars($row['q1']) ?></td>
                <td><?= htmlspecialchars($row['q2']) ?></td>
                <td><?= htmlspecialchars($row['q3']) ?></td>
                <td><?= htmlspecialchars($row['q4']) ?></td>
                <td><?= htmlspecialchars($row['q5']) ?></td>
                <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
