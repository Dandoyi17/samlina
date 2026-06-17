// ratings.js - render drivers sorted by rating; show chart per-driver tasks
document.addEventListener('DOMContentLoaded', () => {
    // Dummy dataset - replace with server data if available
    const drivers = [{
            id: 'D-1004',
            name: 'Fatima Bello',
            phone: '08033445566',
            vehicle: 'Nissan Almera',
            avgRating: 4.9,
            reviews: 24,
            taskRatings: [5, 5, 4, 5, 5, 4, 5, 5, 5, 5, 5, 4, 5, 5, 5, 4, 5, 5, 5, 5, 5, 5, 4, 5]
        },
        {
            id: 'D-1001',
            name: 'Adekunle Okafor',
            phone: '08012345678',
            vehicle: 'Toyota Prado',
            avgRating: 4.8,
            reviews: 32,
            taskRatings: [5, 5, 5, 4, 5, 5, 5, 4, 5, 5, 5, 4, 5, 5, 5, 5, 5, 5, 5, 4, 5, 5, 5, 4, 5, 5, 5, 5, 5, 5, 5, 5]
        },
        {
            id: 'D-1002',
            name: 'Chioma Anyanwu',
            phone: '08087654321',
            vehicle: 'Toyota Camry',
            avgRating: 4.5,
            reviews: 12,
            taskRatings: [4, 5, 4, 5, 4, 5, 4, 4, 5, 4, 5, 5]
        },
        {
            id: 'D-1003',
            name: 'Emeka Nwosu',
            phone: '08023459876',
            vehicle: 'Honda Civic',
            avgRating: 4.2,
            reviews: 9,
            taskRatings: [4, 4, 5, 4, 3, 4, 5, 4, 4]
        }
    ];

    // DOM refs
    const tbody = document.getElementById('ratingsTbody');
    const countEl = document.getElementById('ratingCount');
    const searchInput = document.getElementById('ratingSearch');
    const searchBtn = document.getElementById('ratingSearchBtn');
    const clearBtn = document.getElementById('ratingClearBtn');

    const chartModal = document.getElementById('chartModal');
    const chartBackdrop = document.getElementById('chartBackdrop');
    const chartClose = document.getElementById('chartClose');
    const chartCloseBtn = document.getElementById('chartCloseBtn');
    const chartTitle = document.getElementById('chartTitle');
    const chartSubtitle = document.getElementById('chartSubtitle');
    const chartCanvas = document.getElementById('ratingsChart');

    let chartInstance = null;

    // sort drivers by avgRating desc
    function getSortedDrivers() {
        return drivers.slice().sort((a, b) => b.avgRating - a.avgRating);
    }

    // render stars html (max 5)
    function starsHtml(r) {
        if (!r) return '<span class="muted">Not rated</span>';
        const full = Math.floor(r);
        const half = r % 1 >= 0.5;
        let html = '';
        for (let i = 0; i < full; i++) html += '<i class="fas fa-star star"></i>';
        if (half) html += '<i class="fas fa-star-half-alt star"></i>';
        for (let i = full + (half ? 1 : 0); i < 5; i++) html += '<i class="far fa-star star"></i>';
        return html + ` <strong>${r.toFixed(1)}</strong>`;
    }

    // render table rows
    function renderTable(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="padding:1rem;text-align:center;color:#666;">No drivers found</td></tr>';
            countEl.textContent = '0';
            return;
        }
        list.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${idx+1}</td>
        <td>${escapeHtml(d.id)}</td>
        <td>${escapeHtml(d.name)}</td>
        <td>${escapeHtml(d.phone)}</td>
        <td>${escapeHtml(d.vehicle)}</td>
        <td>${starsHtml(d.avgRating)}</td>
        <td>${d.reviews}</td>
        <td class="actions-col">
          <button class="action-btn view-chart" data-id="${escapeHtml(d.id)}"><i class="fas fa-chart-bar"></i> View Task Ratings</button>
        </td>`;
            tbody.appendChild(tr);
        });
        countEl.textContent = list.length;
    }

    // escape
    function escapeHtml(s) { if (s == null) return ''; return ('' + s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

    // search filter
    function filterDrivers(q) {
        if (!q) return getSortedDrivers();
        q = q.trim().toLowerCase();
        return getSortedDrivers().filter(d => d.name.toLowerCase().includes(q) || d.id.toLowerCase().includes(q));
    }

    // show modal + chart for driver
    function openChartForDriver(driverId) {
        const d = drivers.find(x => x.id === driverId);
        if (!d) return alert('Driver not found');
        chartTitle.textContent = `Task Ratings for ${d.name}`;
        chartSubtitle.textContent = `${d.reviews} review(s) • Avg: ${d.avgRating.toFixed(2)}`;
        // prepare distribution counts (0..5 stars)
        const counts = [0, 0, 0, 0, 0, 0];
        d.taskRatings.forEach(r => {
            const idx = Math.round(r); // round to nearest int
            counts[Math.max(0, Math.min(5, idx))] += 1;
        });
        // create labels and data
        const labels = ['0', '1', '2', '3', '4', '5'];
        const data = counts;

        // destroy existing chart
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(chartCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Number of tasks by rating',
                    data,
                    backgroundColor: labels.map(l => l === '5' ? '#0b7a47' : l >= '4' ? '#be985b' : '#f39c12'),
                    borderColor: '#fff',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: { legend: { display: false } }
            }
        });

        chartModal.classList.add('open');
        chartModal.setAttribute('aria-hidden', 'false');
    }

    function closeChartModal() {
        chartModal.classList.remove('open');
        chartModal.setAttribute('aria-hidden', 'true');
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
    }

    // event wiring
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button.view-chart');
        if (!btn) return;
        openChartForDriver(btn.dataset.id);
    });

    searchBtn.addEventListener('click', () => renderTable(filterDrivers(searchInput.value)));
    searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') searchBtn.click(); });
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        renderTable(getSortedDrivers());
    });

    chartClose.addEventListener('click', closeChartModal);
    chartCloseBtn.addEventListener('click', closeChartModal);
    chartBackdrop.addEventListener('click', closeChartModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeChartModal(); });

    // initial render
    renderTable(getSortedDrivers());
});