<?php
// drivers_profile.php
session_start();
require_once '../../db/config.php';

// Ensure driver is logged in
$driver_id = $_SESSION['driver_id'] ?? null;
if (!$driver_id) {
    header('Location: drivers_login.php');
    exit;
}

// Helper: send JSON response and exit
function json_response($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data);
    exit;
}


// Flash message for avatar upload (shown after redirect from form submit)
$avatarFlash = $_SESSION['avatar_message'] ?? '';
unset($_SESSION['avatar_message']);

// Optional: if we stored avatar_path in session, update $avatarSrc so new avatar appears immediately
if (!empty($_SESSION['avatar_path'])) {
    $avatarSrc = $_SESSION['avatar_path'];
    unset($_SESSION['avatar_path']);
}

// Handle AJAX / API POSTs (JSON or multipart)
// Accept either JSON body with { action: '...', ... } or form POST with fields.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detect JSON payload
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    $action = null;
    if ($json && isset($json['action'])) {
        $action = $json['action'];
        $payload = $json;
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        $payload = $_POST;
    } else {
        // If no explicit action but file upload exists, treat as avatar upload
        if (!empty($_FILES['avatar'])) {
            $action = 'upload_avatar';
            $payload = $_POST;
        }
    }

    if ($action === 'change_password') {
        $current = trim($payload['currentPassword'] ?? '');
        $new = trim($payload['newPassword'] ?? '');
        $confirm = trim($payload['confirmPassword'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            json_response(['success' => false, 'message' => 'All fields are required'], 400);
        }
        if ($new !== $confirm) {
            json_response(['success' => false, 'message' => 'Passwords do not match'], 400);
        }
        if (strlen($new) < 8) {
            json_response(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
        }

        // Fetch current password hash
        $stmt = $conn->prepare("SELECT password FROM drivers WHERE id = ? LIMIT 1");
        if (!$stmt) json_response(['success' => false, 'message' => 'Server error'], 500);
        $stmt->bind_param('i', $driver_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) json_response(['success' => false, 'message' => 'Account not found'], 404);

        $hash = $row['password'] ?? '';
        if (!password_verify($current, $hash)) {
            json_response(['success' => false, 'message' => 'Current password is incorrect'], 400);
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE drivers SET password = ? WHERE id = ?");
        if (!$update) json_response(['success' => false, 'message' => 'Server error (update)'], 500);
        $update->bind_param('si', $newHash, $driver_id);
        $ok = $update->execute();
        $update->close();

        if ($ok) {
            json_response(['success' => true, 'message' => 'Password updated successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to update password'], 500);
        }
    } elseif ($action === 'update_profile') {
        // Accept name, email, phone, address, city, state
        $name = trim($payload['name'] ?? $payload['editName'] ?? '');
        $email = trim($payload['email'] ?? $payload['editEmail'] ?? '');
        $phone = trim($payload['phone'] ?? $payload['editPhone'] ?? '');
        $address = trim($payload['address'] ?? $payload['editAddress'] ?? '');
        $city = trim($payload['city'] ?? $payload['editCity'] ?? '');
        $state = trim($payload['state'] ?? $payload['editState'] ?? '');

        if ($name === '' || $email === '' || $phone === '') {
            json_response(['success' => false, 'message' => 'Name, email and phone are required'], 400);
        }

        // Basic email uniqueness check (don't block if it's the same as current)
        $check = $conn->prepare("SELECT id FROM drivers WHERE email = ? AND id != ? LIMIT 1");
        if ($check) {
            $check->bind_param('si', $email, $driver_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                json_response(['success' => false, 'message' => 'Email is already in use'], 400);
            }
            $check->close();
        }

        $upd = $conn->prepare("UPDATE drivers SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ? WHERE id = ?");
        if (!$upd) json_response(['success' => false, 'message' => 'Server error (prepare update)'], 500);
        $upd->bind_param('ssssssi', $name, $email, $phone, $address, $city, $state, $driver_id);
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            json_response(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to update profile'], 500);
        }

    } elseif ($action === 'upload_avatar') {
      // Handle avatar upload for both AJAX (JSON) and regular form submits.
      $isAjax = false;
      if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $isAjax = true;
      } elseif (!empty($raw) && json_decode($raw, true)) {
        $isAjax = true;
      } elseif (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        $isAjax = true;
      }

      $savedPath = '';

      // base64 payload (API clients)
      if (!empty($payload['avatar_data'])) {
        $dataUri = $payload['avatar_data'];
        if (preg_match('#^data:image/(\w+);base64,#i', $dataUri, $m)) {
          $ext = strtolower($m[1]);
          $base64 = substr($dataUri, strpos($dataUri, ',') + 1);
          $imgData = base64_decode($base64);
          if ($imgData === false) {
            if ($isAjax) json_response(['success' => false, 'message' => 'Invalid image data'], 400);
            $_SESSION['avatar_message'] = 'Invalid image data'; header('Location: drivers_profile.php'); exit;
          }

          $dir = __DIR__ . '/../../images/drivers';
          if (!is_dir($dir)) @mkdir($dir, 0755, true);

          $filename = 'driver_' . $driver_id . '_avatar.' . $ext;
          $absPath = $dir . '/' . $filename;
          if (file_put_contents($absPath, $imgData) === false) {
            if ($isAjax) json_response(['success' => false, 'message' => 'Failed to save image'], 500);
            $_SESSION['avatar_message'] = 'Failed to save image'; header('Location: drivers_profile.php'); exit;
          }
          $savedPath = '../../images/drivers/' . $filename;
        } else {
          if ($isAjax) json_response(['success' => false, 'message' => 'Unsupported image format'], 400);
          $_SESSION['avatar_message'] = 'Unsupported image format'; header('Location: drivers_profile.php'); exit;
        }

      // multipart/form-data file upload
      } elseif (!empty($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $f = $_FILES['avatar'];
        if ($f['size'] > 2 * 1024 * 1024) {
          if ($isAjax) json_response(['success' => false, 'message' => 'File too large'], 400);
          $_SESSION['avatar_message'] = 'File too large (max 2MB)'; header('Location: drivers_profile.php'); exit;
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$f['type']])) {
          if ($isAjax) json_response(['success' => false, 'message' => 'Unsupported file type'], 400);
          $_SESSION['avatar_message'] = 'Unsupported file type (JPG or PNG only)'; header('Location: drivers_profile.php'); exit;
        }
        $ext = $allowed[$f['type']];

        $dir = __DIR__ . '/../../images/drivers';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $filename = 'driver_' . $driver_id . '_avatar.' . $ext;
        $absPath = $dir . '/' . $filename;
        if (!move_uploaded_file($f['tmp_name'], $absPath)) {
          if ($isAjax) json_response(['success' => false, 'message' => 'Failed to move uploaded file'], 500);
          $_SESSION['avatar_message'] = 'Failed to save uploaded file'; header('Location: drivers_profile.php'); exit;
        }

        $savedPath = '../../images/drivers/' . $filename;

      } else {
        if ($isAjax) json_response(['success' => false, 'message' => 'No image provided'], 400);
        $_SESSION['avatar_message'] = 'No image provided'; header('Location: drivers_profile.php'); exit;
      }

      // Update DB with saved path
      $up = $conn->prepare("UPDATE drivers SET profile_image = ? WHERE id = ?");
      if (!$up) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Server error (DB prepare)'], 500);
        $_SESSION['avatar_message'] = 'Server error (DB)'; header('Location: drivers_profile.php'); exit;
      }
      $up->bind_param('si', $savedPath, $driver_id);
      $ok = $up->execute();
      $up->close();

      if ($ok) {
        if ($isAjax) {
          json_response(['success' => true, 'message' => 'Avatar uploaded', 'path' => $savedPath]);
        } else {
          // Normal form submit: set flash and redirect back to profile page
          $_SESSION['avatar_message'] = 'Avatar uploaded successfully';
          $_SESSION['avatar_path'] = $savedPath; // optional immediate UI update
          header('Location: drivers_profile.php');
          exit;
        }
      } else {
        if ($isAjax) json_response(['success' => false, 'message' => 'Failed to save avatar'], 500);
        $_SESSION['avatar_message'] = 'Failed to save avatar'; header('Location: drivers_profile.php'); exit;
      }
    } else {
      // Unknown action — accept legacy form submissions: if user posted fields directly (but your HTML uses JS)
      json_response(['success' => false, 'message' => 'Unknown action'], 400);
    }
}

