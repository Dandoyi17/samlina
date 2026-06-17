/*
drivers_profile.js
- Loads driver profile from localStorage
- Allows edit profile, change password, and upload avatar
- Persists changes to localStorage and optional server
*/

function reportError(err, ctx) {
    try {
        const msg = err && err.message ? err.message : String(err || 'Unknown error');
        console.error('drivers_profile:', ctx || '', err);
        let t = document.getElementById('profileToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'profileToast';
            Object.assign(t.style, {
                position: 'fixed',
                right: '12px',
                top: '72px',
                background: '#b71c1c',
                color: '#fff',
                padding: '8px 12px',
                borderRadius: '8px',
                boxShadow: '0 8px 20px rgba(2,6,23,.2)',
                zIndex: 9999,
                opacity: '0',
                transition: 'opacity .2s'
            });
            document.body.appendChild(t);
        }
        t.textContent = 'An error occurred';
        t.style.opacity = '1';
        setTimeout(() => { t.style.opacity = '0'; }, 2500);
    } catch (e) {
        console.error('reportError failed', e);
    }
}

window.addEventListener('error', (ev) => { reportError(ev.error || ev.message || ev, 'window.error'); });
window.addEventListener('unhandledrejection', (ev) => { reportError(ev.reason || ev, 'unhandledrejection'); });

