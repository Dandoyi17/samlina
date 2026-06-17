<?php
// rate_driver.php
// Usage: rate_driver.php?booking_id=BOOKING_ID

session_start();

// Robust DB config include: try multiple likely locations and show a helpful error if not found.
$configPaths = [
    __DIR__ . '/../../db/config.php',
    __DIR__ . '/../db/config.php',
    rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/samlina/db/config.php',
    rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/db/config.php',
];

$configIncluded = false;
foreach ($configPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $configIncluded = true;
        break;
    }
}

if (!$configIncluded) {
    $tried = implode("\n - ", $configPaths);
    die("DB config.php not found. Tried:\n - $tried\n\nPlace `config.php` at one of those locations or update the path in `rate_driver.php`.");
}

$booking_id = trim($_GET['booking_id'] ?? $_POST['booking_id'] ?? '');

if ($booking_id === '') {
    echo "Booking id missing.";
    exit;
}

// Helper: fetch hire & driver info by booking_id
$stmt = $conn->prepare("SELECT h.id AS hire_id, h.driver_id, d.id AS driver_pk, d.name AS driver_name
                        FROM hire h
                        LEFT JOIN drivers d ON h.driver_id = d.id
                        WHERE h.booking_id = ? LIMIT 1");
if (!$stmt) {
    echo "Server error: " . $conn->error;
    exit;
}
$stmt->bind_param('s', $booking_id);
$stmt->execute();
$res = $stmt->get_result();
$hire = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$hire) {
    echo "Booking not found.";
    exit;
}
$hire_id = intval($hire['hire_id']);
$driver_pk = intval($hire['driver_pk'] ?? 0);
$driver_name = $hire['driver_name'] ?? 'Driver';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $rater_name = trim($_POST['rater_name'] ?? '');
    $review = trim($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Please choose a rating between 1 and 5.';
    }

    if (empty($errors)) {
        // Insert or update (overwrite existing rating for same hire).
        // This relies on your `ratings` table having a UNIQUE key on the column(s)
        // that prevent duplicate ratings per hire (e.g., uniq_hire on `hire_id`).
        $sql = "
            INSERT INTO ratings (hire_id, booking_id, driver_id, rater_name, rating, review)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              rater_name = VALUES(rater_name),
              rating = VALUES(rating),
              review = VALUES(review),
              booking_id = VALUES(booking_id),
              driver_id = VALUES(driver_id),
              created_at = NOW()
        ";

        $ins = $conn->prepare($sql);
        if (!$ins) {
            $errors[] = 'Server error preparing statement: ' . $conn->error;
        } else {
            // types: i (hire_id), s (booking_id), i (driver_pk), s (rater_name), i (rating), s (review)
            if (!$ins->bind_param('isisis', $hire_id, $booking_id, $driver_pk, $rater_name, $rating, $review)) {
                $errors[] = 'Bind failed: ' . $ins->error;
            } else {
                if ($ins->execute()) {
                    // mark hire.rated = 1
                    $u = $conn->prepare("UPDATE hire SET rated = 1 WHERE id = ?");
                    if ($u) { $u->bind_param('i', $hire_id); $u->execute(); $u->close(); }

                    // Recompute driver average and update drivers.rating
                    $avgStmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM ratings WHERE driver_id = ?");
                    if ($avgStmt) {
                        $avgStmt->bind_param('i', $driver_pk);
                        $avgStmt->execute();
                        $avgRes = $avgStmt->get_result();
                        $avgRow = $avgRes ? $avgRes->fetch_assoc() : null;
                        $avg = ($avgRow && $avgRow['avg_rating'] !== null) ? round(floatval($avgRow['avg_rating']), 1) : null;
                        $avgStmt->close();

                        if ($avg !== null) {
                            $updateDriver = $conn->prepare("UPDATE drivers SET rating = ? WHERE id = ?");
                            if ($updateDriver) {
                                $updateDriver->bind_param('di', $avg, $driver_pk);
                                $updateDriver->execute();
                                $updateDriver->close();
                            }
                        }
                    }

                    // Redirect to the site home after a successful rating.
                    // Adjust path if your index.php is located elsewhere.
                    header('Location: ..\index.php');
                    exit;
                } else {
                    // Execution failed
                    $errors[] = 'Failed to save rating: ' . $ins->error;
                }
            }
            $ins->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Rate your driver</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;padding:18px;max-width:700px;margin:0 auto}
    .card{border:1px solid #eee;padding:16px;border-radius:8px}
    .stars input{display:none}
    .stars label{font-size:1.6rem;color:#ccc;cursor:pointer;margin-right:.25rem}
    .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label{color:#f5b301}
    .btn{background:#1976d2;color:#fff;border:0;padding:.6rem 1rem;border-radius:6px;cursor:pointer}
    .muted{color:#666;font-size:.95rem}
  </style>
</head>
<body>
  <h2>Rate <?= htmlspecialchars($driver_name) ?></h2>
  <?php if (!empty($errors)): ?>
    <div style="background:#ffebee;color:#b71c1c;padding:.6rem;border-radius:6px;margin-bottom:8px;">
      <?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <p class="muted">Booking ID: <strong><?= htmlspecialchars($booking_id) ?></strong></p>
    <form method="post" action="rate_driver.php?booking_id=<?= urlencode($booking_id) ?>">
      <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_id) ?>">
      <div>
        <label>Your name (optional)<br>
          <input type="text" name="rater_name" placeholder="Your name" style="width:100%;padding:.5rem;margin-top:.25rem" value="<?= isset($rater_name) ? htmlspecialchars($rater_name) : '' ?>">
        </label>
      </div>

      <div style="margin-top:.6rem;">
        <label style="display:block;margin-bottom:.25rem">Rating</label>
        <div class="stars" style="direction:ltr;">
          <?php for($i=5;$i>=1;$i--): ?>
            <input id="star<?= $i ?>" type="radio" name="rating" value="<?= $i ?>" <?= (isset($rating) && intval($rating) === $i) || (!isset($rating) && $i===5) ? 'checked' : '' ?>>
            <label for="star<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <div style="margin-top:.8rem;">
        <label>Review (optional)<br>
          <textarea name="review" rows="4" style="width:100%;padding:.5rem;margin-top:.25rem"><?= isset($review) ? htmlspecialchars($review) : '' ?></textarea>
        </label>
      </div>

      <div style="margin-top:.8rem;text-align:right;">
        <button class="btn" type="submit">Submit rating</button>
      </div>
    </form>
  </div>
</body>
</html>