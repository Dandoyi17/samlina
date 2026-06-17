// admin_drivers.js - helpers for active state + hamburger behavior
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger');
    // links may be <a> or <button>; select both
    const items = Array.from(document.querySelectorAll('.sidebar a, .sidebar button'));

    // click handler: set active class and (for anchors) allow default navigation into target iframe
    items.forEach(item => {
        item.addEventListener('click', function(e) {
            // mark active
            items.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            // close sidebar on mobile after click
            if (window.innerWidth <= 920 && sidebar) {
                sidebar.classList.remove('open');
            }
        });
    });

    // hamburger toggles sidebar open on small screens
    if (hamburger) {
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            if (sidebar) sidebar.classList.toggle('open');
        });
    }

    // close sidebar when clicking outside (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 920 && sidebar && hamburger) {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });

    // set initial active based on iframe src if possible
    const iframe = document.getElementById('tabFrame');
    if (iframe) {
        const src = iframe.getAttribute('src') || '';
        const srcFile = src.split('/').pop();
        if (srcFile) {
            const initial = items.find(i => {
                const href = (i.getAttribute('href') || '').split('/').pop();
                return href && href === srcFile;
            });
            if (initial) {
                items.forEach(i => i.classList.remove('active'));
                initial.classList.add('active');
            }
        }
    }
});