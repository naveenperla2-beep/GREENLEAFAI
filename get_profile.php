<?php
// get_profile.php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); echo json_encode(["status"=>"error","message"=>"Use POST only"]); exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) $data = $_POST;

$password = isset($data['password']) ? $data['password'] : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($password === '') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Password required"]); exit; }

if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Invalid email"]); exit; }
    $stmt = $conn->prepare("SELECT u.id, u.password_hash, p.id AS profile_id, p.full_name, p.city, p.gardener_experience, p.created_at
                            FROM users u LEFT JOIN profiles p ON p.user_id = u.id WHERE u.email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
} elseif ($user_id) {
    $stmt = $conn->prepare("SELECT u.id, u.password_hash, p.id AS profile_id, p.full_name, p.city, p.gardener_experience, p.created_at
                            FROM users u LEFT JOIN profiles p ON p.user_id = u.id WHERE u.id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
} else {
    http_response_code(422); echo json_encode(["status"=>"error","message"=>"Provide email or user_id"]); exit;
}

$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) { http_response_code(404); echo json_encode(["status"=>"error","message"=>"User not found"]); $stmt->close(); $conn->close(); exit; }
$stmt->bind_result($uid, $password_hash, $profile_id, $full_name, $city, $experience, $created_at);
$stmt->fetch();
$stmt->close();

if (!password_verify($password, $password_hash)) {
    http_response_code(401); echo json_encode(["status"=>"error","message"=>"Invalid password"]); $conn->close(); exit;
}

$response = [
    "status" => "success",
    "user" => ["id" => (int)$uid, "email" => $email ?: null],
    "profile" => $profile_id ? [
        "profile_id" => (int)$profile_id,
        "full_name" => $full_name,
        "city" => $city,
        "gardener_experience" => $experience,
        "created_at" => $created_at
    ] : null
];

echo json_encode($response);
$conn->close();
?>
