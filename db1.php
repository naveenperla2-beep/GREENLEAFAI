<?php
$conn = new mysqli("localhost", "root", "", "greenleafai");

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}
?>
