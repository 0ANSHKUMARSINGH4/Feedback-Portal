<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=signed_up_users.csv');

$output = fopen("php://output", "w");

// Headers
fputcsv($output, ['User ID', 'Role', 'Created At']);

// Fetch data
$result = $conn->query("SELECT user_id, role, created_at FROM users");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit();
