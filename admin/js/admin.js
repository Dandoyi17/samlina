// admin/js/admin.js
// Admin system using localStorage WITHOUT JSON/AJAX.
// Records are stored as encoded strings and managed via index keys.

// ----------------- Utility: serialization helpers -----------------
function encodeField(v) {
    return encodeURIComponent(v == null ? '' : String(v));
}

function decodeField(v) {
    try {
        return decodeURIComponent(v == null ? '' : String(v));
    } catch (e) {
        return v;
    }
}

// ---------- Admin storage helpers (no JSON) ----------
function adminKey(email) {
    return 'admin:' + email.toLowerCase();
}

// Add or update an admin record (object: {name,email,phone,password,registeredDate})
function setAdminRecord(admin) {
    const key = adminKey(admin.email);
    const value = [
        encodeField(admin.name),
        encodeField(admin.email),
        encodeField(admin.phone),
        encodeField(admin.password),
        encodeField(admin.registeredDate || '')
    ].join('|');
    localStorage.setItem(key, value);

    // update index
    let index = localStorage.getItem('admin_index') || '';
    const emails = index ? index.split(',') : [];
    const lower = admin.email.toLowerCase();
    if (!emails.includes(lower)) {
        emails.push(lower);
        localStorage.setItem('admin_index', emails.join(','));
    }
}

function getAdminRecord(email) {
    if (!email) return null;
    const key = adminKey(email);
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    const parts = raw.split('|');
    return {
        name: decodeField(parts[0] || ''),
        email: decodeField(parts[1] || ''),
        phone: decodeField(parts[2] || ''),
        password: decodeField(parts[3] || ''),
        registeredDate: decodeField(parts[4] || '')
    };
}

function getAllAdminRecords() {
    const index = localStorage.getItem('admin_index') || '';
    if (!index) return [];
    return index.split(',').map(email => getAdminRecord(email)).filter(Boolean);
}

// ---------- Booking storage helpers (no JSON) ----------
function bookingKey(id) {
    return 'booking:' + id;
}

function setBookingRecord(booking) {
    // booking fields order:
    // id,name,email,phone,vehicleType,pickupDate,returnDate,pickupLocation,destination,status,requestDate,notes
    const value = [
        encodeField(booking.id),
        encodeField(booking.name),
        encodeField(booking.email),
        encodeField(booking.phone),
        encodeField(booking.vehicleType),
        encodeField(booking.pickupDate),
        encodeField(booking.returnDate),
        encodeField(booking.pickupLocation),
        encodeField(booking.destination),
        encodeField(booking.status),
        encodeField(booking.requestDate),
        encodeField(booking.notes || '')
    ].join('|');
    localStorage.setItem(bookingKey(booking.id), value);

    // update booking index
    let idx = localStorage.getItem('booking_index') || '';
    const ids = idx ? idx.split(',') : [];
    if (!ids.includes(booking.id)) {
        ids.push(booking.id);
        localStorage.setItem('booking_index', ids.join(','));
    }
}

function getBookingRecord(id) {
    if (!id) return null;
    const raw = localStorage.getItem(bookingKey(id));
    if (!raw) return null;
    const parts = raw.split('|');
    return {
        id: decodeField(parts[0] || ''),
        name: decodeField(parts[1] || ''),
        email: decodeField(parts[2] || ''),
        phone: decodeField(parts[3] || ''),
        vehicleType: decodeField(parts[4] || ''),
        pickupDate: decodeField(parts[5] || ''),
        returnDate: decodeField(parts[6] || ''),
        pickupLocation: decodeField(parts[7] || ''),
        destination: decodeField(parts[8] || ''),
        status: decodeField(parts[9] || ''),
        requestDate: decodeField(parts[10] || ''),
        notes: decodeField(parts[11] || '')
    };
}

function getAllBookingRecords() {
    const index = localStorage.getItem('booking_index') || '';
    if (!index) return [];
    return index.split(',').map(id => getBookingRecord(id)).filter(Boolean);
}

// ----------------- Initialization (seed defaults) -----------------
function initializeAdminSystem() {
    // Seed default admin if none
    const adminIndex = localStorage.getItem('admin_index');
    if (!adminIndex) {
        setAdminRecord({
            name: 'Samlina Admin',
            email: 'admin@samlina.com',
            phone: '08123917323',
            password: 'password123',
            registeredDate: new Date().toISOString()
        });
        console.log('Default admin created: admin@samlina.com / password123');
    }

    // Seed some default bookings for dashboard/testing if none
    const bookingIndex = localStorage.getItem('booking_index') || '';
    if (!bookingIndex) {
        const b1 = {
            id: 'SB-1001',
            name: 'Adekunle Okafor',
            email: 'adekunle@example.com',
            phone: '08012345678',
            vehicleType: 'toyota-prado',
            pickupDate: '2025-12-01',
            returnDate: '2025-12-05',
            pickupLocation: 'Abuja',
            destination: 'Lagos',
            status: 'Pending',
            requestDate: '2025-11-20',
            notes: 'Needs driver for long-distance trip'
        };
        const b2 = {
            id: 'SB-1002',
            name: 'Chioma Anyanwu',
            email: 'chioma@example.com',
            phone: '08087654321',
            vehicleType: 'toyota-camry',
            pickupDate: '2025-12-15',
            returnDate: '2025-12-18',
            pickupLocation: 'Lagos',
            destination: 'Ibadan',
            status: 'Pending',
            requestDate: '2025-11-19',
            notes: 'Executive sedan for business trip'
        };
        setBookingRecord(b1);
        setBookingRecord(b2);
        console.log('Default bookings seeded');
    }
}

