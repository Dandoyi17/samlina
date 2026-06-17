<?php
// admin/update_driver.php
require_once '../db/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $vehicle = $_POST['vehicle'];
    $rating = $_POST['rating'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE drivers SET name=?, phone=?, vehicle=?, rating=?, status=?, notes=? WHERE id=?");
    $stmt->bind_param("sssdsss", $name, $phone, $vehicle, $rating, $status, $notes, $id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Driver updated' : $stmt->error]);
}