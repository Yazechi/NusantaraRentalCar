<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Build query with prepared statements
$where = ["c.is_available = 1"];
$params = [];
$types = "";

// Brand filter
if (!empty($_GET['brand'])) {
    $brand = filter_var($_GET['brand'], FILTER_VALIDATE_INT);
    if ($brand) {
        $where[] = "c.brand_id = ?";
        $params[] = $brand;
        $types .= "i";
    }
}

// Seats filter
if (!empty($_GET['seats'])) {
    $seats = filter_var($_GET['seats'], FILTER_VALIDATE_INT);
    if ($seats) {
        $where[] = "c.seats = ?";
        $params[] = $seats;
        $types .= "i";
    }
}

// Transmission filter
if (!empty($_GET['transmission'])) {
    $transmission = trim($_GET['transmission']);
    if (in_array(strtolower($transmission), ['automatic', 'manual'])) {
        $where[] = "c.transmission = ?";
        $params[] = strtolower($transmission);
        $types .= "s";
    }
}

// Price range filter
if (!empty($_GET['price_range'])) {
    $range = $_GET['price_range'];
    if (preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
        $min = (int)$matches[1];
        $max = (int)$matches[2];
        $where[] = "c.price_per_day BETWEEN ? AND ?";
        $params[] = $min;
        $params[] = $max;
        $types .= "ii";
    }
}

$where_sql = implode(" AND ", $where);

$sql = "SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE $where_sql
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($cars);
