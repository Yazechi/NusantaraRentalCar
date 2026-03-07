<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Build query with prepared statements
$where = [];
$params = [];
$types = "";
$joins = "";

// Brand filter
if (!empty($_GET['brand'])) {
    $brand = filter_var($_GET['brand'], FILTER_VALIDATE_INT);
    if ($brand) {
        $where[] = "c.brand_id = ?";
        $params[] = $brand;
        $types .= "i";
    }
}

// Type filter
if (!empty($_GET['type'])) {
    $type = filter_var($_GET['type'], FILTER_VALIDATE_INT);
    if ($type) {
        $where[] = "c.type_id = ?";
        $params[] = $type;
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

// Rental goal filter
if (!empty($_GET['goal'])) {
    $goal = filter_var($_GET['goal'], FILTER_VALIDATE_INT);
    if ($goal) {
        $joins .= " JOIN car_rental_goals crg ON c.id = crg.car_id";
        $where[] = "crg.rental_goal_id = ?";
        $params[] = $goal;
        $types .= "i";
    }
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

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
        $joins
        $where_sql
        ORDER BY c.discount_percent DESC, c.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($cars);
