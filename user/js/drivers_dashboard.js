/* drivers_dashboard.js
- Renders a tasks table for the logged-in driver
- Persists locally by default and provides hooks for server sync
*/

// Report error to console and show a small toast to the user
function reportError(err, ctx) {
    try {
        const msg = err && err.message ? err.message : String(err || 'Unknown error');
        console.error('drivers_dashboard:', ctx || '', err);
        let t = document.getElementById('dashboardToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'dashboardToast';
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
        t.textContent = 'An error occurred — please refresh';
        t.style.opacity = '1';
        setTimeout(() => { t.style.opacity = '0'; }, 3500);
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

        const tableBody = document.querySelector('#tasksTable tbody');
        const noTasks = document.getElementById('noTasks');
        const filterStatus = document.getElementById('filterStatus');
        const searchBox = document.getElementById('searchBox');
        const hamburger = document.getElementById('hamburger');
        const topNav = document.getElementById('topNav');

        const sampleTasks = [
            { id: 'T-10001', client: 'Acme Corp', pickup: '12 King St', dropoff: '34 Queen Ave', assignedAt: '2025-11-20 08:12', status: 'assigned' },
            { id: 'T-10002', client: 'Beta LLC', pickup: 'Park Road', dropoff: 'Oak Street', assignedAt: '2025-11-21 10:20', status: 'pickup' },
            { id: 'T-10003', client: 'Gamma Ltd', pickup: 'Main Depot', dropoff: 'Sector 9', assignedAt: '2025-11-23 09:05', status: 'in-process' }
        ];

        const storageKey = `driver_tasks_${driverId}`;
        let tasks = JSON.parse(localStorage.getItem(storageKey) || 'null') || sampleTasks;

        function escapeHtml(s) { if (s == null) return ''; const m = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }; return String(s).replace(/[&<>"]/g, c => m[c] || c); }

        function showTempMessage(msg) {
            let t = document.getElementById('dashboardToast');
            if (!t) { t = document.createElement('div');
                t.id = 'dashboardToast';
                Object.assign(t.style, { position: 'fixed', right: '12px', top: '72px', background: '#0b7a47', color: '#fff', padding: '8px 12px', borderRadius: '8px', boxShadow: '0 8px 20px rgba(2,6,23,.2)', zIndex: 9999, opacity: '0', transition: 'opacity .2s' });
                document.body.appendChild(t); }
            t.textContent = msg;
            t.style.opacity = '1';
            setTimeout(() => { t.style.opacity = '0'; }, 1800);
        }

        function saveTasks(silent) { try { localStorage.setItem(storageKey, JSON.stringify(tasks)); if (!silent) showTempMessage('Saved'); } catch (e) { reportError(e, 'saveTasks'); } }

        function statusLabel(s) {
            if (s === 'assigned') return `<span class="status-pill status-assigned">Assigned</span>`;
            if (s === 'pickup') return `<span class="status-pill status-pickup">Pickup</span>`;
            if (s === 'in-process') return `<span class="status-pill status-in-process">In-Process</span>`;
            if (s === 'completed') return `<span class="status-pill status-completed">Completed</span>`;
            return `<span class="status-pill">${escapeHtml(s)}</span>`;
        }

        function appendShare(row, task) {
            try {
                const actionsCell = row.querySelector('.actions-inline');
                if (!actionsCell) return;
                actionsCell.innerHTML = '';
                const base = window.location.origin + '/user/rate_driver.html';
                const shareUrl = `${base}?driverId=${encodeURIComponent(driverId)}&taskId=${encodeURIComponent(task.id)}`;
                const container = document.createElement('span');
                container.className = 'share-link';
                container.innerHTML = `<input readonly value="${shareUrl}" aria-label="Share link for ${task.id}"><button class="action-btn copy-btn" data-url="${shareUrl}" title="Copy share link"><i class="far fa-copy"></i></button><button class="action-btn open-btn" data-url="${shareUrl}" title="Open share link"><i class="fas fa-external-link-alt"></i></button>`;
                actionsCell.appendChild(container);
                const copyBtn = container.querySelector('.copy-btn');
                if (copyBtn) copyBtn.addEventListener('click', (e) => {
                    const url = e.currentTarget.dataset.url;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(() => showTempMessage('Link copied to clipboard')).catch(() => showTempMessage('Copy failed'));
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = url;
                        document.body.appendChild(ta);
                        ta.select();
                        try { document.execCommand('copy');
                            showTempMessage('Link copied to clipboard'); } catch (err) { showTempMessage('Copy failed'); }
                        ta.remove();
                    }
                });
                const openBtn = container.querySelector('.open-btn');
                if (openBtn) openBtn.addEventListener('click', (e) => { window.open(e.currentTarget.dataset.url, '_blank'); });
            } catch (err) { reportError(err, 'appendShare'); }
        }

        function renderTable() {
            try {
                if (!tableBody) return;
                const q = (searchBox && searchBox.value || '').trim().toLowerCase();
                const statusFilter = (filterStatus && filterStatus.value) || '';
                tableBody.innerHTML = '';
                const filtered = tasks.filter(t => {
                    if (statusFilter && t.status !== statusFilter) return false;
                    if (!q) return true;
                    return [t.id, t.client, t.pickup, t.dropoff].join(' ').toLowerCase().includes(q);
                });
                if (filtered.length === 0) { if (noTasks) noTasks.style.display = 'block'; } else { if (noTasks) noTasks.style.display = 'none'; }
                filtered.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${escapeHtml(t.id)}</td><td>${escapeHtml(t.client)}</td><td>${escapeHtml(t.pickup)}</td><td>${escapeHtml(t.dropoff)}</td><td>${escapeHtml(t.assignedAt)}</td><td><select class="select-inline" data-task="${t.id}" aria-label="Change status for ${t.id}"><option value="assigned"${t.status==='assigned'?' selected':''}>Assigned</option><option value="pickup"${t.status==='pickup'?' selected':''}>Pickup</option><option value="in-process"${t.status==='in-process'?' selected':''}>In-Process</option><option value="completed"${t.status==='completed'?' selected':''}>Completed</option></select></td><td class="actions-cell"><span class="muted">${statusLabel(t.status)}</span><div class="actions-inline" style="display:inline-block;margin-left:8px"></div></td>`;
                    tableBody.appendChild(tr);
                    if (t.status === 'completed') appendShare(tr, t);
                });
            } catch (err) { reportError(err, 'renderTable'); }
        }

        if (tableBody) {
            tableBody.addEventListener('change', (e) => {
                try {
                    const sel = e.target;
                    if (!sel.matches || !sel.matches('select[data-task]')) return;
                    const taskId = sel.dataset.task;
                    const newStatus = sel.value;
                    const task = tasks.find(x => x.id === taskId);
                    if (!task) return;
                    task.status = newStatus;
                    if (newStatus === 'pickup') task.pickedAt = new Date().toISOString();
                    if (newStatus === 'completed') task.completedAt = new Date().toISOString();
                    saveTasks();
                    renderTable();
                } catch (err) { reportError(err, 'statusChange'); }
            });
        }

        if (filterStatus) filterStatus.addEventListener('change', renderTable);
        if (searchBox) searchBox.addEventListener('input', renderTable);

        if (hamburger && topNav) {
            hamburger.addEventListener('click', () => {
                try { const open = topNav.classList.toggle('open');
                    topNav.setAttribute('aria-hidden', String(!open)); } catch (err) { reportError(err, 'hamburger.toggle'); }
            });
        }

        renderTable();
    } catch (err) { reportError(err, 'init'); }
});