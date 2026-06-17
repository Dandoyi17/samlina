// pending.js - show pending tasks and detail modal
document.addEventListener('DOMContentLoaded', () => {
            // Dummy full task list
            const allTasks = [
                { id: 'T-2002', name: 'Lagos Airport Pickup', driver: null, driverId: null, client: 'Zainab Ahmed', status: 'pending', rating: 0, date: '2025-11-22', pickup: 'MMA', destination: 'VI', notes: 'Business traveler' },
                { id: 'T-2006', name: 'Personal Errand Run', driver: null, driverId: null, client: 'Mary Okafor', status: 'pending', rating: 0, date: '2025-11-23', pickup: 'Ajah', destination: 'Lekki Shoprite', notes: 'Short distance' },
                { id: 'T-2004', name: 'Corporate Event Transport', driver: 'Emeka Nwosu', driverId: 'D-1003', client: 'Zenith Bank Plc', status: 'in-progress', rating: 0, date: '2025-11-24', pickup: 'Zenith', destination: 'Lekki', notes: 'VIP' }
            ];

            // pending includes those with status 'pending' (not in-progress or completed)
            const tasks = allTasks.filter(t => ((t.status || '').toLowerCase() === 'pending'));

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
                    tbody.innerHTML = '<tr><td colspan="8" style="padding:1rem;text-align:center;color:#666;">No pending tasks found</td></tr>';
                    totalCount.textContent = '0';
                    return;
                }
                list.forEach(t => {
                    const tr = document.createElement('tr');
                    const driverDisplay = t.driver ? `${escapeHtml(t.driver)} (${escapeHtml(t.driverId)})` : '<span class="muted">Unassigned</span>';
                    tr.innerHTML = `
        <td>${escapeHtml(t.id)}</td>
        <td><strong>${escapeHtml(t.name)}</strong></td>
        <td>${driverDisplay}</td>
        <td>${escapeHtml(t.client)}</td>
        <td><span class="status-badge pending">Pending</span></td>
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
        <div class="detail-item"><div class="detail-label">Assigned Driver</div><div class="detail-value">${escapeHtml(t.driver||'')} ${t.driverId?`(${escapeHtml(t.driverId)})`:''}</div></div>
        <div class="detail-item"><div class="detail-label">Client</div><div class="detail-value">${escapeHtml(t.client)}</div></div>
        <div class="detail-item"><div class="detail-label">Pickup</div><div class="detail-value">${escapeHtml(t.pickup)}</div></div>
        <div class="detail-item"><div class="detail-label">Destination</div><div class="detail-value">${escapeHtml(t.destination)}</div></div>
        <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">${formatDate(t.date)}</div></div>
        <div class="detail-item"><div class="detail-label">Amount</div><div class="detail-value">${escapeHtml(t.amount||'')}</div></div>
      </div>
      <div style="margin-top:12px;"><div class="detail-label">Notes</div><div class="detail-value">${escapeHtml(t.notes)}</div></div>
      <div style="margin-top:12px; display:flex; gap:.5rem; justify-content:flex-end;"><button class="btn" id="closeModalBtn">Close</button></div>
    `;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  }

  function closeModal(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); modalContent.innerHTML=''; }

  // wiring
  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button.view');
    if(!btn) return;
    openModal(btn.dataset.id);
  });

  searchBtn.addEventListener('click', ()=> render(filter(searchInput.value)));
  searchInput.addEventListener('keydown', (e)=>{ if(e.key==='Enter') searchBtn.click(); });
  clearBtn.addEventListener('click', ()=>{ searchInput.value=''; render(tasks); });

  modalClose.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });

  // initial
  render(tasks);
});