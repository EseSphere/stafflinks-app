<!-- footer.php - StaffLinks mobile footer navigation and scripts -->
<footer class="footer">
    <div class="footer-item"><a href="dashboard.php" class="active" aria-label="Home"><i
                class="bi bi-house-fill"></i></a><span class="footer-label">Home</span></div>
    <div class="footer-item"><a href="app.php" aria-label="Rota"><i class="bi bi-calendar2-week-fill"></i></a><span
            class="footer-label">Rota</span></div>
    <div class="footer-item"><a href="visit-logs.php" aria-label="Past Shift"><i
                class="bi bi-clock-history"></i></a><span class="footer-label">Past Shift</span></div>
    <div class="footer-item"><a href="leave.php" aria-label="Leave"><i class="bi bi-umbrella-fill"></i></a><span
            class="footer-label">Leave</span></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sideNav = document.getElementById('sideNav'),
    overlay = document.getElementById('overlay'),
    menuBtn = document.getElementById('menuBtn'),
    closeNav = document.getElementById('closeNav'),
    datePills = document.querySelectorAll('.date-pill'),
    quickActions = document.querySelectorAll('[data-action]'),
    darkModeBtn = document.getElementById('darkModeBtn');

function openSideNav() {
    sideNav.classList.add('active');
    overlay.classList.add('active')
}

function closeSideNav() {
    sideNav.classList.remove('active');
    overlay.classList.remove('active')
}
menuBtn?.addEventListener('click', openSideNav);
closeNav?.addEventListener('click', closeSideNav);
overlay?.addEventListener('click', closeSideNav);
datePills.forEach(p => p.addEventListener('click', () => {
    datePills.forEach(i => i.classList.remove('active'));
    p.classList.add('active')
}));
quickActions.forEach(b => b.addEventListener('click', () => alert(`${b.dataset.action} section selected`)));
darkModeBtn?.addEventListener('click', () => document.body.classList.toggle('dark-mode'));

AOS.init();

// Prevent double-tap zoom
let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
    const now = new Date().getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);

// Prevent pinch zoom
['gesturestart', 'gesturechange', 'gestureend'].forEach(evt => {
    document.addEventListener(evt, function(e) {
        e.preventDefault();
    });
});

// Force viewport to normal zoom if it changes
function resetZoom() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
}

// Monitor for zoom or resize
window.addEventListener('resize', resetZoom);
window.addEventListener('orientationchange', resetZoom);
// Force zoom reset if user tries to pinch zoom
document.addEventListener("gesturestart", function(e) {
    e.preventDefault();
    document.querySelector("meta[name=viewport]").setAttribute(
        "content",
        "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    );
});
</script>