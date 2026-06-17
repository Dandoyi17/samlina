// delete.js - search, select, confirm delete UI (no AJAX). Posts to delete_driver.php
document.addEventListener('DOMContentLoaded', function() {
    // Dummy data for UI; replace by server-provided content for production
    let drivers = [
        { id: 'D-1001', name: 'Adekunle Okafor', phone: '08012345678', vehicle: 'Toyota Prado', status: 'online' },
        { id: 'D-1002', name: 'Chioma Anyanwu', phone: '08087654321', vehicle: 'Toyota Camry', status: 'pending' },
        { id: 'D-1003', name: 'Emeka Nwosu', phone: '08023459876', vehicle: 'Honda Civic', status: 'offline' },
        { id: 'D-1004', name: 'Fatima Bello', phone: '08033445566', vehicle: 'Nissan Almera', status: 'online' },
        { id: 'D-1005', name: 'John Smith', phone: '08099887766', vehicle: 'Mercedes Sprinter', status: 'online' }
    ];

    // DOM refs
    const tbody = document.querySelector('#driversTable tbody');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');
    const selectAll = document.getElementById('selectAll');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const deleteIdsInput = document.getElementById('delete_ids');

    // modal refs
    const confirmModal = document.getElementById('confirmModal');
    const confirmBackdrop = document.getElementById('confirmBackdrop');
    const confirmPanel = document.getElementById('confirmPanel');
    const confirmContent = document.getElementById('confirmContent');
    const confirmClose = document.getElementById('confirmClose');

    // helpers
    function escapeHtml(s) { if (s == null) return ''; return ('' + s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

    function capitalize(s) { return s ? s[0].toUpperCase() + s.slice(1) : ''; }

    // render table rows
    function render(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="padding:1rem;color:#666;">No drivers found</td></tr>';
            return;
        }
        list.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td><input type="checkbox" class="row-checkbox" data-id="${escapeHtml(d.id)}"></td>
        <td>${escapeHtml(d.id)}</td>
        <td>${escapeHtml(d.name)}</td>
        <td>${escapeHtml(d.phone)}</td>
        <td>${escapeHtml(d.vehicle)}</td>
        <td>${capitalize(d.status)}</td>
        <td class="actions-col">
          <button class="action-btn view" data-id="${escapeHtml(d.id)}"><i class="fas fa-eye"></i> View</button>
          <button class="action-btn delete" data-id="${escapeHtml(d.id)}"><i class="fas fa-trash"></i> Delete</button>
        </td>`;
            tbody.appendChild(tr);
        });
        updateSelectAllState();
        updateDeleteSelectedState();
    }

    // search function
    function filter(term) {
        if (!term) return drivers.slice();
        term = term.trim().toLowerCase();
        return drivers.filter(d => (d.name || '').toLowerCase().includes(term) || (d.id || '').toLowerCase().includes(term));
    }

    // selection helpers
    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(ch => ch.dataset.id);
    }

    function updateDeleteSelectedState() {
        const any = getSelectedIds().length > 0;
        deleteSelectedBtn.disabled = !any;
    }

    function updateSelectAllState() {
        const boxes = document.querySelectorAll('.row-checkbox');
        if (!boxes.length) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            return;
        }
        const total = boxes.length,
            checked = Array.from(boxes).filter(c => c.checked).length;
        selectAll.checked = checked === total;
        selectAll.indeterminate = checked > 0 && checked < total;
    }

    // modal open/close
    function openConfirm(html) {
        confirmContent.innerHTML = html;
        confirmModal.classList.add('open');
        confirmModal.setAttribute('aria-hidden', 'false');
    }

    function closeConfirm() {
        confirmModal.classList.remove('open');
        confirmModal.setAttribute('aria-hidden', 'true');
        confirmContent.innerHTML = '';
    }

    // single delete flow: opens modal, when confirmed submits a form POST
    function promptDeleteSingle(id) {
        const d = drivers.find(x => x.id === id);
        const html = `
      <h3>Confirm Delete</h3>
      <p>Are you sure you want to permanently delete driver <strong>${escapeHtml(d.name)}</strong> (ID: ${escapeHtml(d.id)})?</p>
      <form id="confirmDeleteForm" method="post" action="delete_driver.php">
        <input type="hidden" name="delete_ids" value="${escapeHtml(d.id)}">
        <div style="margin-top:1rem; display:flex; gap:.5rem; justify-content:flex-end;">
          <button type="submit" class="btn danger">Delete</button>
          <button type="button" class="btn" id="cancelBtn">Cancel</button>
        </div>
      </form>`;
        openConfirm(html);
        // wire cancel
        document.getElementById('cancelBtn').addEventListener('click', closeConfirm);
    }

    // bulk delete flow: prepare delete_ids hidden input and submit
    function promptDeleteBulk(ids) {
        const html = `
      <h3>Confirm Bulk Delete</h3>
      <p>Delete <strong>${ids.length}</strong> selected driver(s)? This cannot be undone.</p>
      <form id="confirmBulkForm" method="post" action="delete_driver.php">
        <input type="hidden" name="delete_ids" value="${escapeHtml(ids.join(','))}">
        <div style="margin-top:1rem; display:flex; gap:.5rem; justify-content:flex-end;">
          <button type="submit" class="btn danger">Delete ${ids.length} Driver(s)</button>
          <button type="button" class="btn" id="cancelBulkBtn">Cancel</button>
        </div>
      </form>`;
        openConfirm(html);
        document.getElementById('cancelBulkBtn').addEventListener('click', closeConfirm);
    }

    // event wiring
    // search
    searchBtn.addEventListener('click', function() {
        render(filter(searchInput.value));
    });
    searchInput.addEventListener('keydown', function(e) { if (e.key === 'Enter') searchBtn.click(); });
    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        render(drivers);
    });

    // select all toggle
    selectAll.addEventListener('change', function() {
        const checked = selectAll.checked;
        document.querySelectorAll('.row-checkbox').forEach(ch => ch.checked = checked);
        updateDeleteSelectedState();
    });

    // row checkbox change (delegation)
    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateSelectAllState();
            updateDeleteSelectedState();
        }
    });

    // actions (delegation)
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;
        if (btn.classList.contains('view')) {
            const d = drivers.find(x => x.id === id);
            alert(`${d.name}\nID: ${d.id}\nPhone: ${d.phone}\nVehicle: ${d.vehicle}\nStatus: ${d.status}`);
        } else if (btn.classList.contains('delete')) {
            promptDeleteSingle(id);
        }
    });

    // delete selected button
    deleteSelectedBtn.addEventListener('click', function() {
        const ids = getSelectedIds();
        if (!ids.length) return;
        promptDeleteBulk(ids);
    });

    // modal close wiring
    confirmClose.addEventListener('click', closeConfirm);
    confirmBackdrop.addEventListener('click', closeConfirm);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeConfirm(); });

    // initial render
    render(drivers);
});