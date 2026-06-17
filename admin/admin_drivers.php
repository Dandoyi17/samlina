<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Drivers Dashboard</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Drivers Admin</h1>
                <!-- <p>Samlina Global Nig Limited</p> -->
            </div>
        </div>
        <div class="header-right">
            <span class="admin-name"><i class="fas fa-user-circle"></i> Administrator</span>
            <button class="logout-btn"><a href="dashboard.php">Back to Dashboard</a></button>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>
    <div class="dashboard-container">
        <nav class="sidebar" id="sidebar">
            <ul>
                <li><a href="drivers.php" target="tabFrame">Drivers</a></li>
                <li><a href="add.php" target="tabFrame">Add New Driver</a></li>
                <li><a href="edit.php" target="tabFrame">Edit Driver</a></li>
                <li><a href="delete.php" target="tabFrame">Delete Driver</a></li>
                <li><a href="approved.php" target="tabFrame">All Approved Tasks</a></li>
                <li><a href="assign.php" target="tabFrame">Assign Approved Task</a></li>
                <li><a href="ratings.php" target="tabFrame">Driver Ratings</a></li>
                <li><a href="completed.php" target="tabFrame">Completed Tasks</a></li>
                <li><a href="pending.php" target="tabFrame">Pending Tasks</a></li>
            </ul>
        </nav>

        <main class="main-content" id="main-content">
            <iframe name="tabFrame" id="tabFrame" src="drivers.php" frameborder="0" style="width:100%;height:70vh;"></iframe>
        </main>
    </div>
    <script src="js/admin_drivers.js"></script>
</body>

</html>