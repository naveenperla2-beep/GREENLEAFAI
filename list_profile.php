<?php
// list_profiles.php
header("Content-Type: application/json; charset=utf-8");
require_once 'db.php';

$sql = "SELECT id, user_id, full_name, city, gardener_experience, created_at FROM profiles ORDER BY created_at DESC";
$res = $conn->query($sql);
$profiles = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $profiles[] = [
            "id" => (int)$r['id'],
            "user_id" => (int)$r['user_id'],
            "full_name" => $r['full_name'],
            "city" => $r['city'],
            "gardener_experience" => $r['gardener_experience'],
            "created_at" => $r['created_at']
        ];
    }
}

echo json_encode(["status"=>"success","profiles"=>$profiles]);
$conn->close();
?>
