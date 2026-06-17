// assign.js - manage listing approved tasks and assigning drivers via modal
document.addEventListener('DOMContentLoaded', () => {
    // Set to false to allow a real form POST to server (assign_driver.php).
    // When true the assignment will be simulated in the browser (useful for testing without server).
    const SIMULATE_SERVER = true;

    // Dummy data for tasks and drivers (replace with server data in production)
    let approvedTasks = [
        { id: 'T-2001', name: 'Abuja to Lagos Transfer', driverId: null, driverName: '', client: 'Ahmed', status: 'pending', rating: 0, date: '2025-11-20', pickup: 'Abuja', destination: 'Lagos', amount: '₦45,000', notes: 'Long distance' },
        { id: 'T-2002', name: 'Lagos Airport Pickup', driverId: null, driverName: '', client: 'Zainab', status: 'pending', rating: 0, date: '2025-11-22', pickup: 'MMA', destination: 'VI', amount: '₦12,500', notes: 'Meet at arrivals' },
        { id: 'T-2003', name: 'Corporate Event Transport', driverId: 'D-1003', driverName: 'Emeka Nwosu', client: 'Zenith Bank', status: 'in-progress', rating: 0, date: '2025-11-24', pickup: 'Zenith', destination: 'Lekki', amount: '₦85,000', notes: 'VIP' }
    ];

    const drivers = [
        { id: 'D-1001', name: 'Adekunle Okafor', phone: '08012345678', vehicle: 'Toyota Prado' },
        { id: 'D-1002', name: 'Chioma Anyanwu', phone: '08087654321', vehicle: 'Toyota Camry' },
        { id: 'D-1003', name: 'Emeka Nwosu', phone: '08023459876', vehicle: 'Honda Civic' },
        { id: 'D-1004', name: 'Fatima Bello', phone: '08033445566', vehicle: 'Nissan Almera' }
    ];

    // DOM refs
    const tbody = document.getElementById('assignTbody');
    const total = document.getElementById('assignTotal');
    const searchInput = document.getElementById('assignSearch');
    const searchBtn = document.getElementById('assignSearchBtn');
    const clearBtn = document.getElementById('assignClearBtn');

    const modal = document.getElementById('assignModal');
    const modalBackdrop = document.getElementById('assignBackdrop');
    const modalPanel = document.getElementById('assignPanel');
    const modalClose = document.getElementById('assignClose');

    const modalTaskName = document.getElementById('modalTaskName');
    const modalTaskId = document.getElementById('modalTaskId');
    const modalPickup = document.getElementById('modalPickup');
    const modalDestination = document.getElementById('modalDestination');
    const modalClient = document.getElementById('modalClient');
    const modalAmount = document.getElementById('modalAmount');

    const formTaskId = document.getElementById('form_task_id');
    const formDriverId = document.getElementById('form_driver_id');
    const driverSearch = document.getElementById('driverSearch');
    const driverResults = document.getElementById('driverResults');
    const instructions = document.getElementById('form_instructions');
    const assignForm = document.getElementById('assignForm');
    const assignMessage = document.getElementById('assignMessage');
    const assignCancel = document.getElementById('assignCancel');

    // Utility
    function escapeHtml(s) { if (s == null) return ''; return ('' + s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]); }

    function formatDate(d) { const dt = new Date(d); return dt.toLocaleDateString('en-NG', { year: 'numeric', month: 'short', day: 'numeric' }); }

    // Render table rows
    function renderTable(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="padding:1rem;text-align:center;color:#666;">No tasks found</td></tr>';
            total.textContent = '0';
            return;
        }
        list.forEach(t => {
            const tr = document.createElement('tr');
            const driverDisplay = t.driverId ? `${escapeHtml(t.driverName)} (${escapeHtml(t.driverId)})` : '<span class="muted">Unassigned</span>';
            const statusClass = t.status ? t.status.replace(/\s+/g, '-').toLowerCase() : 'pending';
            const ratingDisplay = t.rating ? `${t.rating.toFixed(1)}★` : '<span class="muted">Not rated</span>';
            tr.innerHTML = `
        <td>${escapeHtml(t.id)}</td>
        <td><strong>${escapeHtml(t.name)}</strong></td>
        <td>${driverDisplay}</td>
        <td><span class="status-badge ${statusClass}">${escapeHtml(t.status)}</span></td>
        <td>${ratingDisplay}</td>
        <td>${formatDate(t.date)}</td>
        <td class="actions-col">
          <button class="action-btn ${t.driverId ? 'reassign' : ''}" data-action="assign" data-id="${escapeHtml(t.id)}">${t.driverId ? 'Reassign' : 'Assign'}</button>
        </td>`;
            tbody.appendChild(tr);
        });
        total.textContent = list.length;
    }

    // Simple search/filter
    function filterTasks(q) {
        if (!q) return approvedTasks.slice();
        q = q.trim().toLowerCase();
        return approvedTasks.filter(t =>
            (t.id || '').toLowerCase().includes(q) ||
            (t.name || '').toLowerCase().includes(q) ||
            (t.driverName || '').toLowerCase().includes(q) ||
            (t.date || '').toLowerCase().includes(q)
        );
    }

    // Modal open/populate
    function openAssignModal(taskId) {
        const task = approvedTasks.find(x => x.id === taskId);
        if (!task) return alert('Task not found');
        modalTaskName.textContent = task.name;
        modalTaskId.textContent = task.id;
        modalPickup.textContent = task.pickup || '';
        modalDestination.textContent = task.destination || '';
        modalClient.textContent = task.client || '';
        modalAmount.textContent = task.amount || '';
        formTaskId.value = task.id;
        formDriverId.value = ''; // reset
        driverSearch.value = '';
        driverResults.innerHTML = '';
        instructions.value = task.assignmentInstructions || '';
        assignMessage.textContent = '';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        driverSearch.focus();
    }

    function closeAssignModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        driverResults.innerHTML = '';
    }

    // Driver search populate
    function populateDriverResults(q) {
        q = (q || '').trim().toLowerCase();
        const matches = drivers.filter(d => d.name.toLowerCase().includes(q) || d.id.toLowerCase().includes(q));
        if (!matches.length) {
            driverResults.innerHTML = '<li class="no-result">No drivers found</li>';
            return;
        }
        driverResults.innerHTML = matches.map(d => `<li data-id="${escapeHtml(d.id)}">${escapeHtml(d.name)} (${escapeHtml(d.id)})</li>`).join('');
    }

    // Select a driver from results
    function selectDriver(driverId) {
        const d = drivers.find(x => x.id === driverId);
        if (!d) return;
        formDriverId.value = d.id;
        driverSearch.value = `${d.name} (${d.id})`;
        // highlight
        driverResults.querySelectorAll('li').forEach(li => li.classList.toggle('selected', li.dataset.id === driverId));
    }

    // Table action delegation (Assign buttons)
    tbody.addEventListener('click', (ev) => {
        const btn = ev.target.closest('button[data-action="assign"]');
        if (!btn) return;
        const id = btn.dataset.id;
        openAssignModal(id);
    });

    // Search actions
    searchBtn.addEventListener('click', () => renderTable(filterTasks(searchInput.value)));
    searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') searchBtn.click(); });
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        renderTable(approvedTasks);
    });

    // Driver search input
    driverSearch.addEventListener('input', (e) => populateDriverResults(e.target.value));
    driverResults.addEventListener('click', (e) => {
        const li = e.target.closest('li');
        if (!li || !li.dataset.id) return;
        selectDriver(li.dataset.id);
    });

    // modal close wiring
    modalClose.addEventListener('click', closeAssignModal);
    assignCancel.addEventListener('click', closeAssignModal);
    modalBackdrop.addEventListener('click', closeAssignModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAssignModal(); });

    // Form submit handling
    assignForm.addEventListener('submit', (e) => {
        // if you want real server-side save, set SIMULATE_SERVER = false at top of this file.
        if (!formDriverId.value) {
            e.preventDefault();
            assignMessage.textContent = 'Please select a driver before assigning.';
            assignMessage.style.color = '#b71c1c';
            driverSearch.focus();
            return false;
        }

        if (SIMULATE_SERVER) {
            // prevent actual POST and simulate server behavior for immediate UI update
            e.preventDefault();
            const tid = formTaskId.value;
            const did = formDriverId.value;
            const driver = drivers.find(d => d.id === did);
            const instr = instructions.value || '';
            // update task in memory
            const idx = approvedTasks.findIndex(t => t.id === tid);
            if (idx > -1) {
                approvedTasks[idx].driverId = driver.id;
                approvedTasks[idx].driverName = driver.name;
                approvedTasks[idx].assignmentInstructions = instr;
                approvedTasks[idx].status = approvedTasks[idx].status === 'pending' ? 'in-progress' : approvedTasks[idx].status;
            }
            renderTable(filterTasks(searchInput.value));
            assignMessage.textContent = 'Assigned (simulated). Remove SIMULATE_SERVER to post to server.';
            assignMessage.style.color = '#0b7a47';
            setTimeout(() => closeAssignModal(), 700);
            return false;
        }

        // if SIMULATE_SERVER is false, let the form submit normally to server endpoint assign_driver.php
        // server should process the POST and redirect back to this page
        return true;
    });

    // Initial render
    renderTable(approvedTasks);
});