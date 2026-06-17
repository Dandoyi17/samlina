

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Add Driver - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <!-- existing header styles -->
    <link rel="stylesheet" href="css/add.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Add Driver</h1>
                <!-- <p class="muted">Add new driver details</p> -->
            </div>
        </div>
        <!-- <div class="header-right">
            <button class="back-btn" onclick="location.href='drivers.php'"><i class="fas fa-arrow-left"></i> Back</button>
        </div> -->
    </header>

        <?php
    session_start(); // if not already started at top of add.php
    $formMessage = $_SESSION['formMessage'] ?? '';
    $formErrors = $_SESSION['formErrors'] ?? [];
    unset($_SESSION['formMessage'], $_SESSION['formErrors']);
    ?>
    
    <?php if ($formMessage): ?>
        <div style="background:#27ae60;color:#fff;padding:12px 16px;border-radius:6px;margin-bottom:12px;">
            <?php echo htmlspecialchars($formMessage); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($formErrors)): ?>
        <div style="background:#c0392b;color:#fff;padding:12px 16px;border-radius:6px;margin-bottom:12px;">
            <ul style="margin:0 0 0 18px;">
                <?php foreach ($formErrors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <main class="page-container">
        <form id="addDriverForm" class="driver-form card" method="post" action="add_driver.php" enctype="multipart/form-data" novalidate>
            <div class="form-grid">
                <fieldset>
                    <legend>Personal & Account</legend>

                    <label>Full name <span class="required">*</span>
            <input name="name" type="text" required placeholder="e.g. John Doe">
          </label>

                    <label>Driver ID <span class="required">*</span>
            <input name="driver_id" type="text" required placeholder="e.g. D-1006">
          </label>

                    <label>Email <span class="required">*</span>
            <input name="email" type="email" required placeholder="driver@example.com">
          </label>

                    <label>Phone <span class="required">*</span>
            <input name="phone" type="tel" required placeholder="08012345678">
          </label>

                    <label>Username <span class="required">*</span>
            <input name="username" type="text" required placeholder="username">
          </label>

                    <label>Password <span class="required">*</span>
            <input id="password" name="password" type="password" required minlength="6" placeholder="Password">
          </label>

                    <label>Confirm Password <span class="required">*</span>
            <input id="confirmPassword" name="confirm_password" type="password" required minlength="6" placeholder="Confirm password">
            <small id="pwMessage" class="muted" aria-live="polite"></small>
          </label>

                    <label>Address
            <textarea name="address" rows="2" placeholder="Driver address"></textarea>
          </label>
                </fieldset>

                <fieldset>
                    <legend>Vehicle & Documents</legend>

                    <label>Vehicle
            <input name="vehicle" type="text" placeholder="e.g. Toyota Prado">
          </label>

                    <label>License Number
            <input name="license_no" type="text" placeholder="License number">
          </label>

                    <label>Profile Image (jpg/png, &lt; 3MB)
            <input id="profileImage" name="profile_image" type="file" accept="image/*">
            <div class="image-preview" id="imagePreview"><img alt="Preview" src="" /></div>
          </label>

                    <label>License Document (pdf/jpg/png, &lt; 5MB)
            <input id="licenseDoc" name="license_doc" type="file" accept=".pdf,image/*">
          </label>

                    <label>ID Document (pdf/jpg/png, &lt; 5MB)
            <input id="idDoc" name="id_doc" type="file" accept=".pdf,image/*">
          </label>

                    <label>Other Notes
            <textarea name="notes" rows="3" placeholder="Optional notes about the driver"></textarea>
          </label>

                </fieldset>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary">Submit</button>
                <button type="reset" class="btn">Reset</button>
                <!-- <button type="button" class="btn ghost" onclick="location.href='drivers.php'">Back to Drivers</button> -->
            </div>

            <div id="formMessage" role="status" aria-live="polite" class="muted"></div>
        </form>
    </main>

    <script src="js/add.js"></script>
</body>

</html>