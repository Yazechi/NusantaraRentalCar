<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Show each available stock unit individually with its plate number
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        cs.id AS stock_id, cs.plate_number,
        c.discount_percent
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        JOIN car_stock cs ON cs.car_id = c.id AND cs.status = 'available'
        ORDER BY c.created_at DESC, cs.plate_number ASC");
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($cars);
