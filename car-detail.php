<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$id = intval($_GET['id']);

$sql = "SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE c.id = $id";

$result = $conn->query($sql);
$car = $result->fetch_assoc();
?>

<h2><?= $car['brand_name'] ?> <?= $car['model'] ?></h2>
<img src="assets/images/cars/<?= $car['image'] ?>" width="400">
<p>Seats: <?= $car['seats'] ?></p>
<p>Transmission: <?= $car['transmission'] ?></p>
<p>Price: $<?= $car['price_per_day'] ?>/day</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