document.addEventListener('DOMContentLoaded', () => {
    try {
        const driverId = localStorage.getItem('driverLoggedIn') || 'D-UNKNOWN';
        const yearEl = document.getElementById('year');
        if (yearEl) yearEl.textContent = new Date().getFullYear();

        // Sample driver profile (replace with server data)
        const defaultProfile = {
            id: driverId,
            name: 'John Driver',
            email: 'john@samlina.com',
            phone: '+234 800 123 4567',
            address: '123 Main Street',
            city: 'Lagos',
            state: 'Lagos',
            status: 'active',
            joined: '2025-01-15',
            avatar: '',
            totalTasks: 45,
            completedTasks: 42,
            averageRating: 4.8,
            pendingTasks: 2
        };

        const storageKey = `driver_profile_${driverId}`;
        let profile = JSON.parse(localStorage.getItem(storageKey) || 'null') || defaultProfile;

        // DOM elements
        const driverNameEl = document.getElementById('driverName');
        const driverIdEl = document.getElementById('driverId');
        const emailDisplay = document.getElementById('emailDisplay');
        const phoneDisplay = document.getElementById('phoneDisplay');
        const statusDisplay = document.getElementById('statusDisplay');
        const joinedDisplay = document.getElementById('joinedDisplay');
        const avatarImg = document.getElementById('avatarImg');
        const totalTasksEl = document.getElementById('totalTasks');
        const completedTasksEl = document.getElementById('completedTasks');
        const averageRatingEl = document.getElementById('averageRating');
        const pendingTasksEl = document.getElementById('pendingTasks');

        const editProfileBtn = document.getElementById('editProfileBtn');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const changeAvatarBtn = document.getElementById('changeAvatarBtn');

        const hamburger = document.getElementById('hamburger');
        const topNav = document.getElementById('topNav');
        const logoutLink = document.getElementById('logoutLink');

        // Modals
        const editProfileModal = document.getElementById('editProfileModal');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const uploadAvatarModal = document.getElementById('uploadAvatarModal');

        const editProfileForm = document.getElementById('editProfileForm');
        const changePasswordForm = document.getElementById('changePasswordForm');
        const uploadAvatarForm = document.getElementById('uploadAvatarForm');

        // Helper functions
        function showMsg(msg, type, targetId) {
            const msgEl = document.getElementById(targetId);
            if (!msgEl) return;
            msgEl.className = `form-msg ${type}`;
            msgEl.textContent = msg;
            setTimeout(() => { msgEl.className = 'form-msg'; }, 4000);
        }

        function openModal(modal) {
            if (modal) {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function closeModal(modal) {
            if (modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        }

        function saveProfile() {
            try { localStorage.setItem(storageKey, JSON.stringify(profile)); } catch (e) { reportError(e, 'saveProfile'); }
        }

        function displayProfile() {
            try {
                if (driverNameEl) driverNameEl.textContent = profile.name;
                if (driverIdEl) driverIdEl.textContent = `ID: ${profile.id}`;
                if (emailDisplay) emailDisplay.textContent = profile.email;
                if (phoneDisplay) phoneDisplay.textContent = profile.phone;
                if (statusDisplay) statusDisplay.textContent = (profile.status || 'active').charAt(0).toUpperCase() + (profile.status || 'active').slice(1);
                if (joinedDisplay) joinedDisplay.textContent = profile.joined || '--';
                if (avatarImg && profile.avatar) avatarImg.src = profile.avatar;
                if (totalTasksEl) totalTasksEl.textContent = profile.totalTasks || 0;
                if (completedTasksEl) completedTasksEl.textContent = profile.completedTasks || 0;
                if (averageRatingEl) averageRatingEl.textContent = (profile.averageRating || 0).toFixed(1);
                if (pendingTasksEl) pendingTasksEl.textContent = profile.pendingTasks || 0;
            } catch (err) { reportError(err, 'displayProfile'); }
        }

        // Edit Profile Modal
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', () => {
                try {
                    document.getElementById('editName').value = profile.name;
                    document.getElementById('editEmail').value = profile.email;
                    document.getElementById('editPhone').value = profile.phone;
                    document.getElementById('editAddress').value = profile.address || '';
                    document.getElementById('editCity').value = profile.city || '';
                    document.getElementById('editState').value = profile.state || '';
                    openModal(editProfileModal);
                } catch (err) { reportError(err, 'editProfile.open'); }
            });
        }

        if (editProfileForm) {
            editProfileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                try {
                    const name = document.getElementById('editName').value.trim();
                    const email = document.getElementById('editEmail').value.trim();
                    const phone = document.getElementById('editPhone').value.trim();
                    if (!name || !email || !phone) {
                        showMsg('All required fields must be filled', 'error', 'editProfileMsg');
                        return;
                    }
                    profile.name = name;
                    profile.email = email;
                    profile.phone = phone;
                    profile.address = document.getElementById('editAddress').value.trim();
                    profile.city = document.getElementById('editCity').value.trim();
                    profile.state = document.getElementById('editState').value.trim();
                    saveProfile();
                    displayProfile();
                    showMsg('Profile updated successfully', 'success', 'editProfileMsg');
                    setTimeout(() => { closeModal(editProfileModal); }, 1200);
                    // Optional: POST to server
                    // fetch('/api/driver/update_profile.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(profile)}).catch(err => console.error(err));
                } catch (err) {
                    reportError(err, 'editProfile.submit');
                    showMsg('An error occurred', 'error', 'editProfileMsg');
                }
            });
        }

        // Change Password Modal
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', () => {
                try {
                    changePasswordForm.reset();
                    document.getElementById('changePasswordMsg').className = 'form-msg';
                    openModal(changePasswordModal);
                } catch (err) { reportError(err, 'changePassword.open'); }
            });
        }

        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', (e) => {
                e.preventDefault();
                try {
                    const current = document.getElementById('currentPassword').value;
                    const newPwd = document.getElementById('newPassword').value;
                    const confirm = document.getElementById('confirmPassword').value;

                    if (!current || !newPwd || !confirm) {
                        showMsg('All fields are required', 'error', 'changePasswordMsg');
                        return;
                    }
                    if (newPwd.length < 8) {
                        showMsg('New password must be at least 8 characters', 'error', 'changePasswordMsg');
                        return;
                    }
                    if (newPwd !== confirm) {
                        showMsg('Passwords do not match', 'error', 'changePasswordMsg');
                        return;
                    }
                    // For demo: check against localStorage password (replace with server validation)
                    const storedPwd = localStorage.getItem(`driver_password_${driverId}`) || 'password123';
                    if (current !== storedPwd) {
                        showMsg('Current password is incorrect', 'error', 'changePasswordMsg');
                        return;
                    }
                    localStorage.setItem(`driver_password_${driverId}`, newPwd);
                    showMsg('Password changed successfully. You will be logged out.', 'success', 'changePasswordMsg');
                    setTimeout(() => {
                        localStorage.removeItem('driverLoggedIn');
                        window.location.href = 'drivers_login.php';
                    }, 1500);
                    // Optional: POST to server
                    // fetch('/api/driver/change_password.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({driverId, current, newPassword: newPwd})}).catch(err => console.error(err));
                } catch (err) {
                    reportError(err, 'changePassword.submit');
                    showMsg('An error occurred', 'error', 'changePasswordMsg');
                }
            });
        }

        // Avatar Upload
        if (changeAvatarBtn) {
            changeAvatarBtn.addEventListener('click', () => {
                try {
                    document.getElementById('avatarFile').value = '';
                    document.getElementById('avatarPreview').innerHTML = '';
                    document.getElementById('uploadAvatarMsg').className = 'form-msg';
                    openModal(uploadAvatarModal);
                } catch (err) { reportError(err, 'avatar.open'); }
            });
        }

        const avatarFileInput = document.getElementById('avatarFile');
        if (avatarFileInput) {
            avatarFileInput.addEventListener('change', (e) => {
                try {
                    const file = e.target.files[0];
                    if (!file) return;
                    if (file.size > 2 * 1024 * 1024) {
                        document.getElementById('uploadAvatarMsg').className = 'form-msg error';
                        document.getElementById('uploadAvatarMsg').textContent = 'File is too large (max 2MB)';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (evt) => {
                        const previewBox = document.getElementById('avatarPreview');
                        previewBox.innerHTML = `<img src="${evt.target.result}" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                } catch (err) { reportError(err, 'avatar.filechange'); }
            });
        }

        if (uploadAvatarForm) {
            uploadAvatarForm.addEventListener('submit', (e) => {
                e.preventDefault();
                try {
                    const file = avatarFileInput.files[0];
                    if (!file) {
                        document.getElementById('uploadAvatarMsg').className = 'form-msg error';
                        document.getElementById('uploadAvatarMsg').textContent = 'Please select a file';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (evt) => {
                        profile.avatar = evt.target.result; // base64 for demo
                        saveProfile();
                        if (avatarImg) avatarImg.src = profile.avatar;
                        document.getElementById('uploadAvatarMsg').className = 'form-msg success';
                        document.getElementById('uploadAvatarMsg').textContent = 'Avatar updated';
                        setTimeout(() => { closeModal(uploadAvatarModal); }, 1000);
                        // Optional: POST FormData to server for server-side storage
                    };
                    reader.readAsDataURL(file);
                } catch (err) {
                    reportError(err, 'avatar.submit');
                    document.getElementById('uploadAvatarMsg').className = 'form-msg error';
                    document.getElementById('uploadAvatarMsg').textContent = 'An error occurred';
                }
            });
        }

        // Password visibility toggle
        document.querySelectorAll('.toggle-pwd').forEach(btn => {
            btn.addEventListener('click', (e) => {
                try {
                    e.preventDefault();
                    const targetId = e.currentTarget.dataset.target;
                    const input = document.getElementById(targetId);
                    if (input) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            e.currentTarget.innerHTML = '<i class="far fa-eye-slash"></i>';
                        } else {
                            input.type = 'password';
                            e.currentTarget.innerHTML = '<i class="far fa-eye"></i>';
                        }
                    }
                } catch (err) { reportError(err, 'toggle.pwd'); }
            });
        });

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                try {
                    e.preventDefault();
                    const modal = e.currentTarget.closest('.modal');
                    if (modal) closeModal(modal);
                } catch (err) { reportError(err, 'modal.close'); }
            });
        });

        // Close modal on backdrop click
        [editProfileModal, changePasswordModal, uploadAvatarModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', (e) => {
                    try {
                        if (e.target === modal) closeModal(modal);
                    } catch (err) { reportError(err, 'modal.backdrop'); }
                });
            }
        });

        // Hamburger
        if (hamburger && topNav) {
            hamburger.addEventListener('click', () => {
                try {
                    const open = topNav.classList.toggle('open');
                    topNav.setAttribute('aria-hidden', String(!open));
                } catch (err) { reportError(err, 'hamburger'); }
            });
        }

        // Logout
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                try {
                    localStorage.removeItem('driverLoggedIn');
                    window.location.href = 'drivers_login.php';
                } catch (err) { reportError(err, 'logout'); }
            });
        }

        // Initial display
        displayProfile();

    } catch (err) {
        reportError(err, 'init');
    }
});