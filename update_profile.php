<?php
// update_profile.php
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
$full_name = isset($data['full_name']) ? trim($data['full_name']) : null;
$city = isset($data['city']) ? trim($data['city']) : null;
$experience = isset($data['gardener_experience']) ? trim($data['gardener_experience']) : null;

if (($email === '' && $user_id === 0) || $password === '') {
    http_response_code(422); echo json_encode(["status"=>"error","message"=>"Provide (email or user_id) and password"]); exit;
}

// fetch user
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

// ensure profile exists
$chk = $conn->prepare("SELECT id FROM profiles WHERE user_id = ? LIMIT 1");
$chk->bind_param("i", $auth_user_id); $chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) { http_response_code(404); echo json_encode(["status"=>"error","message"=>"Profile not found"]); $chk->close(); $conn->close(); exit; }
$chk->bind_result($profile_id); $chk->fetch(); $chk->close();

// Build update dynamically
$fields = [];
$types = '';
$params = [];

if ($full_name !== null) { $fields[] = "full_name = ?"; $types .= 's'; $params[] = $full_name; }
if ($city !== null) { $fields[] = "city = ?"; $types .= 's'; $params[] = $city; }
if ($experience !== null) { $fields[] = "gardener_experience = ?"; $types .= 's'; $params[] = $experience; }

if (empty($fields)) {
    echo json_encode(["status"=>"error","message"=>"No fields to update"]); $conn->close(); exit;
}

$sql = "UPDATE profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
$types .= 'i';
$params[] = $auth_user_id;

$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB error (prepare)"]); $conn->close(); exit; }

// bind params dynamically
$bind_names[] = $types;
for ($i=0; $i<count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);

if ($stmt->execute()) {
    echo json_encode(["status"=>"success","message"=>"Profile updated"]);
} else {
    http_response_code(500); echo json_encode(["status"=>"error","message"=>"Failed to update profile"]);
}
$stmt->close(); $conn->close();
?>