// -- Page render (GET) --

// Fetch driver info
$driverInfo = null;
$dstmt = $conn->prepare("SELECT id, driver_id, name, username, email, phone, address, city, state, profile_image, status, created_at, rating, assigned_tasks FROM drivers WHERE id = ? LIMIT 1");
if ($dstmt) {
    $dstmt->bind_param('i', $driver_id);
    $dstmt->execute();
    $dres = $dstmt->get_result();
    if ($dres) $driverInfo = $dres->fetch_assoc();
    $dstmt->close();
}

// If no driver found, log out
if (!$driverInfo) {
    session_destroy();
    header('Location: drivers_login.php');
    exit;
}

// Stats: total tasks, completed, pending, average rating from drivers.rating (fallback)
$totalTasks = 0;
$completedTasks = 0;
$pendingTasks = 0;

$tstmt = $conn->prepare("SELECT COUNT(*) AS total,
    SUM(CASE WHEN task_assign_status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN task_assign_status IN ('assigned','processing') THEN 1 ELSE 0 END) AS pending
    FROM hire WHERE driver_id = ?");
if ($tstmt) {
    $tstmt->bind_param('i', $driver_id);
    $tstmt->execute();
    $tres = $tstmt->get_result();
    if ($tres && ($crow = $tres->fetch_assoc())) {
        $totalTasks = intval($crow['total'] ?? 0);
        $completedTasks = intval($crow['completed'] ?? 0);
        $pendingTasks = intval($crow['pending'] ?? 0);
    }
    $tstmt->close();
}

$averageRating = $driverInfo['rating'] !== null ? number_format((float)$driverInfo['rating'], 1) : '0.0';

// Choose avatar src
$avatarSrc = $driverInfo['profile_image'] ?: '../../logo/logowhite.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Driver Profile — Samlina Global</title>
    <link rel="stylesheet" href="../css/drivers_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="driver-profile-page">
    <header class="topbar">
        <div class="topbar-left">
            <img class="brand-logo" src="../../logo/logowhite.png" alt="Samlina Global logo">
            <div class="brand-text">
                <h1>Samlina Global</h1>
                <p class="tag">Driver Profile</p>
            </div>
        </div>

        <button id="hamburger" class="hamburger" aria-label="Toggle menu">
      <i class="fas fa-bars"></i>
    </button>

        <nav id="topNav" class="topnav" aria-hidden="true">
            <a href="../../index.php">Home</a>
            <a href="drivers_dashboard.php">Dashboard</a>
            <a href="drivers_profile.php" class="active">Profile</a>
            <a href="logout.php" id="logoutLink">Logout</a>
        </nav>
    </header>

    <main class="profile-main">
        <section class="profile-section">
            <div class="profile-header">
                <h2>My Profile</h2>
                <p class="muted">View and manage your driver information</p>
            </div>

            <div class="profile-card">
                <div class="profile-avatar">
                    <img id="avatarImg" src="<?= htmlspecialchars($avatarSrc) ?>" alt="Driver avatar" class="avatar">
                    <div class="avatar-overlay">
                        <button id="changeAvatarBtn" class="btn-icon" title="Change avatar">
              <i class="fas fa-camera"></i>
            </button>
                    </div>
                </div>

                <div class="profile-details">
                    <h3 id="driverName"><?= htmlspecialchars($driverInfo['name'] ?? $driverInfo['username'] ?? 'Driver') ?></h3>
                    <p id="driverId" class="muted">ID: <?= htmlspecialchars($driverInfo['driver_id'] ?? $driverInfo['id']) ?></p>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Email</label>
                            <p id="emailDisplay"><?= htmlspecialchars($driverInfo['email'] ?? '--') ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p id="phoneDisplay"><?= htmlspecialchars($driverInfo['phone'] ?? '--') ?></p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p id="statusDisplay" class="status-badge"><?= htmlspecialchars(ucfirst($driverInfo['status'] ?? 'active')) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Joined</label>
                            <p id="joinedDisplay"><?= htmlspecialchars(isset($driverInfo['created_at']) ? date('Y-m-d', strtotime($driverInfo['created_at'])) : '--') ?></p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button id="editProfileBtn" class="btn primary">
              <i class="fas fa-edit"></i> Edit Profile
            </button>
                        <button id="changePasswordBtn" class="btn secondary">
              <i class="fas fa-key"></i> Change Password
            </button>
                    </div>
                </div>
            </div>

            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Total Tasks</h4>
                        <p id="totalTasks" class="stat-value"><?= $totalTasks ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Completed</h4>
                        <p id="completedTasks" class="stat-value"><?= $completedTasks ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Average Rating</h4>
                        <p id="averageRating" class="stat-value"><?= $averageRating ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Pending Tasks</h4>
                        <p id="pendingTasks" class="stat-value"><?= $pendingTasks ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="profile-footer">
        <p>&copy; <span id="year"></span> Samlina Global Nig Limited</p>
    </footer>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal" role="dialog" aria-labelledby="editProfileTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editProfileTitle">Edit Profile</h3>
                <button class="modal-close" aria-label="Close modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="editProfileForm" novalidate>
                <div class="form-group">
                    <label for="editName">Full Name *</label>
                    <input id="editName" name="editName" type="text" required placeholder="Your full name" value="<?= htmlspecialchars($driverInfo['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="editEmail">Email *</label>
                    <input id="editEmail" name="editEmail" type="email" required placeholder="your@email.com" value="<?= htmlspecialchars($driverInfo['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="editPhone">Phone *</label>
                    <input id="editPhone" name="editPhone" type="tel" required placeholder="+234 800 000 0000" value="<?= htmlspecialchars($driverInfo['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="editAddress">Address</label>
                    <input id="editAddress" name="editAddress" type="text" placeholder="Your address" value="<?= htmlspecialchars($driverInfo['address'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="editCity">City</label>
                    <input id="editCity" name="editCity" type="text" placeholder="Your city" value="<?= htmlspecialchars($driverInfo['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="editState">State</label>
                    <input id="editState" name="editState" type="text" placeholder="Your state" value="<?= htmlspecialchars($driverInfo['state'] ?? '') ?>">
                </div>

                <div id="editProfileMsg" class="form-msg"></div>

                <div class="modal-actions">
                    <button type="button" class="btn secondary modal-close">Cancel</button>
                    <button type="submit" class="btn primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal" role="dialog" aria-labelledby="changePasswordTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="changePasswordTitle">Change Password</h3>
                <button class="modal-close" aria-label="Close modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="changePasswordForm" novalidate>
                <div class="form-group">
                    <label for="currentPassword">Current Password *</label>
                    <div class="password-row">
                        <input id="currentPassword" name="currentPassword" type="password" required placeholder="Enter current password">
                        <button type="button" class="icon-btn toggle-pwd" data-target="currentPassword" aria-label="Show password">
              <i class="far fa-eye"></i>
            </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password *</label>
                    <div class="password-row">
                        <input id="newPassword" name="newPassword" type="password" required placeholder="Enter new password (min 8 chars)">
                        <button type="button" class="icon-btn toggle-pwd" data-target="newPassword" aria-label="Show password">
              <i class="far fa-eye"></i>
            </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <div class="password-row">
                        <input id="confirmPassword" name="confirmPassword" type="password" required placeholder="Confirm new password">
                        <button type="button" class="icon-btn toggle-pwd" data-target="confirmPassword" aria-label="Show password">
              <i class="far fa-eye"></i>
            </button>
                    </div>
                </div>

                <div id="changePasswordMsg" class="form-msg"></div>

                <div class="modal-actions">
                    <button type="button" class="btn secondary modal-close">Cancel</button>
                    <button type="submit" class="btn primary">
            <i class="fas fa-check"></i> Update Password
          </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Avatar Upload Modal (optional) -->
    <div id="uploadAvatarModal" class="modal" role="dialog" aria-labelledby="uploadAvatarTitle" aria-hidden="true">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 id="uploadAvatarTitle">Change Avatar</h3>
                <button class="modal-close" aria-label="Close modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="uploadAvatarForm" method="post" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="upload_avatar">
                <div class="form-group">
                    <label for="avatarFile">Select Image (JPG, PNG, max 2MB)</label>
                    <input id="avatarFile" name="avatar" type="file" accept="image/jpeg,image/png" required>
                    <div id="avatarPreview" class="preview-box"></div>
                </div>
                <div id="uploadAvatarMsg" class="form-msg"></div>
                <div class="modal-actions">
                    <button type="button" class="btn secondary modal-close">Cancel</button>
                    <button type="submit" class="btn primary">
            <i class="fas fa-upload"></i> Upload
          </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // small toggle for mobile nav (existing behavior)
    document.getElementById('hamburger')?.addEventListener('click', function(){
        var nav = document.getElementById('topNav');
        if (!nav) return;
        var hidden = nav.getAttribute('aria-hidden') === 'true';
        nav.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    });

    // set current year in footer
    document.getElementById('year').textContent = new Date().getFullYear();
    </script>

    <!-- Optionally include your client JS -->
    <!-- <script src="../js/drivers_profile.js"></script> -->
    <script>
    // Modal handling
        // Modal helpers for drivers_profile page
    document.addEventListener('DOMContentLoaded', function () {
      function openModal(modal) {
        if (!modal) return;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
      }
    
      function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
      }
    
      // Buttons that open modals
      var editBtn = document.getElementById('editProfileBtn');
      var changePwdBtn = document.getElementById('changePasswordBtn');
      var changeAvatarBtn = document.getElementById('changeAvatarBtn');
    
      // Modal elements
      var editModal = document.getElementById('editProfileModal');
      var pwdModal = document.getElementById('changePasswordModal');
      var avatarModal = document.getElementById('uploadAvatarModal');
    
      if (editBtn) editBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(editModal);
      });
    
      if (changePwdBtn) changePwdBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(pwdModal);
      });
    
      if (changeAvatarBtn) changeAvatarBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal(avatarModal);
      });
    
      // Close buttons (any element with .modal-close)
      document.querySelectorAll('.modal-close').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var modal = btn.closest('.modal');
          closeModal(modal);
        });
      });
    
      // Close when clicking backdrop (only close if click target is the modal container)
      [editModal, pwdModal, avatarModal].forEach(function (m) {
        if (!m) return;
        m.addEventListener('click', function (e) {
          if (e.target === m) closeModal(m);
        });
      });
    
      // Close on Escape key
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
          [editModal, pwdModal, avatarModal].forEach(function (m) { if (m && m.classList.contains('open')) closeModal(m); });
        }
      });
    });


        // showToast(msg, type) - type: 'success'|'error'|'info'
    function showToast(msg, type = 'info') {
      var existing = document.getElementById('driverToast');
      if (!existing) {
        existing = document.createElement('div');
        existing.id = 'driverToast';
        Object.assign(existing.style, {
          position: 'fixed',
          right: '16px',
          top: '72px',
          zIndex: 9999,
          minWidth: '200px',
          maxWidth: '320px',
          padding: '10px 14px',
          borderRadius: '8px',
          boxShadow: '0 8px 20px rgba(2,6,23,0.15)',
          color: '#fff',
          fontSize: '0.95rem',
          opacity: '0',
          transition: 'opacity .18s ease, transform .18s ease',
          transform: 'translateY(-6px)'
        });
        document.body.appendChild(existing);
      }
      existing.textContent = msg;
      existing.style.background = (type === 'success') ? '#2e7d32' : (type === 'error') ? '#b71c1c' : '#1976d2';
      existing.style.opacity = '1';
      existing.style.transform = 'translateY(0)';
      clearTimeout(existing._hideTimer);
      existing._hideTimer = setTimeout(function () {
        existing.style.opacity = '0';
        existing.style.transform = 'translateY(-6px)';
      }, 4000);
    }    if (editProfileForm) {
      editProfileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const name = document.getElementById('editName').value.trim();
          const email = document.getElementById('editEmail').value.trim();
          const phone = document.getElementById('editPhone').value.trim();
          const address = document.getElementById('editAddress').value.trim();
    
          if (!name || !email || !phone) {
            showToast('Name, email and phone are required', 'error');
            return;
          }
    
          // Send JSON to server endpoint
          const payload = {
            action: 'update_profile',
            editName: name,
            editEmail: email,
            editPhone: phone,
            editAddress: address
          };
    
          const resp = await fetch('drivers_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
    
          const data = await resp.json().catch(() => ({ success: false, message: 'Invalid server response' }));
    
          if (data && data.success) {
            showToast(data.message || 'Profile updated', 'success');
            // Update UI values
            const driverNameEl = document.getElementById('driverName');
            const emailDisplay = document.getElementById('emailDisplay');
            const phoneDisplay = document.getElementById('phoneDisplay');
            if (driverNameEl) driverNameEl.textContent = name;
            if (emailDisplay) emailDisplay.textContent = email;
            if (phoneDisplay) phoneDisplay.textContent = phone;
    
            // close modal if available
            if (typeof closeModal === 'function') {
              closeModal(editProfileModal);
            } else {
              // fallback: hide by class
              if (editProfileModal) { editProfileModal.classList.remove('open'); editProfileModal.setAttribute('aria-hidden','true'); }
            }
          } else {
            showToast(data.message || 'Failed to update profile', 'error');
          }
        } catch (err) {
          console.error('Profile update failed:', err);
          showToast('Server error while updating profile', 'error');
        }
      });
    }    if (changePasswordForm) {
      changePasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const current = document.getElementById('currentPassword').value || '';
          const newPwd = document.getElementById('newPassword').value || '';
          const confirm = document.getElementById('confirmPassword').value || '';
    
          if (!current || !newPwd || !confirm) {
            showToast('All password fields are required', 'error');
            return;
          }
          if (newPwd.length < 8) {
            showToast('New password must be at least 8 characters', 'error');
            return;
          }
          if (newPwd !== confirm) {
            showToast('Passwords do not match', 'error');
            // focus confirm field to help user
            const confEl = document.getElementById('confirmPassword');
            if (confEl) confEl.focus();
            return;
          }
    
          // send to server
          const payload = {
            action: 'change_password',
            currentPassword: current,
            newPassword: newPwd,
            confirmPassword: confirm
          };
    
          const resp = await fetch('drivers_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
    
          const data = await resp.json().catch(() => ({ success: false, message: 'Invalid server response' }));
    
          if (data && data.success) {
            showToast(data.message || 'Password changed successfully', 'success');
            // close modal
            if (typeof closeModal === 'function') {
              closeModal(changePasswordModal);
            } else {
              if (changePasswordModal) { changePasswordModal.classList.remove('open'); changePasswordModal.setAttribute('aria-hidden','true'); }
            }
          } else {
            showToast(data.message || 'Failed to change password', 'error');
          }
        } catch (err) {
          console.error('Change password failed:', err);
          showToast('Server error while changing password', 'error');
        }
      });
    }

    </script>

        <?php if (!empty($avatarFlash)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // call existing showToast function
      try {
        showToast(<?php echo json_encode($avatarFlash); ?>, 'success');
      } catch (e) {
        // fallback: simple alert if showToast isn't available yet
        console.log('Avatar flash:', <?php echo json_encode($avatarFlash); ?>);
      }
    });
    </script>
    <?php endif; ?>
</body>

</html>