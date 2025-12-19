<?php
// delete_profile.php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); echo json_encode(["status"=>"error","message"=>"Use POST only"]); exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) $data = $_POST;

$email = isset($data['email']) ? trim($data['email']) : '';
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$password = isset($data['password']) ? $data['password'] : '';

if (($email === '' && $user_id === 0) || $password === '') {
    http_response_code(422); echo json_encode(["status"=>"error","message"=>"Provide (email or user_id) and password"]); exit;
}

// authenticate (same as update)
if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Invalid email"]); exit; }
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
} else {
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute(); $stmt->store_result();
if ($stmt->num_rows === 0) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Invalid credentials"]); $stmt->close(); $conn->close(); exit; }
$stmt->bind_result($found_id, $password_hash); $stmt->fetch(); $stmt->close();
if (!password_verify($password, $password_hash)) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Invalid credentials"]); $conn->close(); exit; }
$auth_user_id = (int)$found_id;

// delete profile
$del = $conn->prepare("DELETE FROM profiles WHERE user_id = ?");
$del->bind_param("i", $auth_user_id);
if ($del->execute()) {
    echo json_encode(["status"=>"success","message"=>"Profile deleted"]);
} else {
    http_response_code(500); echo json_encode(["status"=>"error","message"=>"Failed to delete profile"]);
}
$del->close(); $conn->close();
?>
