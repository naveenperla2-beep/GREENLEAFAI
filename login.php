<?php
// login.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Use POST only"]);
    exit;
}

// Read JSON body first, fallback to $_POST (form-data / x-www-form-urlencoded)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) $data = $_POST;

// Get inputs (trim email)
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Basic validation
if ($email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Email and password required"]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid email"]);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error (prepare)"]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Do not reveal whether email exists
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->bind_result($user_id, $password_hash);
$stmt->fetch();
$stmt->close();

// Verify password
if (!password_verify($password, $password_hash)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    $conn->close();
    exit;
}

// Success
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "user_id" => (int)$user_id,
    "email" => $email
]);

$conn->close();
exit;
?>
