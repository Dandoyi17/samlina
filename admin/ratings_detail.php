<?php
// ratings_detail.php (admin)
require_once '../db/config.php';
$driver_id = intval($_GET['driver_id'] ?? 0);
$stmt = $conn->prepare("SELECT r.rating, r.review, r.booking_id, r.created_at, h.fullName AS client FROM ratings r LEFT JOIN hire h ON r.hire_id=h.id WHERE r.driver_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
// render table of $rows