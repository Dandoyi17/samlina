// drivers.js - fetch drivers from database, search, modals, edit
(function() {
    let drivers = [];

    // DOM refs
    const tbody = document.querySelector('#driversTable tbody');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const viewAllBtn = document.getElementById('viewAllBtn');

    const modal = document.getElementById('modal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalPanel = document.getElementById('modalPanel');
    const modalContent = document.getElementById('modalContent');
    const modalClose = document.getElementById('modalClose');

    function formatRating(r) { return r ? parseFloat(r).toFixed(1) : '—'; }

    function statusClass(s) {
        if (!s) return 'pill pending';
        s = s.toLowerCase();
        if (s === 'online' || s === 'active' || s === 'free to work') return 'pill online';
        if (s === 'offline' || s === 'not available') return 'pill offline';
        if (s === 'engaged') return 'pill pending';
        return 'pill pending';
    }

    // // Fetch all drivers from database
    // function fetchDrivers() {
    //     fetch('fetch_drivers.php')
    //         .then(res => res.json())
    //         .then(data => {
    //             drivers = Array.isArray(data) ? data : [];
    //             renderTable(drivers);
    //         })
    //         .catch(err => {
    //             console.error('Failed to load drivers:', err);
    //             tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;color:#d32f2f;">Failed to load drivers</td></tr>';
    //         });
    // }

    function renderTable(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;color:#666;">No drivers found</td></tr>';
            totalCount.textContent = '0';
            return;
        }
        list.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHtml(d.id)}</td>
        <td>${escapeHtml(d.name)}</td>
        <td>${escapeHtml(d.phone)}</td>
        <td>${escapeHtml(d.vehicle)}</td>
        <td>${formatRating(d.rating)}</td>
        <td><span class="${statusClass(d.status)}">${capitalize(d.status)}</span></td>
        <td class="actions-col">
          <button class="action-btn view" data-id="${escapeHtml(d.id)}"><i class="fas fa-eye"></i> View</button>
          <button class="action-btn edit" data-id="${escapeHtml(d.id)}"><i class="fas fa-edit"></i> Edit</button>
          <button class="action-btn details" data-id="${escapeHtml(d.id)}"><i class="fas fa-search-plus"></i> Details</button>
        </td>`;
            tbody.appendChild(tr);
        });
        totalCount.textContent = list.length;
    }

    // Search by name or id (case-insensitive)
    function filterDrivers(term) {
        if (!term) return drivers.slice();
        term = term.trim().toLowerCase();
        return drivers.filter(d => (d.name || '').toLowerCase().includes(term) || (d.id || '').toLowerCase().includes(term));
    }

    // Modal helpers
    function openModal(html) {
        modalContent.innerHTML = html;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        const firstFocusable = modal.querySelector('button, [href], input, select, textarea');
        if (firstFocusable) firstFocusable.focus();
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modalContent.innerHTML = '';
    }

    // Escape HTML to prevent injection
    function escapeHtml(text) {
        if (text == null) return '';
        return ('' + text).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

    // View single driver summary modal
    function viewDriver(id) {
        const d = drivers.find(x => x.id == id);
        if (!d) return openModal('<p>Driver not found</p>');
        const html = `
      <h3>Driver — ${escapeHtml(d.name)} <small class="muted">(${escapeHtml(d.id)})</small></h3>
      <dl>
        <dt>Phone</dt><dd>${escapeHtml(d.phone)}</dd>
        <dt>Vehicle</dt><dd>${escapeHtml(d.vehicle)}</dd>
        <dt>Rating</dt><dd>${formatRating(d.rating)}</dd>
        <dt>Status</dt><dd>${capitalize(d.status)}</dd>
        <dt>Notes</dt><dd>${escapeHtml(d.notes || '—')}</dd>
      </dl>
      <div style="margin-top:1rem;display:flex;gap:.5rem;">
        <button class="btn accent" id="modalEditBtn">Edit</button>
        <button class="btn" id="modalCloseBtn">Close</button>
      </div>`;
        openModal(html);

        document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
        document.getElementById('modalEditBtn').addEventListener('click', function() {
            closeModal();
            editDriver(id);
        });
    }

    // View all driver details in modal
    function viewAllDetails(id) {
        const d = drivers.find(x => x.id == id);
        if (!d) return openModal('<p>Driver not found</p>');
        const html = `
      <h3>Driver Details — ${escapeHtml(d.name)}</h3>
      <dl>
        <dt>ID</dt><dd>${escapeHtml(d.id)}</dd>
        <dt>Name</dt><dd>${escapeHtml(d.name)}</dd>
        <dt>Phone</dt><dd>${escapeHtml(d.phone)}</dd>
        <dt>Vehicle</dt><dd>${escapeHtml(d.vehicle)}</dd>
        <dt>Rating</dt><dd>${formatRating(d.rating)}</dd>
        <dt>Status</dt><dd>${capitalize(d.status)}</dd>
        <dt>Notes</dt><dd>${escapeHtml(d.notes || '—')}</dd>
      </dl>
      <div style="margin-top:1rem;">
        <button class="btn" id="detailsCloseBtn">Close</button>
      </div>`;
        openModal(html);
        document.getElementById('detailsCloseBtn').addEventListener('click', closeModal);
    }

    // Edit driver form
    function editDriver(id) {
        const d = drivers.find(x => x.id == id);
        if (!d) return;
        const html = `
      <h3>Edit Driver — ${escapeHtml(d.name)}</h3>
      <form id="editForm">
        <label>Driver ID (readonly)<br><input name="id" value="${escapeHtml(d.id)}" readonly></label><br><br>
        <label>Name<br><input name="name" value="${escapeHtml(d.name)}" required></label><br><br>
        <label>Phone<br><input name="phone" value="${escapeHtml(d.phone)}" required></label><br><br>
        <label>Vehicle<br><input name="vehicle" value="${escapeHtml(d.vehicle)}" required></label><br><br>
        <label>Rating<br><input name="rating" type="number" step="0.1" min="0" max="5" value="${escapeHtml(d.rating || 0)}"></label><br><br>
        <label>Status<br>
          <select name="status" required>
            <option value="">-- Select Status --</option>
            <option value="active"${d.status==='active'?' selected':''}>Active</option>
            <option value="online"${d.status==='online'?' selected':''}>Online</option>
            <option value="free to work"${d.status==='free to work'?' selected':''}>Free to Work</option>
            <option value="engaged"${d.status==='engaged'?' selected':''}>Engaged</option>
            <option value="not available"${d.status==='not available'?' selected':''}>Not Available</option>
            <option value="offline"${d.status==='offline'?' selected':''}>Offline</option>
            <option value="pending"${d.status==='pending'?' selected':''}>Pending</option>
          </select>
        </label><br><br>
        <label>Notes<br><textarea name="notes" style="width:100%;height:80px;">${escapeHtml(d.notes || '')}</textarea></label><br><br>
        <div style="display:flex;gap:.5rem;">
          <button class="btn primary" type="submit">Save</button>
          <button class="btn" type="button" id="cancelEditBtn">Cancel</button>
        </div>
        <div id="editMessage" style="margin-top:0.5rem;color:#d32f2f;"></div>
      </form>
    `;
        openModal(html);

        const editForm = document.getElementById('editForm');
        const editMessage = document.getElementById('editMessage');
        document.getElementById('cancelEditBtn').addEventListener('click', closeModal);

        editForm.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const formData = new FormData(editForm);
            editMessage.textContent = 'Saving...';

            fetch('update_driver.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    editMessage.textContent = 'Saved successfully!';
                    setTimeout(() => {
                        closeModal();
                        fetchDrivers(); // refresh list
                    }, 1000);
                } else {
                    editMessage.textContent = 'Error: ' + (data.message || 'Failed to save');
                }
            })
            .catch(err => {
                console.error(err);
                editMessage.textContent = 'Error updating driver';
            });
        });
    }

    // Event delegation for table actions
    function tableClickHandler(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;
        if (btn.classList.contains('view')) viewDriver(id);
        else if (btn.classList.contains('edit')) editDriver(id);
        else if (btn.classList.contains('details')) viewAllDetails(id);
    }

    // Initialize
    function init() {
        fetchDrivers();
        document.querySelector('#driversTable tbody').addEventListener('click', tableClickHandler);
        searchBtn.addEventListener('click', function() {
            const term = searchInput.value || '';
            renderTable(filterDrivers(term));
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') searchBtn.click();
        });
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            renderTable(drivers);
        });
        viewAllBtn.addEventListener('click', function() {
            let html = '<h3>All Drivers</h3><div>';
            if (!drivers.length) {
                html += '<p>No drivers found</p>';
            } else {
                drivers.forEach(d => {
                    html += `<section style="border-bottom:1px solid #eee;padding:0.5rem 0;">
            <strong>${escapeHtml(d.name)} <small class="muted">(${escapeHtml(d.id)})</small></strong>
            <div class="muted">${escapeHtml(d.vehicle)} • ${escapeHtml(d.phone)} • Rating: ${formatRating(d.rating)}</div>
            <div style="margin-top:.25rem;font-size:0.9rem;">${escapeHtml(d.notes || '—')}</div>
          </section>`;
                });
            }
            html += '</div><div style="margin-top:1rem;"><button class="btn" id="closeAllBtn">Close</button></div>';
            openModal(html);
            document.getElementById('closeAllBtn').addEventListener('click', closeModal);
        });

        // Modal close wiring
        modalClose.addEventListener('click', closeModal);
        modalBackdrop.addEventListener('click', closeModal);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    }

    // Start when DOM is ready
    document.addEventListener('DOMContentLoaded', init);
})();