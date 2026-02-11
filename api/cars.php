<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$sql = "SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE c.is_available = 1
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
$cars = [];

if ($result) {
    $cars = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($cars);
