<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Get all cars with their stock availability and ratings
$sql = "SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        c.discount_percent,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock,
        (SELECT GROUP_CONCAT(cs.plate_number SEPARATOR ', ') FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS plates,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id) AS total_stock,
        (SELECT AVG(rating) FROM car_reviews cr WHERE cr.car_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM car_reviews cr WHERE cr.car_id = c.id) as review_count
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        ORDER BY c.discount_percent DESC, c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($cars);
