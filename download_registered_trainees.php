<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=registered_trainees.csv');

$output = fopen("php://output", "w");

// Column headers
fputcsv($output, ['User ID', 'Full Name', 'Address', 'DOB', 'Contact Number', 'Created At']);

// Fetch and write data
$result = $conn->query("SELECT user_id, full_name, address, dob, contact_number, created_at FROM complete_info");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
exit();
