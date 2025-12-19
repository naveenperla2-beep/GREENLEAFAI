<?php
// db.php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';        // set your MySQL password
$DB_NAME = 'greenleaf';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');
?>