// ----------------- Validation helpers -----------------
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]{10,}$/;
    return re.test(phone);
}

// ----------------- UI helpers -----------------
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.classList.add('show');
    }
}

function clearError(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = '';
        element.classList.remove('show');
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#0b7a47' : '#b71c1c'};
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    z-index: 3000;
    animation: slideIn 0.3s ease;
    max-width: 400px;
  `;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// ----------------- Admin actions: login / register / logout -----------------
function handleAdminLogin(email, password) {
    clearError('emailError');
    clearError('passwordError');

    if (!email || !validateEmail(email)) {
        showError('emailError', 'Please enter a valid email address');
        return;
    }
    if (!password || password.length < 3) {
        showError('passwordError', 'Please enter your password');
        return;
    }

    const admin = getAdminRecord(email);
    if (!admin || admin.password !== password) {
        showError('passwordError', 'Invalid email or password');
        return;
    }

    // Login successful: store only email in session
    localStorage.setItem('currentAdmin', admin.email.toLowerCase());
    showNotification('Login successful! Redirecting to dashboard...', 'success');
    setTimeout(() => {
        window.location.href = 'dashboard.php';
    }, 1200);
}

function handleAdminRegistration(name, email, phone, password, confirmPassword) {
    // clear errors
    document.querySelectorAll('.error-msg').forEach(el => {
        el.classList.remove('show');
        el.textContent = '';
    });

    let hasErrors = false;
    if (!name || name.length < 3) {
        showError('nameError', 'Name must be at least 3 characters');
        hasErrors = true;
    }
    if (!email || !validateEmail(email)) {
        showError('regEmailError', 'Please enter a valid email address');
        hasErrors = true;
    }
    if (!phone || !validatePhone(phone)) {
        showError('phoneError', 'Please enter a valid phone number');
        hasErrors = true;
    }
    if (!password || password.length < 6) {
        showError('passwordError', 'Password must be at least 6 characters');
        hasErrors = true;
    }
    if (password !== confirmPassword) {
        showError('confirmPasswordError', 'Passwords do not match');
        hasErrors = true;
    }
    if (hasErrors) return;

    // Check if admin already exists
    if (getAdminRecord(email)) {
        showError('regEmailError', 'This email is already registered');
        return;
    }

    // Register
    const admin = {
        name: name,
        email: email.toLowerCase(),
        phone: phone,
        password: password,
        registeredDate: new Date().toISOString()
    };
    setAdminRecord(admin);

    showNotification('Registration successful! Logging you in...', 'success');
    setTimeout(() => {
        localStorage.setItem('currentAdmin', admin.email);
        window.location.href = 'dashboard.php';
    }, 1200);
}

function handleAdminLogout() {
    localStorage.removeItem('currentAdmin');
    showNotification('Logged out successfully', 'success');
    setTimeout(() => {
        window.location.href = 'admin_login.php';
    }, 800);
}

function checkAdminSession() {
    const email = localStorage.getItem('currentAdmin');
    if (!email) {
        window.location.href = 'admin_login.php';
        return null;
    }
    const admin = getAdminRecord(email);
    if (!admin) {
        // session invalid -> redirect
        localStorage.removeItem('currentAdmin');
        window.location.href = 'admin_login.php';
        return null;
    }
    return admin;
}

// ----------------- Booking helpers (used by dashboard) -----------------
function getAllBookings() {
    return getAllBookingRecords();
}

function updateBookingStatus(bookingId, newStatus) {
    const booking = getBookingRecord(bookingId);
    if (!booking) return false;
    booking.status = newStatus;
    setBookingRecord(booking);
    return true;
}

// ----------------- Initialization on load -----------------
document.addEventListener('DOMContentLoaded', () => {
    initializeAdminSystem();

    // Add animation keyframes if not present (used by notifications)
    if (!document.getElementById('admin-anim-styles')) {
        const style = document.createElement('style');
        style.id = 'admin-anim-styles';
        style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(500px); opacity: 0; } 
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(500px); opacity: 0; }
      }
    `;
        document.head.appendChild(style);
    }
});