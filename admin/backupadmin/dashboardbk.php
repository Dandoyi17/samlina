

<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'samlina');
if ($conn->connect_error) {
    die('Database connection failed.');
}

// Get counts for each status
function getCount($conn, $status = null) {
    if ($status === null) {
        $sql = "SELECT COUNT(*) as cnt FROM hire";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM hire WHERE status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $status);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return (int)($row['cnt'] ?? 0);
}

$pendingCount = getCount($conn, 'pending');
$approvedCount = getCount($conn, 'approved');
$rejectedCount = getCount($conn, 'rejected');
$totalCount = getCount($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Samlina Global</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="dashboard">
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="dashboard-nav">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <img src="../logo/logowhite.png" alt="Samlina Global Logo" style="height: 50px; width: auto;">
                <div>
                    <h1 style="font-size: 1.2rem; margin: 0;">Admin Dashboard</h1>
                    <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">Samlina Global Nig Limited</p>
                </div>
            </div>
            <div class="welcome">
                <i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i>
                <span id="adminName">Administrator</span>
            </div>
            <div class="spacer">
                <button class="logout-btn"><a href="fleet.php">Fleets</a></button>
                <button class="logout-btn"><a href="admin_drivers.php">Driver</a></button>
                <button class="logout-btn"><a href="logout.php">Logout</a></button>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="container">
        <!-- Statistics -->
       <div class="dashboard-stats">
    <div class="stat-card">
        <i class="fas fa-hourglass-half" style="font-size: 2rem; color: #be985b;"></i>
        <div class="stat-number" id="pendingCount"><?php echo $pendingCount; ?></div>
        <div class="stat-label">Pending Requests</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle" style="font-size: 2rem; color: #0b7a47;"></i>
        <div class="stat-number" id="approvedCount"><?php echo $approvedCount; ?></div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-times-circle" style="font-size: 2rem; color: #b71c1c;"></i>
        <div class="stat-number" id="rejectedCount"><?php echo $rejectedCount; ?></div>
        <div class="stat-label">Rejected</div>
    </div>
    <div class="stat-card">
        <i class="fas fa-car" style="font-size: 2rem; color: #19496c;"></i>
        <div class="stat-number" id="totalCount"><?php echo $totalCount; ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
</div>

        <!-- Filter Buttons -->
        <div class="section-header">
            <h2>Booking Requests</h2>
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterBookings('all')">All Bookings</button>
            <button class="filter-btn" onclick="filterBookings('Pending')">Pending</button>
            <button class="filter-btn" onclick="filterBookings('Approved')">Approved</button>
            <button class="filter-btn" onclick="filterBookings('Rejected')">Rejected</button>
        </div>

        <!-- Bookings List -->
        <div id="bookingsList"></div>
    </div>

    <!-- Inline Script with all functionality -->
    <!-- <script>
        // ===== DUMMY BOOKING DATA =====
        const allBookings = [{
            id: 'SB-1001',
            name: 'Adekunle Okafor',
            email: 'adekunle@example.com',
            phone: '08012345678',
            vehicleType: 'Toyota Prado',
            pickupDate: '2025-12-01',
            returnDate: '2025-12-05',
            pickupLocation: 'Abuja',
            destination: 'Lagos',
            status: 'Pending',
            requestDate: '2025-11-20',
            notes: 'Needs driver for long-distance trip'
        }, {
            id: 'SB-1002',
            name: 'Chioma Anyanwu',
            email: 'chioma@example.com',
            phone: '08087654321',
            vehicleType: 'Toyota Camry',
            pickupDate: '2025-12-15',
            returnDate: '2025-12-18',
            pickupLocation: 'Lagos',
            destination: 'Ibadan',
            status: 'Pending',
            requestDate: '2025-11-19',
            notes: 'Executive sedan for business trip'
        }, {
            id: 'SB-1003',
            name: 'Emeka Nwankwo',
            email: 'emeka@example.com',
            phone: '08156789012',
            vehicleType: 'Toyota Bus',
            pickupDate: '2025-11-25',
            returnDate: '2025-11-27',
            pickupLocation: 'Kano',
            destination: 'Katsina',
            status: 'Approved',
            requestDate: '2025-11-18',
            notes: 'Group transport for corporate event'
        }];

        let currentFilter = 'all';
        let bookingsToDisplay = allBookings;

        // ===== Initialize page =====
        function initDashboard() {
            document.getElementById('adminName').textContent = 'Admin User';
            updateStats();
            displayBookings();
        }

        // ===== Update statistics =====
        function updateStats() {
            const pending = allBookings.filter(b => b.status === 'Pending').length;
            const approved = allBookings.filter(b => b.status === 'Approved').length;
            const rejected = allBookings.filter(b => b.status === 'Rejected').length;
            const total = allBookings.length;

            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('approvedCount').textContent = approved;
            document.getElementById('rejectedCount').textContent = rejected;
            document.getElementById('totalCount').textContent = total;
        }

        // ===== Filter bookings =====
        function filterBookings(status) {
            currentFilter = status;

            // Update button states
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter data
            if (status === 'all') {
                bookingsToDisplay = allBookings;
            } else {
                bookingsToDisplay = allBookings.filter(b => b.status === status);
            }

            displayBookings();
        }

        // ===== Display bookings =====
        function displayBookings() {
            const container = document.getElementById('bookingsList');

            if (bookingsToDisplay.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Bookings</h3>
                        <p>There are no booking requests to display in this filter.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = bookingsToDisplay.map(booking => `
                <div class="booking-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; color: #19496c;">${booking.name}</h3>
                            <p style="color: #6c757d; margin: 0; font-size: 0.9rem;">Booking ID: <strong>${booking.id}</strong></p>
                        </div>
                        <span class="booking-status status-${booking.status.toLowerCase()}">
                            ${booking.status}
                        </span>
                    </div>

                    <div class="booking-details">
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">${booking.email}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">${booking.phone}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Vehicle Type</span>
                            <span class="detail-value">${booking.vehicleType}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pickup Date</span>
                            <span class="detail-value">${booking.pickupDate}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Return Date</span>
                            <span class="detail-value">${booking.returnDate}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Route</span>
                            <span class="detail-value">${booking.pickupLocation} → ${booking.destination}</span>
                        </div>
                    </div>

                    ${booking.notes ? `<p style="color: #6c757d; margin-top: 1rem; margin-bottom: 1rem;"><strong>Notes:</strong> ${booking.notes}</p>` : ''}

                    ${booking.status === 'Pending' ? `
                        <div class="booking-actions">
                            <button class="action-btn approve-btn" onclick="approveBooking('${booking.id}')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="action-btn reject-btn" onclick="rejectBooking('${booking.id}')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        // ===== Approve booking =====
        // function approveBooking(bookingId) {
        //     if (confirm('Are you sure you want to approve this booking?')) {
        //         const booking = allBookings.find(b => b.id === bookingId);
        //         if (booking) {
        //             booking.status = 'Approved';
        //             updateStats();
        //             displayBookings();
        //             showNotification('Booking approved successfully!', 'success');
        //         }
        //     }
        // }

        // ===== Reject booking =====
        // function rejectBooking(bookingId) {
        //     if (confirm('Are you sure you want to reject this booking?')) {
        //         const booking = allBookings.find(b => b.id === bookingId);
        //         if (booking) {
        //             booking.status = 'Rejected';
        //             updateStats();
        //             displayBookings();
        //             showNotification('Booking rejected.', 'success');
        //         }
        //     }
        // }

        // ===== Logout handler =====
        // function handleLogout() {
        //     if (confirm('Are you sure you want to logout?')) {
        //         showNotification('Logged out successfully', 'success');
        //         setTimeout(() => {
        //             window.location.href = 'admin_login.php';
        //         }, 800);
        //     }
        // }

        // ===== Show notification =====
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

        // ===== Add animation styles =====
        const style = document.createElement('style');
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

        // ===== Initialize on load =====
        window.addEventListener('DOMContentLoaded', initDashboard);
    </script> -->
</body>

</html>