<?php
// admin/fetch_drivers.php
require_once '../db/config.php';

header('Content-Type: application/json');

$sql = "SELECT id, name, phone, vehicle, rating, status, notes FROM drivers";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

$drivers = [];
while ($row = $result->fetch_assoc()) {
    $drivers[] = $row;
}
echo json_encode($drivers);