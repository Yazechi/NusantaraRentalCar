<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$where = ["c.is_available = 1"];

/* BRAND */
if (!empty($_GET['brand'])) {
    $brand = intval($_GET['brand']);
    $where[] = "c.brand_id = $brand";
}

/* SEATS */
if (!empty($_GET['seats'])) {
    $seats = intval($_GET['seats']);
    $where[] = "c.seats = $seats";
}

/* TRANSMISSION */
if (!empty($_GET['transmission'])) {
    $trans = $conn->real_escape_string($_GET['transmission']);
    $where[] = "c.transmission = '$trans'";
}

/* PRICE RANGE (INI BAGIAN YANG KAMU BINGUNG) */
if (!empty($_GET['price_range'])) {

    $range = $_GET['price_range'];      // contoh: 50-100
    list($min, $max) = explode('-', $range);

    $min = intval($min);
    $max = intval($max);

    $where[] = "c.price_per_day BETWEEN $min AND $max";
}

/* QUERY */
$where_sql = implode(" AND ", $where);

$sql = "SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE $where_sql";

$result = $conn->query($sql);

$cars = [];
if ($result) {
    $cars = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($cars);
