<?php
// admin/add_fleet.php
session_start();
require_once __DIR__ . '/../db/config.php';

$uploadDir = __DIR__ . '/../images/fleet/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function saveUpload($fieldName, $uploadDir) {
    if (!isset($_FILES[$fieldName]) || !$_FILES[$fieldName]['name']) return null;
    $f = $_FILES[$fieldName];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    // Basic validation: image mime types
    $info = getimagesize($f['tmp_name']);
    if (!$info) return null;
    $ext = image_type_to_extension($info[2]) ?: pathinfo($f['name'], PATHINFO_EXTENSION);
    $base = time() . '_' . bin2hex(random_bytes(6));
    $filename = $base . ($ext[0] === '.' ? $ext : '.' . $ext);
    $target = $uploadDir . $filename;
    if (move_uploaded_file($f['tmp_name'], $target)) {
        return 'images/fleet/' . $filename; // web path relative to project root
    }
    return null;
}

$car_id_original = trim($_POST['car_id_original'] ?? '');
$car_id = trim($_POST['car_id'] ?? '');
$model = trim($_POST['model'] ?? '');
$make = trim($_POST['make'] ?? '');
$seats = intval($_POST['seats'] ?? 0) ?: null;
$type = trim($_POST['type'] ?? '');
$plate = trim($_POST['plate'] ?? '');
$rate = trim($_POST['rate'] ?? '');
$details = trim($_POST['details'] ?? '');

if ($car_id === '' || $model === '') {
    $_SESSION['error'] = 'Car ID and Model are required.';
    header('Location: fleet.php');
    exit;
}

// If editing, load existing record to keep images if not replaced
$existing = null;
if ($car_id_original !== '') {
    $q = $conn->prepare("SELECT * FROM fleet WHERE car_id = ? LIMIT 1");
    $q->bind_param('s', $car_id_original);
    $q->execute();
    $res = $q->get_result();
    $existing = $res ? $res->fetch_assoc() : null;
    $q->close();
}

// process uploads (if provided)
$image1 = saveUpload('image1', $uploadDir) ?? ($existing['image1'] ?? null);
$image2 = saveUpload('image2', $uploadDir) ?? ($existing['image2'] ?? null);
$image3 = saveUpload('image3', $uploadDir) ?? ($existing['image3'] ?? null);

if ($car_id_original !== '' && $existing) {
    // update
    $stmt = $conn->prepare("UPDATE fleet SET car_id=?, model=?, make=?, seats=?, type=?, plate=?, rate=?, details=?, image1=?, image2=?, image3=? WHERE id = ?");
    $stmt->bind_param('sssisisssssi', $car_id, $model, $make, $seats, $type, $plate, $rate, $details, $image1, $image2, $image3, $existing['id']);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Vehicle updated.';
    } else {
        $_SESSION['error'] = 'Update failed: ' . $stmt->error;
    }
    $stmt->close();
} else {
    // insert - ensure car_id unique
    $stmt = $conn->prepare("INSERT INTO fleet (car_id, model, make, seats, type, plate, rate, details, image1, image2, image3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssisisssss', $car_id, $model, $make, $seats, $type, $plate, $rate, $details, $image1, $image2, $image3);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Vehicle added.';
    } else {
        $_SESSION['error'] = 'Insert failed: ' . $stmt->error;
    }
    $stmt->close();
}

header('Location: fleet.php');
exit;