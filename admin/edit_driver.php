<?php
// admin/edit_driver.php
require_once '../db/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_id = $_POST['original_id'] ?? '';
    $driver_id = $_POST['driver_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $username = $_POST['username'] ?? '';
    $address = $_POST['address'] ?? '';
    $vehicle = $_POST['vehicle'] ?? '';
    $license_no = $_POST['license_no'] ?? '';
    $rating = $_POST['rating'] ?? 0;
    $status = $_POST['status'] ?? 'pending';

    // Handle profile image upload
    $profile_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
        $file = $_FILES['profile_image'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
            exit;
        }
        
        $upload_dir = '../uploads/drivers/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = 'driver_' . time() . '_' . basename($file['name']);
        $profile_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $profile_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
        $profile_path = 'uploads/drivers/' . $filename;
    }

    // Update driver
    if ($profile_path) {
        $sql = "UPDATE drivers SET name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, rating=?, status=?, profile=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssdsss", $name, $email, $phone, $username, $address, $vehicle, $license_no, $rating, $status, $profile_path, $original_id);
    } else {
        $sql = "UPDATE drivers SET name=?, email=?, phone=?, username=?, address=?, vehicle=?, license_no=?, rating=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssdss", $name, $email, $phone, $username, $address, $vehicle, $license_no, $rating, $status, $original_id);
    }
    
    $ok = $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Driver updated successfully' : 'Failed to update driver']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
