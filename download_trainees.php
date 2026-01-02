<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=registered_trainees.csv');

$output = fopen("php://output", "w");

// CSV Header Row
fputcsv($output, [
    'User ID',
    'Full Name',
    'Gender',
    'DOB',
    'Phone Number',
    'Email',
    'Branch',
    'College Name',
    'Address',
    'Pincode',
    'Photo Filename',
    'Created At'
]);

// Join users and complete_info, only for users with role='user'
$query = "
    SELECT u.user_id, u.name AS full_name, ci.gender, ci.dob, ci.phone, ci.email, 
           ci.branch, ci.college_name, ci.address, ci.pincode, ci.photo, ci.created_at
    FROM users u
    JOIN complete_info ci ON u.user_id = ci.user_id
    WHERE u.role = 'user'
";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['user_id'],
        $row['full_name'],
        $row['gender'],
        $row['dob'],
        $row['phone'],
        $row['email'],
        $row['branch'],
        $row['college_name'],
        $row['address'],
        $row['pincode'],
        $row['photo'],
        $row['created_at']
    ]);
}

fclose($output);
$conn->close();
exit();
