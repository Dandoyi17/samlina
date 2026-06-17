<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Drivers - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/drivers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header small-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>All My Drivers</h1>
                <!-- <p class="muted">Manage drivers — view, edit and search</p> -->
            </div>
        </div>

        <div class="header-right controls">
            <div class="search-wrap">
                <input id="searchInput" type="search" placeholder="Search by name or driver ID" aria-label="Search drivers">
                <button id="searchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                <button id="clearSearchBtn" class="btn ghost" title="Reset search"><i class="fas fa-times"></i></button>
            </div>

            <button id="viewAllBtn" class="btn accent"><i class="fas fa-list"></i> View All Drivers</button>
        </div>

        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Drivers List</h2>
                <!-- <p class="muted small">Showing <span id="totalCount">0</span> drivers</p> -->
            </div>

            <div class="table-wrap" id="tableWrap">
                <table id="driversTable" class="responsive-table">
                                        <?php
                    // Fetch drivers from database
                    require_once '../db/config.php';
                    
                    $drivers = [];
                    $error = null;
                    
                    try {
                        // Query only columns that likely exist in your drivers table
                        $sql = "SELECT id, name, phone, vehicle, status FROM drivers ORDER BY id";
                        $result = $conn->query($sql);
                        
                        if (!$result) {
                            $error = "Query failed: " . $conn->error;
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                $drivers[] = $row;
                            }
                        }
                    } catch (Exception $e) {
                        $error = "Error: " . $e->getMessage();
                    }
                    ?>
                    
                    <thead>
                        <tr>
                            <th>Driver ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <!-- <th class="actions-col">Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($error): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:1rem;color:#d32f2f;">
                                    <?php echo htmlspecialchars($error); ?>
                                </td>
                            </tr>
                        <?php elseif (empty($drivers)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:1rem;color:#666;">
                                    No drivers found in database
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drivers as $driver): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($driver['id']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['name']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($driver['vehicle']); ?></td>
                                    <td>
                                        <?php 
                                            $status = strtolower($driver['status'] ?? '');
                                            $statusClass = 'pill pending';
                                            
                                            if ($status === 'online' || $status === 'active' || $status === 'free to work') {
                                                $statusClass = 'pill online';
                                            } elseif ($status === 'offline' || $status === 'not available') {
                                                $statusClass = 'pill offline';
                                            } elseif ($status === 'engaged') {
                                                $statusClass = 'pill pending';
                                            }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($driver['status'])); ?>
                                        </span>
                                    </td>
                                    <!-- <td class="actions-col">
                                        <a href="edit.php?id=<?php echo urlencode($driver['id']); ?>" class="action-btn edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td> -->
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal -->
    <!-- <div id="modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="modal-panel" role="document" id="modalPanel">
            <button class="modal-close" id="modalClose" aria-label="Close modal">&times;</button>
            <div id="modalContent">
                filled by JS 
            </div>
        </div>
    </div> -->

    <!-- <script src="js/drivers.js"></script> -->
</body>

</html>