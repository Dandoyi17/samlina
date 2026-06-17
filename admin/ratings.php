<?php
// admin/ratings.php
require_once '../db/config.php';

// Fetch drivers with average rating and reviews count
$rows = [];
$sql = "
SELECT d.id,
       d.driver_id AS code,
       d.name,
       d.phone,
       d.vehicle,
       d.rating AS avg_rating,
       COALESCE(r.reviews_count, 0) AS reviews_count
FROM drivers d
LEFT JOIN (
    SELECT driver_id, COUNT(*) AS reviews_count
    FROM ratings
    GROUP BY driver_id
) r ON r.driver_id = d.id
ORDER BY d.rating DESC, d.name ASC
";
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Driver Ratings - Admin</title>
    <link rel="stylesheet" href="css/admin_drivers.css">
    <link rel="stylesheet" href="css/ratings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="dashboard-header">
        <div class="header-left">
            <img src="../logo/logowhite.png" alt="Logo" class="logo">
            <div>
                <h1>Driver Ratings</h1>
            </div>
        </div>
        <div class="header-right controls">
            <div class="search-wrap">
                <input id="ratingSearch" type="search" placeholder="Search by driver name or ID" aria-label="Search drivers">
                <button id="ratingSearchBtn" class="btn primary"><i class="fas fa-search"></i></button>
                <button id="ratingClearBtn" class="btn ghost"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </header>

    <main class="page-container">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Drivers by Rating</h2>
                <p class="muted small">Showing <span id="ratingCount"><?= count($rows) ?></span> drivers</p>
            </div>

            <div class="table-wrap">
                <table id="ratingsTable" class="responsive-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Driver ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>Avg Rating</th>
                            <th>Reviews</th>
                            <th class="actions-col">Task Ratings</th>
                        </tr>
                    </thead>
                    <tbody id="ratingsTbody">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" style="text-align:center;color:#666;padding:1rem;">No drivers found</td></tr>
                        <?php else: $i = 1; foreach ($rows as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r['code']) ?></td>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['vehicle'] ?? '') ?></td>
                                <td><?= ($r['avg_rating'] !== null) ? number_format((float)$r['avg_rating'], 1) : '-' ?></td>
                                <td><?= intval($r['reviews_count']) ?></td>
                                <td class="actions-col">
                                    <button class="action-btn" onclick="window.location.href='ratings_detail.php?driver_id=<?= intval($r['id']) ?>'">
                                        <i class="fas fa-chart-bar"></i> Task Ratings
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Chart Modal (left for future use) -->
    <div id="chartModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-backdrop" id="chartBackdrop"></div>
        <div class="modal-panel" id="chartPanel">
            <button class="modal-close" id="chartClose" aria-label="Close modal">&times;</button>
            <div style="padding:0.5rem;">
                <h3 id="chartTitle">Task Ratings</h3>
                <p class="muted small" id="chartSubtitle"></p>
                <canvas id="ratingsChart" width="600" height="350"></canvas>
                <div style="margin-top:12px; display:flex; gap:.5rem; justify-content:flex-end;">
                    <button id="chartCloseBtn" class="btn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Minimal client-side search for quick filtering (works on server-rendered rows)
    (function () {
        function escape(s){return (s||'').toString().toLowerCase();}
        const input = document.getElementById('ratingSearch');
        const btn = document.getElementById('ratingSearchBtn');
        const clear = document.getElementById('ratingClearBtn');
        const tbody = document.getElementById('ratingsTbody');

        function filterRows() {
            const q = escape(input.value.trim());
            if (!q) {
                Array.from(tbody.querySelectorAll('tr')).forEach(tr=>tr.style.display='');
                return;
            }
            Array.from(tbody.querySelectorAll('tr')).forEach(tr=>{
                const cols = Array.from(tr.querySelectorAll('td')).map(td=>escape(td.textContent||''));
                const matches = cols.some(c=>c.indexOf(q) !== -1);
                tr.style.display = matches ? '' : 'none';
            });
        }
        if (btn) btn.addEventListener('click', filterRows);
        if (input) input.addEventListener('keydown', (e)=>{ if (e.key==='Enter') filterRows(); });
        if (clear) clear.addEventListener('click', ()=>{ input.value=''; filterRows(); });
    }());
    </script>
</body>

</html>