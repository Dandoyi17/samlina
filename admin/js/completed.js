// completed.js - show completed tasks and detail modal
document.addEventListener('DOMContentLoaded', () => {
    // Dummy tasks - replace with server data when ready
    const allTasks = [
        { id: 'T-2001', name: 'Abuja to Lagos Transfer', driver: 'Adekunle Okafor', driverId: 'D-1001', client: 'Ahmed Hassan', status: 'completed', rating: 5, date: '2025-11-20', pickup: 'Abuja', destination: 'Lagos', notes: 'Professional and timely' },
        { id: 'T-2003', name: 'Weekend Leisure Trip', driver: 'Fatima Bello', driverId: 'D-1004', client: 'Chinyere Obi', status: 'completed', rating: 4.5, date: '2025-11-18', pickup: 'VI Lagos', destination: 'Ijebu Ode', notes: 'Very courteous' },
        { id: 'T-2005', name: 'Intercity Logistics', driver: 'Adekunle Okafor', driverId: 'D-1001', client: 'Swift Logistics Ltd', status: 'completed', rating: 5, date: '2025-11-19', pickup: 'Ibadan', destination: 'Abuja', notes: 'Cargo delivered safely' }
    ];

    const tasks = allTasks.filter(t => (t.status || '').toLowerCase() === 'completed');

    const tbody = document.getElementById('tasksTbody');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearBtn');

    // modal refs
    const modal = document.getElementById('detailModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalClose = document.getElementById('modalClose');
    const modalContent = document.getElementById('modalContent');

    function escapeHtml(s) { if (s == null) return ''; return ('' + s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

    function formatDate(d) { const dt = new Date(d); return dt.toLocaleDateString('en-NG', { year: 'numeric', month: 'short', day: 'numeric' }); }

    function starsHtml(r) { if (!r) return '<span class="muted">Not rated</span>'; const full = Math.floor(r); const half = r % 1 >= 0.5; let html = ''; for (let i = 0; i < full; i++) html += '<i class="fas fa-star"></i>'; if (half) html += '<i class="fas fa-star-half-alt"></i>'; for (let i = full + (half ? 1 : 0); i < 5; i++) html += '<i class="far fa-star"></i>'; return `<span class="stars">${html}</span> <strong>${r.toFixed(1)}</strong>`; }

    function render(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="padding:1rem;text-align:center;color:#666;">No completed tasks found</td></tr>';
            totalCount.textContent = '0';
            return;
        }
        list.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHtml(t.id)}</td>
        <td><strong>${escapeHtml(t.name)}</strong></td>
        <td>${escapeHtml(t.driver)} <small class="muted">(${escapeHtml(t.driverId)})</small></td>
        <td>${escapeHtml(t.client)}</td>
        <td><span class="status-badge completed">Completed</span></td>
        <td>${starsHtml(t.rating)}</td>
        <td>${formatDate(t.date)}</td>
        <td class="actions-col"><button class="action-btn view" data-id="${escapeHtml(t.id)}"><i class="fas fa-eye"></i> View</button></td>`;
            tbody.appendChild(tr);
        });
        totalCount.textContent = list.length;
    }

    function filter(q) {
        if (!q) return tasks.slice();
        q = q.trim().toLowerCase();
        return tasks.filter(t => (t.id || '').toLowerCase().includes(q) || (t.name || '').toLowerCase().includes(q) || (t.driver || '').toLowerCase().includes(q) || (t.date || '').includes(q));
    }

    function openModal(taskId) {
        const t = tasks.find(x => x.id === taskId);
        if (!t) return alert('Task not found');
        modalContent.innerHTML = `
      <h3>${escapeHtml(t.name)}</h3>
      <p class="muted small">Task ID: ${escapeHtml(t.id)}</p>
      <div class="detail-grid" style="margin-top:8px;">
        <div class="detail-item"><div class="detail-label">Assigned Driver</div><div class="detail-value">${escapeHtml(t.driver)} (${escapeHtml(t.driverId)})</div></div>
        <div class="detail-item"><div class="detail-label">Client</div><div class="detail-value">${escapeHtml(t.client)}</div></div>
        <div class="detail-item"><div class="detail-label">Pickup</div><div class="detail-value">${escapeHtml(t.pickup)}</div></div>
        <div class="detail-item"><div class="detail-label">Destination</div><div class="detail-value">${escapeHtml(t.destination)}</div></div>
        <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">${formatDate(t.date)}</div></div>
        <div class="detail-item"><div class="detail-label">Amount</div><div class="detail-value">${escapeHtml(t.amount || '')}</div></div>
      </div>
      <div style="margin-top:12px;"><div class="detail-label">Notes</div><div class="detail-value">${escapeHtml(t.notes)}</div></div>
      <div style="margin-top:12px; display:flex; gap:.5rem; justify-content:flex-end;"><button class="btn" id="closeModalBtn">Close</button></div>
    `;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modalContent.innerHTML = '';
    }

    // wiring
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button.view');
        if (!btn) return;
        openModal(btn.dataset.id);
    });

    searchBtn.addEventListener('click', () => render(filter(searchInput.value)));
    searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') searchBtn.click(); });
    clearBtn.addEventListener('click', () => { searchInput.value = '';
        render(tasks); });

    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // initial
    render(tasks);
});