<?php
header("Content-Type: application/json");

$city = "Hyderabad";
$apiKey = "eb598c768375941e4132d7fec878c58c";

$url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid=" . $apiKey;

$response = @file_get_contents($url);

if ($response === FALSE) {
    echo json_encode([
        "error" => "Weather API request failed",
        "message" => "Check API key or internet connection"
    ]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['main'])) {
    echo json_encode([
        "error" => "Invalid weather data received"
    ]);
    exit;
}

echo json_encode([
    "temperature" => $data['main']['temp'],
    "humidity" => $data['main']['humidity'],
    "rain" => $data['rain']['1h'] ?? 0,
    "condition" => $data['weather'][0]['description']
]);
