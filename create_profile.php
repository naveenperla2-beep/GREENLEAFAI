<?php
// create_profile.php
header("Content-Type: application/json; charset=utf-8");
require_once "db.php";

// Only POST allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Use POST only"]);
    exit;
}

// Read JSON or form data
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

// Extract fields
$email      = trim($data["email"] ?? "");
$password   = trim($data["password"] ?? "");
$full_name  = trim($data["full_name"] ?? "");
$city       = trim($data["city"] ?? "");
$experience = trim($data["gardener_experience"] ?? "");

// Validation
if ($email === "" || $password === "") {
    echo json_encode(["status" => "error", "message" => "Email and password required"]);
    exit;
}
if ($full_name === "" || $city === "" || $experience === "") {
    echo json_encode(["status" => "error", "message" => "All profile fields required"]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email"]);
    exit;
}

// Authenticate user
$stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    exit;
}

$stmt->bind_result($user_id, $hash);
$stmt->fetch();
$stmt->close();

if (!password_verify($password, $hash)) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    exit;
}

// Prevent duplicate profile
$check = $conn->prepare("SELECT id FROM profiles WHERE user_id = ? LIMIT 1");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Profile already exists"]);
    exit;
}
$check->close();

// Insert profile
$insert = $conn->prepare(
    "INSERT INTO profiles (user_id, full_name, city, gardener_experience)
     VALUES (?, ?, ?, ?)"
);
$insert->bind_param("isss", $user_id, $full_name, $city, $experience);

if ($insert->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Profile created successfully",
        "profile_id" => $insert->insert_id,
        "user_id" => $user_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create profile"
    ]);
}

?>
