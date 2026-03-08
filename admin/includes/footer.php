<?php
// Admin footer - closing layout
?>
</div> <!-- Close admin-container -->
</div> <!-- Close admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>window.__notifApiUrl = '<?php echo SITE_URL; ?>/api/notifications.php';</script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script>
// Theme Toggle Logic
document.addEventListener('DOMContentLoaded', function() {
    var themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        var icon = themeToggleBtn.querySelector('i');
        
        // Initial icon state
        if (currentTheme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }

        themeToggleBtn.addEventListener('click', function() {
            var newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('adminTheme', newTheme);
            
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
            
            // Optionally update Chart.js colors if charts exist
            if (typeof Chart !== 'undefined' && Chart.instances.length > 0) {
                var isDark = newTheme === 'dark';
                Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
                Chart.instances.forEach(function(chart) {
                    if (chart.options.scales && chart.options.scales.x) {
                        chart.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
                        chart.options.scales.x.grid.color = isDark ? '#334155' : '#f1f5f9';
                    }
                    if (chart.options.scales && chart.options.scales.y) {
                        chart.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
                        chart.options.scales.y.grid.color = isDark ? '#334155' : '#f1f5f9';
                    }
                    if (chart.options.plugins && chart.options.plugins.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = isDark ? '#1e293b' : '#ffffff';
                        chart.options.plugins.tooltip.titleColor = isDark ? '#f8fafc' : '#0f172a';
                        chart.options.plugins.tooltip.bodyColor = isDark ? '#cbd5e1' : '#334155';
                        chart.options.plugins.tooltip.borderColor = isDark ? '#334155' : '#e2e8f0';
                    }
                    chart.update();
                });
            }
        });
    }
});

function clearNotifBadge() {
    var badge = document.getElementById('notifBadge');
    if (badge) badge.style.display = 'none';
    fetch(window.__notifApiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_read'
    }).catch(function(){});
}
function dismissNotifItem(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.transition = 'opacity 0.3s, max-height 0.3s';
    el.style.opacity = '0';
    setTimeout(function(){ el.remove(); checkNotifEmpty(); }, 300);
    fetch(window.__notifApiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=dismiss&key=' + encodeURIComponent(id)
    }).catch(function(){});
}
function checkNotifEmpty() {
    var items = document.querySelectorAll('.notif-item');
    if (items.length === 0) {
        var empty = document.getElementById('notif-empty');
        if (!empty) {
            var menu = document.querySelector('#notifBellToggle + .dropdown-menu');
            if (menu) {
                // Remove group headers
                menu.querySelectorAll('.dropdown-header:not(:first-child)').forEach(function(h){ h.remove(); });
                var li = document.createElement('li');
                li.id = 'notif-empty';
                li.className = 'text-center py-3 text-muted small';
                li.textContent = 'No new notifications';
                menu.appendChild(li);
            }
        }
    }
}
</script>
</body>

</html>