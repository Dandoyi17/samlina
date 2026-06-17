<?php
// admin/add_driver.php
session_start();

// Configuration
$uploadBase = __DIR__ . '/../uploads/drivers'; // create uploads/drivers with write permission
$maxProfileSize = 3 * 1024 * 1024; // 3MB
$maxDocSize = 5 * 1024 * 1024; // 5MB
$allowedImage = ['image/jpeg', 'image/png', 'image/webp'];
$allowedDoc = array_merge($allowedImage, ['application/pdf']);

// Ensure upload dir exists
if (!is_dir($uploadBase)) {
    mkdir($uploadBase, 0755, true);
}

// Simple helper to sanitize filenames
function safe_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
    return substr($name, 0, 200);
}

$formErrors = [];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get fields
    $name = trim($_POST['name'] ?? '');
    $driver_id = trim($_POST['driver_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $vehicle = trim($_POST['vehicle'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    if ($name === '') $formErrors[] = 'Full name is required.';
    if ($driver_id === '') $formErrors[] = 'Driver ID is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $formErrors[] = 'Valid email is required.';
    if ($username === '') $formErrors[] = 'Username is required.';
    if (strlen($password) < 6) $formErrors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $formErrors[] = 'Passwords do not match.';

    // Validate uploads (profile image)
    $profilePath = null;
    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $pf = $_FILES['profile_image'];
        if ($pf['error'] !== UPLOAD_ERR_OK) {
            $formErrors[] = 'Error uploading profile image.';
        } elseif ($pf['size'] > $maxProfileSize) {
            $formErrors[] = 'Profile image exceeds maximum size of 3MB.';
        } elseif (!in_array(mime_content_type($pf['tmp_name']), $allowedImage, true)) {
            $formErrors[] = 'Profile image must be jpg, png or webp.';
        }
    }

    // License doc
    $licensePath = null;
    if (!empty($_FILES['license_doc']) && $_FILES['license_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
        $ld = $_FILES['license_doc'];
        if ($ld['error'] !== UPLOAD_ERR_OK) {
            $formErrors[] = 'Error uploading license document.';
        } elseif ($ld['size'] > $maxDocSize) {
            $formErrors[] = 'License document exceeds maximum size of 5MB.';
        } elseif (!in_array(mime_content_type($ld['tmp_name']), $allowedDoc, true)) {
            $formErrors[] = 'License document must be PDF or an image (jpg/png).';
        }
    }

    // ID doc
    $idPath = null;
    if (!empty($_FILES['id_doc']) && $_FILES['id_doc']['error'] !== UPLOAD_ERR_NO_FILE) {
        $id = $_FILES['id_doc'];
        if ($id['error'] !== UPLOAD_ERR_OK) {
            $formErrors[] = 'Error uploading ID document.';
        } elseif ($id['size'] > $maxDocSize) {
            $formErrors[] = 'ID document exceeds maximum size of 5MB.';
        } elseif (!in_array(mime_content_type($id['tmp_name']), $allowedDoc, true)) {
            $formErrors[] = 'ID document must be PDF or an image (jpg/png).';
        }
    }

    // If validation passes, insert
    if (empty($formErrors)) {
        $conn = new mysqli('localhost', 'root', '', 'samlina');
        if ($conn->connect_error) {
            $formErrors[] = 'Database connection failed.';
        } else {
            // check duplicates: driver_id, email, username
            $check = $conn->prepare("SELECT id FROM drivers WHERE driver_id = ? OR email = ? OR username = ? LIMIT 1");
            $check->bind_param('sss', $driver_id, $email, $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $formErrors[] = 'Driver ID, email or username already exists.';
                $check->close();
                $conn->close();
            } else {
                $check->close();

                // handle uploads: move files with unique names
                $timestamp = time();

                if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $pf = $_FILES['profile_image'];
                    $ext = pathinfo($pf['name'], PATHINFO_EXTENSION);
                    $fn = safe_filename($driver_id . '_profile_' . $timestamp . '.' . $ext);
                    $dest = $uploadBase . '/' . $fn;
                    if (move_uploaded_file($pf['tmp_name'], $dest)) {
                        $profilePath = 'uploads/drivers/' . $fn;
                    }
                }

                if (!empty($_FILES['license_doc']) && $_FILES['license_doc']['error'] === UPLOAD_ERR_OK) {
                    $ld = $_FILES['license_doc'];
                    $ext = pathinfo($ld['name'], PATHINFO_EXTENSION);
                    $fn = safe_filename($driver_id . '_license_' . $timestamp . '.' . $ext);
                    $dest = $uploadBase . '/' . $fn;
                    if (move_uploaded_file($ld['tmp_name'], $dest)) {
                        $licensePath = 'uploads/drivers/' . $fn;
                    }
                }

                if (!empty($_FILES['id_doc']) && $_FILES['id_doc']['error'] === UPLOAD_ERR_OK) {
                    $id = $_FILES['id_doc'];
                    $ext = pathinfo($id['name'], PATHINFO_EXTENSION);
                    $fn = safe_filename($driver_id . '_id_' . $timestamp . '.' . $ext);
                    $dest = $uploadBase . '/' . $fn;
                    if (move_uploaded_file($id['tmp_name'], $dest)) {
                        $idPath = 'uploads/drivers/' . $fn;
                    }
                }

                // Hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert record
                $stmt = $conn->prepare("INSERT INTO drivers (driver_id, name, email, phone, username, password, address, vehicle, license_no, profile_image, license_doc, id_doc, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssssssssss', $driver_id, $name, $email, $phone, $username, $hash, $address, $vehicle, $license_no, $profilePath, $licensePath, $idPath, $notes);

                if ($stmt->execute()) {
                    $_SESSION['formMessage'] = 'Driver added successfully.';
                    $stmt->close();
                    $conn->close();
                    // Redirect back to add form (PRG)
                    header('Location: add.php');
                    exit;
                } else {
                    $formErrors[] = 'Failed to add driver (database error).';
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    }

    // If here, there were errors — set flash and redirect back so add.php can show them
    $_SESSION['formErrors'] = $formErrors;
    header('Location: add.php');
    exit;
}

// If not POST, just redirect to add.php
header('Location: add.php');
exit;