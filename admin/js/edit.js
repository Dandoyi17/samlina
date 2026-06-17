// edit.js - fetch drivers from database, search, populate edit form
document.addEventListener('DOMContentLoaded', function() {
    let drivers = [];

    // DOM refs
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');
    const resultsTableBody = document.querySelector('#resultsTable tbody');
    const editCard = document.getElementById('editCard');
    const editTitle = document.getElementById('editTitle');
    const editForm = document.getElementById('editForm');
    const cancelEdit = document.getElementById('cancelEdit');
    const editMessage = document.getElementById('editMessage');
    const profilePreview = document.getElementById('profilePreview') ? document.getElementById('profilePreview').querySelector('img') : null;

    // Fetch all drivers from database
    function fetchDrivers() {
        fetch('drivers.php')
            .then(res => res.json())
            .then(data => {
                drivers = Array.isArray(data) ? data : [];
                renderResults(drivers);
            })
            .catch(err => {
                console.error('Failed to load drivers:', err);
                resultsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1rem;color:#d32f2f;">Failed to load drivers</td></tr>';
            });
    }

    // Render results table
    function renderResults(list) {
        resultsTableBody.innerHTML = '';
        if (!list.length) {
            resultsTableBody.innerHTML = '<tr><td colspan="6" style="padding:1rem;color:#666;text-align:center;">No drivers found</td></tr>';
            return;
        }
        list.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHtml(d.id)}</td>
        <td>${escapeHtml(d.name)}</td>
        <td>${escapeHtml(d.phone)}</td>
        <td>${escapeHtml(d.vehicle)}</td>
        <td>${capitalize(d.status)}</td>
        <td class="actions-col">
          <button class="btn view" data-id="${escapeHtml(d.id)}"><i class="fas fa-eye"></i> View</button>
          <button class="btn edit" data-id="${escapeHtml(d.id)}"><i class="fas fa-edit"></i> Edit</button>
        </td>`;
            resultsTableBody.appendChild(tr);
        });
    }

    // Search filter
    function search(term) {
        if (!term) return drivers.slice();
        term = term.trim().toLowerCase();
        return drivers.filter(d => (d.name || '').toLowerCase().includes(term) || (d.id || '').toLowerCase().includes(term));
    }

    // Escape HTML to prevent injection
    function escapeHtml(text) { 
        if (text == null) return ''; 
        return ('' + text).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); 
    }

    function capitalize(s) { 
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; 
    }

    // Open edit form populated with driver data
    function openEdit(id) {
        const d = drivers.find(x => x.id == id);
        if (!d) return alert('Driver not found');
        editCard.style.display = 'block';
        editTitle.textContent = `${d.name} (${d.id})`;
        
        // Populate form fields
        document.getElementById('original_id').value = d.id;
        document.getElementById('driver_id').value = d.id;
        document.getElementById('name').value = d.name || '';
        document.getElementById('email').value = d.email || '';
        document.getElementById('phone').value = d.phone || '';
        document.getElementById('username').value = d.username || '';
        document.getElementById('address').value = d.address || '';
        document.getElementById('vehicle').value = d.vehicle || '';
        document.getElementById('license_no').value = d.license_no || '';
        document.getElementById('rating').value = d.rating || '';
        document.getElementById('status').value = d.status || 'online';
        
        // Profile preview if image field exists
        if (profilePreview && d.profile) {
            profilePreview.src = d.profile;
            profilePreview.style.display = 'block';
        } else if (profilePreview) {
            profilePreview.style.display = 'none';
            profilePreview.src = '';
        }
        
        editMessage.textContent = '';
        editCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Event delegation for results actions
    resultsTableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;
        if (btn.classList.contains('edit')) openEdit(id);
        else if (btn.classList.contains('view')) {
            const d = drivers.find(x => x.id == id);
            if (!d) return alert('Driver not found');
            alert(`${d.name}\nID: ${d.id}\nPhone: ${d.phone}\nEmail: ${d.email || 'N/A'}\nVehicle: ${d.vehicle}\nStatus: ${d.status}`);
        }
    });

    // Search button
    searchBtn.addEventListener('click', function() {
        const results = search(searchInput.value);
        renderResults(results);
    });
    searchInput.addEventListener('keydown', function(e) { 
        if (e.key === 'Enter') searchBtn.click(); 
    });
    resetBtn.addEventListener('click', function() { 
        searchInput.value = '';
        renderResults(drivers); 
    });

    // Cancel edit
    cancelEdit.addEventListener('click', function() {
        editCard.style.display = 'none';
    });

    // Profile image preview when selecting file
    const profileImage = document.getElementById('profile_image');
    if (profileImage) {
        profileImage.addEventListener('change', function() {
            const f = this.files && this.files[0];
            if (!f) { 
                if (profilePreview) {
                    profilePreview.style.display = 'none';
                    profilePreview.src = '';
                }
                return; 
            }
            if (!f.type.startsWith('image/')) { 
                alert('Please choose an image file');
                this.value = ''; 
                return; 
            }
            const reader = new FileReader();
            reader.onload = function(ev) { 
                if (profilePreview) {
                    profilePreview.src = ev.target.result;
                    profilePreview.style.display = 'block'; 
                }
            };
            reader.readAsDataURL(f);
        });
    }

    // Form submit
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const idVal = document.getElementById('driver_id').value.trim();
        if (!idVal) {
            editMessage.textContent = 'Driver ID is required';
            editMessage.style.color = '#d32f2f';
            return;
        }
        
        editMessage.textContent = 'Submitting changes...';
        editMessage.style.color = '#1976d2';
        
        const formData = new FormData(editForm);
        
        fetch('edit_driver.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                editMessage.textContent = 'Driver updated successfully!';
                editMessage.style.color = '#388e3c';
                setTimeout(() => {
                    editCard.style.display = 'none';
                    fetchDrivers(); // refresh list
                }, 1500);
            } else {
                editMessage.textContent = 'Error: ' + (data.message || 'Failed to update driver');
                editMessage.style.color = '#d32f2f';
            }
        })
        .catch(err => {
            console.error(err);
            editMessage.textContent = 'Error updating driver';
            editMessage.style.color = '#d32f2f';
        });
    });

    // Initial load
    fetchDrivers();
});