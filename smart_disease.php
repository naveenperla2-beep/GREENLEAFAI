<?php
header("Content-Type: application/json");

// 1️⃣ Get city from request (Postman or browser)
$city = $_GET['city'] ?? 'Hyderabad';

// 2️⃣ PUT YOUR REAL API KEY HERE
$apiKey = "eb598c768375941e4132d7fec878c58c";

// 3️⃣ Build weather API URL
$url = "https://api.openweathermap.org/data/2.5/weather?q="
     . urlencode($city)
     . "&units=metric&appid="
     . $apiKey;

// 4️⃣ Call OpenWeather API safely
$response = @file_get_contents($url);

if ($response === FALSE) {
    echo json_encode([
        "status" => "error",
        "message" => "Weather API request failed",
        "reason" => "Invalid API key or network issue"
    ]);
    exit;
}

// 5️⃣ Decode JSON
$data = json_decode($response, true);

// 6️⃣ Validate response
if (!isset($data['main'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid weather data received",
        "api_response" => $data
    ]);
    exit;
}

// 7️⃣ Extract weather values
$temp = $data['main']['temp'];
$humidity = $data['main']['humidity'];
$rain = $data['rain']['1h'] ?? 0;

// 8️⃣ Disease prediction logic
$risk = "Low Risk";

if ($humidity > 80 && $rain > 0) {
    $risk = "High Fungal Disease Risk";
}
elseif ($temp > 30 && $humidity > 70) {
    $risk = "Moderate Bacterial Disease Risk";
}
elseif ($temp < 20 && $humidity > 85) {
    $risk = "Powdery Mildew Risk";
}

// 9️⃣ Final response
echo json_encode([
    "status" => "success",
    "city" => $city,
    "temperature" => $temp,
    "humidity" => $humidity,
    "rain" => $rain,
    "disease_risk" => $risk,
    "advice" => "Ensure proper drainage and airflow"
]);
