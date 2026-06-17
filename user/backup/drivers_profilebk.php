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
            <a href="#" id="logoutLink">Logout</a>
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
                    <img id="avatarImg" src="../../logo/logowhite.png" alt="Driver avatar" class="avatar">
                    <div class="avatar-overlay">
                        <button id="changeAvatarBtn" class="btn-icon" title="Change avatar">
              <i class="fas fa-camera"></i>
            </button>
                    </div>
                </div>

                <div class="profile-details">
                    <h3 id="driverName">Loading...</h3>
                    <p id="driverId" class="muted">ID: --</p>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Email</label>
                            <p id="emailDisplay">--</p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p id="phoneDisplay">--</p>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <p id="statusDisplay" class="status-badge">--</p>
                        </div>
                        <div class="info-item">
                            <label>Joined</label>
                            <p id="joinedDisplay">--</p>
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
                        <p id="totalTasks" class="stat-value">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Completed</h4>
                        <p id="completedTasks" class="stat-value">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Average Rating</h4>
                        <p id="averageRating" class="stat-value">0.0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Pending Tasks</h4>
                        <p id="pendingTasks" class="stat-value">0</p>
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
                    <input id="editName" type="text" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label for="editEmail">Email *</label>
                    <input id="editEmail" type="email" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label for="editPhone">Phone *</label>
                    <input id="editPhone" type="tel" required placeholder="+234 800 000 0000">
                </div>
                <div class="form-group">
                    <label for="editAddress">Address</label>
                    <input id="editAddress" type="text" placeholder="Your address">
                </div>
                <div class="form-group">
                    <label for="editCity">City</label>
                    <input id="editCity" type="text" placeholder="Your city">
                </div>
                <div class="form-group">
                    <label for="editState">State</label>
                    <input id="editState" type="text" placeholder="Your state">
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
                        <input id="currentPassword" type="password" required placeholder="Enter current password">
                        <button type="button" class="icon-btn toggle-pwd" data-target="currentPassword" aria-label="Show password">
              <i class="far fa-eye"></i>
            </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password *</label>
                    <div class="password-row">
                        <input id="newPassword" type="password" required placeholder="Enter new password (min 8 chars)">
                        <button type="button" class="icon-btn toggle-pwd" data-target="newPassword" aria-label="Show password">
              <i class="far fa-eye"></i>
            </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <div class="password-row">
                        <input id="confirmPassword" type="password" required placeholder="Confirm new password">
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
            <form id="uploadAvatarForm" novalidate>
                <div class="form-group">
                    <label for="avatarFile">Select Image (JPG, PNG, max 2MB)</label>
                    <input id="avatarFile" type="file" accept="image/jpeg,image/png" required>
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

    <!-- <script src="../js/drivers_profile.js"></script> -->
</body>

</html>