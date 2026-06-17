// approved.js - display approved tasks, search, and detail modal
document.addEventListener('DOMContentLoaded', function() {
    // Dummy approved tasks data (replace with server data for production)
    const approvedTasks = [{
            id: 'T-2001',
            name: 'Abuja to Lagos Transfer',
            driver: 'Adekunle Okafor',
            driverId: 'D-1001',
            client: 'Ahmed Hassan',
            status: 'completed',
            rating: 5,
            ratingCount: 1,
            date: '2025-11-20',
            pickup: 'Abuja',
            destination: 'Lagos',
            amount: '₦45,000',
            notes: 'Professional and timely service'
        },
        {
            id: 'T-2002',
            name: 'Lagos Airport Pickup',
            driver: 'Chioma Anyanwu',
            driverId: 'D-1002',
            client: 'Zainab Ahmed',
            status: 'pending',
            rating: 0,
            ratingCount: 0,
            date: '2025-11-22',
            pickup: 'Murtala Muhammed International Airport',
            destination: 'Victoria Island',
            amount: '₦12,500',
            notes: 'Business traveler, early morning'
        },
        {
            id: 'T-2003',
            name: 'Weekend Leisure Trip',
            driver: 'Fatima Bello',
            driverId: 'D-1004',
            client: 'Chinyere Obi',
            status: 'completed',
            rating: 4.5,
            ratingCount: 1,
            date: '2025-11-18',
            pickup: 'VI Lagos',
            destination: 'Ijebu Ode',
            amount: '₦28,000',
            notes: 'Excellent driver, very courteous'
        },
        {
            id: 'T-2004',
            name: 'Corporate Event Transport',
            driver: 'Emeka Nwosu',
            driverId: 'D-1003',
            client: 'Zenith Bank Plc',
            status: 'in-progress',
            rating: 0,
            ratingCount: 0,
            date: '2025-11-24',
            pickup: 'Zenith Bank Head Office',
            destination: 'Lekki Convention Centre',
            amount: '₦85,000',
            notes: 'VIP transport, multiple passengers'
        },
        {
            id: 'T-2005',
            name: 'Intercity Logistics',
            driver: 'Adekunle Okafor',
            driverId: 'D-1001',
            client: 'Swift Logistics Ltd',
            status: 'completed',
            rating: 5,
            ratingCount: 1,
            date: '2025-11-19',
            pickup: 'Ibadan',
            destination: 'Abuja',
            amount: '₦65,000',
            notes: 'Cargo delivered safely and on time'
        },
        {
            id: 'T-2006',
            name: 'Personal Errand Run',
            driver: 'Fatima Bello',
            driverId: 'D-1004',
            client: 'Mary Okafor',
            status: 'pending',
            rating: 0,
            ratingCount: 0,
            date: '2025-11-23',
            pickup: 'Ajah',
            destination: 'Lekki Shoprite',
            amount: '₦5,000',
            notes: 'Short distance, flexible timing'
        }
    ];

    // DOM refs
    const tbody = document.querySelector('#tasksTable tbody');
    const totalCount = document.getElementById('totalCount');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const detailModal = document.getElementById('detailModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalPanel = document.getElementById('modalPanel');
    const modalContent = document.getElementById('modalContent');
    const modalClose = document.getElementById('modalClose');

    // helpers
    function escapeHtml(text) {
        if (text == null) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return ('' + text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-NG', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderStars(rating) {
        if (!rating) return '<span class="muted">Not rated</span>';
        const full = Math.floor(rating);
        const half = rating % 1 >= 0.5 ? 1 : 0;
        let stars = '';
        for (let i = 0; i < full; i++) stars += '<i class="fas fa-star"></i>';
        if (half) stars += '<i class="fas fa-star-half-alt"></i>';
        for (let i = full + half; i < 5; i++) stars += '<i class="far fa-star"></i>';
        return `<span class="stars">${stars}</span> <strong>${rating.toFixed(1)}</strong>`;
    }

    function renderTable(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="padding:1rem;text-align:center;color:#666;">No tasks found</td></tr>';
            totalCount.textContent = '0';
            return;
        }
        list.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHtml(t.id)}</td>
        <td><strong>${escapeHtml(t.name)}</strong></td>
        <td>${escapeHtml(t.driver)}</td>
        <td>${escapeHtml(t.client)}</td>
        <td><span class="status-badge ${t.status}">${capitalize(t.status)}</span></td>
        <td><div class="star-rating">${renderStars(t.rating)}</div></td>
        <td>${formatDate(t.date)}</td>
        <td class="actions-col">
          <button class="action-btn view" data-id="${t.id}"><i class="fas fa-eye"></i> View</button>
        </td>`;
            tbody.appendChild(tr);
        });
        totalCount.textContent = list.length;
    }

    function capitalize(s) {
        return s ? s.charAt(0).toUpperCase() + s.slice(1).replace('-', ' ') : '';
    }

    // search filter (by driver, task name, or date)
    function filterTasks(term) {
        if (!term) return approvedTasks.slice();
        term = term.trim().toLowerCase();
        return approvedTasks.filter(t =>
            (t.driver || '').toLowerCase().includes(term) ||
            (t.name || '').toLowerCase().includes(term) ||
            (t.date || '').includes(term) ||
            (t.driverId || '').toLowerCase().includes(term)
        );
    }

    // modal helpers
    function openModal(html) {
        modalContent.innerHTML = html;
        detailModal.classList.add('open');
        detailModal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        detailModal.classList.remove('open');
        detailModal.setAttribute('aria-hidden', 'true');
        modalContent.innerHTML = '';
    }

    // view task detail
    function viewTask(id) {
        const t = approvedTasks.find(x => x.id === id);
        if (!t) return openModal('<p>Task not found</p>');

        const html = `
      <h3>${escapeHtml(t.name)}</h3>
      <p class="muted small">Task ID: ${escapeHtml(t.id)}</p>

      <div class="detail-grid">
        <div class="detail-item">
          <div class="detail-label">Assigned Driver</div>
          <div class="detail-value">${escapeHtml(t.driver)} <small class="muted">(${escapeHtml(t.driverId)})</small></div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Client</div>
          <div class="detail-value">${escapeHtml(t.client)}</div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Status</div>
          <div class="detail-value"><span class="status-badge ${t.status}">${capitalize(t.status)}</span></div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Client Rating</div>
          <div class="detail-value"><div class="star-rating">${renderStars(t.rating)}</div></div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Pickup Location</div>
          <div class="detail-value">${escapeHtml(t.pickup)}</div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Destination</div>
          <div class="detail-value">${escapeHtml(t.destination)}</div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Amount</div>
          <div class="detail-value"><strong>${escapeHtml(t.amount)}</strong></div>
        </div>

        <div class="detail-item">
          <div class="detail-label">Date</div>
          <div class="detail-value">${formatDate(t.date)}</div>
        </div>
      </div>

      <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #f0f1f2;">
        <div class="detail-label">Notes / Comments</div>
        <div class="detail-value">${escapeHtml(t.notes)}</div>
      </div>

      <div style="margin-top:1rem; display:flex; gap:0.5rem; justify-content:flex-end;">
        <button class="btn primary" id="closeDetailBtn">Close</button>
      </div>`;

        openModal(html);
        document.getElementById('closeDetailBtn').addEventListener('click', closeModal);
    }

    // event wiring
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('button.view');
        if (!btn) return;
        const id = btn.dataset.id;
        if (id) viewTask(id);
    });

    searchBtn.addEventListener('click', function() {
        renderTable(filterTasks(searchInput.value));
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') searchBtn.click();
    });

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        renderTable(approvedTasks);
    });

    // modal close
    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // initial render
    renderTable(approvedTasks);
});