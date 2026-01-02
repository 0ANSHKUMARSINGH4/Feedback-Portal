<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

include 'db_connection.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="signedup_users.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['User ID', 'Role', 'Account Created', 'Registered']);

$result = $conn->query("SELECT user_id, role, created_at FROM users");

while ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
    
    // Check registration status
    $check = $conn->prepare("SELECT id FROM complete_info WHERE user_id = ?");
    $check->bind_param("s", $user_id);
    $check->execute();
    $check->store_result();
    $registered = $check->num_rows > 0 ? "Yes" : "No";
    $check->close();

    fputcsv($output, [
        $user_id,
        $row['role'],
        $row['created_at'],
        $registered
    ]);
}

fclose($output);
$conn->close();
exit();
?>
