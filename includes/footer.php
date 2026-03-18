<script>
// Dark mode toggle
const body        = document.getElementById('body-root');
const savedTheme  = localStorage.getItem('theme');

if (savedTheme === 'dark') {
    body.classList.add('dark');
}

function toggleDark() {
    body.classList.toggle('dark');
    localStorage.setItem('theme', body.classList.contains('dark') ? 'dark' : 'light');
    const btn = document.getElementById('darkBtn');
    if (btn) btn.textContent = body.classList.contains('dark') ? '☀️ Light Mode' : '🌙 Dark Mode';
}

// Hamburger
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
}

// Password strength checker
function checkStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    const fill   = document.getElementById('strengthFill');
    const text   = document.getElementById('strengthText');
    if (!fill || !text) return;

    const levels = [
        { width: '25%', color: '#dc2626', label: 'Weak' },
        { width: '50%', color: '#d97706', label: 'Fair' },
        { width: '75%', color: '#2563eb', label: 'Good' },
        { width: '100%', color: '#059669', label: 'Strong' },
    ];

    const level  = levels[strength - 1] || levels[0];
    fill.style.width      = level.width;
    fill.style.background = level.color;
    text.textContent      = 'Strength: ' + level.label;
    text.style.color      = level.color;
}
</script>
</body>
</html>