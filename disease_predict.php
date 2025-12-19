<?php
header("Content-Type: application/json");

// Read raw JSON from Postman
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Get values safely
$temp = $data['temp'] ?? null;
$humidity = $data['humidity'] ?? null;
$rain = $data['rain'] ?? null;

// Validate
if ($temp === null || $humidity === null || $rain === null) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing parameters",
        "required" => ["temp", "humidity", "rain"]
    ]);
    exit;
}

// Disease prediction logic
$risk = "Low Risk";

if ($humidity > 80 && $rain > 0) {
    $risk = "High Fungal Disease Risk";
} elseif ($temp > 30 && $humidity > 70) {
    $risk = "Moderate Bacterial Disease Risk";
} elseif ($temp < 20 && $humidity > 85) {
    $risk = "Powdery Mildew Risk";
}

// Success response
echo json_encode([
    "status" => "success",
    "temperature" => (float)$temp,
    "humidity" => (int)$humidity,
    "rain" => (float)$rain,
    "disease_risk" => $risk
]);
