// fleet.js - client-side fleet management UI (simulated server by default)
document.addEventListener('DOMContentLoaded', () => {
    // Set to false to let the forms post to the server endpoints (add_fleet.php, edit_fleet.php, delete_fleet.php)
    const SIMULATE_SERVER = true;

    // // Dummy fleet data for testing. Replace with server-rendered data for production.
    // let fleet = [{
    //         id: 'CAR-0001',
    //         model: 'Toyota Prado',
    //         make: 'Toyota 2022',
    //         seats: 7,
    //         type: 'SUV',
    //         plate: 'ABC-123DE',
    //         rate: '₦45,000/day',
    //         details: 'Long distance ready',
    //         images: ['images/sample1.jpg', 'images/sample2.jpg', ''],
    //     },
    //     {
    //         id: 'CAR-0002',
    //         model: 'Honda Civic',
    //         make: 'Honda 2021',
    //         seats: 4,
    //         type: 'Sedan',
    //         plate: 'XYZ-987GH',
    //         rate: '₦20,000/day',
    //         details: 'City runs only',
    //         images: ['images/sample3.jpg', '', ''],
    //     }
    // ];

    // DOM refs
    const tbody = document.getElementById('fleetTbody');
    const searchInput = document.getElementById('fleetSearch');
    const searchBtn = document.getElementById('fleetSearchBtn');
    const clearBtn = document.getElementById('fleetClearBtn');
    const addBtn = document.getElementById('addFleetBtn');

    const modal = document.getElementById('fleetModal');
    const modalBackdrop = document.getElementById('fleetBackdrop');
    const modalClose = document.getElementById('fleetModalClose');
    const modalForm = document.getElementById('fleetForm');
    const modalTitle = document.getElementById('fleetModalTitle');
    const carIdInput = document.getElementById('car_id');
    const carIdOriginal = document.getElementById('car_id_original');
    const modelInput = document.getElementById('model');
    const makeInput = document.getElementById('make');
    const seatsInput = document.getElementById('seats');
    const typeInput = document.getElementById('type');
    const plateInput = document.getElementById('plate');
    const rateInput = document.getElementById('rate');
    const detailsInput = document.getElementById('details');

    const image1 = document.getElementById('image1');
    const image2 = document.getElementById('image2');
    const image3 = document.getElementById('image3');
    const preview1 = document.getElementById('preview1');
    const preview2 = document.getElementById('preview2');
    const preview3 = document.getElementById('preview3');
    const formMsg = document.getElementById('fleetFormMsg');
    const cancelBtn = document.getElementById('fleetCancelBtn');

    const viewModal = document.getElementById('viewModal');
    const viewBackdrop = document.getElementById('viewBackdrop');
    const viewClose = document.getElementById('viewClose');
    const viewContent = document.getElementById('viewContent');
    const viewGallery = document.getElementById('viewGallery');
    const viewDetails = document.getElementById('viewDetails');
    const viewCloseBtn = document.getElementById('viewCloseBtn');

    // Utility helpers
    function escapeHtml(s) { if (s == null) return ''; return ('' + s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

    // Render fleet table
    function render(list) {
        tbody.innerHTML = '';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="padding:1rem;color:#666;text-align:center;">No cars found</td></tr>';
            return;
        }
        list.forEach(car => {
            const tr = document.createElement('tr');
            const thumb = car.images && car.images[0] ? `<img src="${escapeHtml(car.images[0])}" class="table-thumb" alt="">` : `<div style="width:96px;height:64px;background:#f6f7f8;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999">No Image</div>`;
            tr.innerHTML = `
        <td>${thumb}</td>
        <td>${escapeHtml(car.id)}</td>
        <td>${escapeHtml(car.model)}</td>
        <td>${escapeHtml(car.seats)}</td>
        <td>${escapeHtml(car.type)}</td>
        <td>${escapeHtml(car.details || '')}</td>
        <td class="actions-col">
          <button class="action-btn view" data-id="${escapeHtml(car.id)}"><i class="fas fa-eye"></i> View</button>
          <button class="action-btn edit" data-id="${escapeHtml(car.id)}"><i class="fas fa-edit"></i> Edit</button>
          <button class="action-btn delete" data-id="${escapeHtml(car.id)}" style="background:#b71c1c;">Delete</button>
        </td>`;
            tbody.appendChild(tr);
        });
    }

    // Filtering
    function filter(q) {
        if (!q) return fleet.slice();
        q = q.trim().toLowerCase();
        return fleet.filter(c =>
            (c.id || '').toLowerCase().includes(q) ||
            (c.model || '').toLowerCase().includes(q) ||
            (c.make || '').toLowerCase().includes(q) ||
            ('' + c.seats).includes(q) ||
            (c.type || '').toLowerCase().includes(q)
        );
    }

    // Open add modal
    function openAddModal() {
        modalTitle.textContent = 'Add New Vehicle';
        modalForm.action = 'add_fleet.php';
        carIdInput.value = '';
        carIdOriginal.value = '';
        modelInput.value = '';
        makeInput.value = '';
        seatsInput.value = '';
        typeInput.value = '';
        plateInput.value = '';
        rateInput.value = '';
        detailsInput.value = '';
        preview1.src = preview2.src = preview3.src = '';
        preview1.style.display = preview2.style.display = preview3.style.display = 'none';
        formMsg.textContent = '';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    // Open edit modal with car data
    function openEditModal(id) {
        const car = fleet.find(c => c.id === id);
        if (!car) return alert('Car not found');
        modalTitle.textContent = 'Edit Vehicle';
        modalForm.action = 'edit_fleet.php';
        carIdInput.value = car.id;
        carIdOriginal.value = car.id;
        modelInput.value = car.model || '';
        makeInput.value = car.make || '';
        seatsInput.value = car.seats || '';
        typeInput.value = car.type || '';
        plateInput.value = car.plate || '';
        rateInput.value = car.rate || '';
        detailsInput.value = car.details || '';
        preview1.src = car.images[0] || '';
        preview2.src = car.images[1] || '';
        preview3.src = car.images[2] || '';
        if (preview1.src) preview1.style.display = 'block';
        else preview1.style.display = 'none';
        if (preview2.src) preview2.style.display = 'block';
        else preview2.style.display = 'none';
        if (preview3.src) preview3.style.display = 'block';
        else preview3.style.display = 'none';
        formMsg.textContent = '';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    // Close modal
    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    // Open view modal for car
    function openViewModal(id) {
        const car = fleet.find(c => c.id === id);
        if (!car) return alert('Car not found');
        document.getElementById('viewTitle').textContent = `${car.model} — ${car.id}`;
        viewGallery.innerHTML = '';
        (car.images || []).forEach(src => {
            if (!src) return;
            const img = document.createElement('img');
            img.src = src;
            viewGallery.appendChild(img);
        });
        viewDetails.innerHTML = `
      <div class="detail-item"><div class="detail-label">Model</div><div class="detail-value">${escapeHtml(car.model)}</div></div>
      <div class="detail-item"><div class="detail-label">Make/Year</div><div class="detail-value">${escapeHtml(car.make)}</div></div>
      <div class="detail-item"><div class="detail-label">Seats</div><div class="detail-value">${escapeHtml(car.seats)}</div></div>
      <div class="detail-item"><div class="detail-label">Type</div><div class="detail-value">${escapeHtml(car.type)}</div></div>
      <div class="detail-item"><div class="detail-label">Plate</div><div class="detail-value">${escapeHtml(car.plate)}</div></div>
      <div class="detail-item"><div class="detail-label">Rate</div><div class="detail-value">${escapeHtml(car.rate)}</div></div>
      <div class="detail-item" style="grid-column:1 / -1;"><div class="detail-label">Details</div><div class="detail-value">${escapeHtml(car.details)}</div></div>
    `;
        viewModal.classList.add('open');
        viewModal.setAttribute('aria-hidden', 'false');
    }

    function closeViewModal() {
        viewModal.classList.remove('open');
        viewModal.setAttribute('aria-hidden', 'true');
    }

    // Preview image input
    function previewFile(inputEl, previewEl) {
        const f = inputEl.files && inputEl.files[0];
        if (!f) { previewEl.style.display = 'none';
            previewEl.src = ''; return; }
        if (!f.type.startsWith('image/')) { alert('Please select an image');
            inputEl.value = ''; return; }
        const reader = new FileReader();
        reader.onload = (e) => { previewEl.src = e.target.result;
            previewEl.style.display = 'block'; };
        reader.readAsDataURL(f);
    }

    // Handle add/edit form submission (simulated or real)
    modalForm.addEventListener('submit', function(e) {
        if (SIMULATE_SERVER) {
            e.preventDefault();
            const form = new FormData(modalForm);
            const carId = form.get('car_id');
            const original = form.get('car_id_original');
            const newCar = {
                id: carId,
                model: form.get('model'),
                make: form.get('make'),
                seats: form.get('seats'),
                type: form.get('type'),
                plate: form.get('plate'),
                rate: form.get('rate'),
                details: form.get('details'),
                images: []
            };
            // read previews for images (if chosen) - using existing preview srcs or files
            newCar.images[0] = preview1.src || '';
            newCar.images[1] = preview2.src || '';
            newCar.images[2] = preview3.src || '';

            if (original) {
                // edit existing
                const idx = fleet.findIndex(c => c.id === original);
                if (idx > -1) fleet[idx] = newCar;
            } else {
                // ensure unique id
                if (fleet.find(c => c.id === carId)) {
                    formMsg.textContent = 'Car ID already exists.';
                    formMsg.style.color = '#b71c1c';
                    return;
                }
                fleet.push(newCar);
            }

            render(filter(searchInput.value));
            formMsg.textContent = 'Saved (simulated). Set SIMULATE_SERVER=false to post to server.';
            formMsg.style.color = '#0b7a47';
            setTimeout(() => closeModal(), 700);
            return;
        }

        // if not simulating, let the form POST to server endpoint; ensure the action is correct (add_fleet.php or edit_fleet.php)
    });

    // Delete confirmation (simulated or post)
    function handleDelete(id) {
        if (!confirm('Delete this vehicle? This action cannot be undone.')) return;
        if (SIMULATE_SERVER) {
            fleet = fleet.filter(c => c.id !== id);
            render(filter(searchInput.value));
            return;
        }
        // For server: create and submit a form
        const f = document.createElement('form');
        f.method = 'post';
        f.action = 'delete_fleet.php';
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'car_id';
        inp.value = id;
        f.appendChild(inp);
        document.body.appendChild(f);
        f.submit();
    }

    // Table click events
    tbody.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('button.view');
        const editBtn = e.target.closest('button.edit');
        const delBtn = e.target.closest('button.delete');
        if (viewBtn) return openViewModal(viewBtn.dataset.id);
        if (editBtn) return openEditModal(editBtn.dataset.id);
        if (delBtn) return handleDelete(delBtn.dataset.id);
    });

    // Image preview events
    image1.addEventListener('change', () => previewFile(image1, preview1));
    image2.addEventListener('change', () => previewFile(image2, preview2));
    image3.addEventListener('change', () => previewFile(image3, preview3));

    // Buttons wiring
    addBtn.addEventListener('click', openAddModal);
    modalClose.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);

    viewClose.addEventListener('click', closeViewModal);
    viewBackdrop.addEventListener('click', closeViewModal);
    viewCloseBtn.addEventListener('click', closeViewModal);

    // Search
    searchBtn.addEventListener('click', () => render(filter(searchInput.value)));
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') searchBtn.click(); });
    clearBtn.addEventListener('click', () => { searchInput.value = '';
        render(fleet); });

    // Initial render
    render(fleet);
});