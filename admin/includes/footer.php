<?php
// Admin footer - closing layout
?>
</div> <!-- Close admin-container -->
</div> <!-- Close admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>window.__notifApiUrl = '<?php echo SITE_URL; ?>/api/notifications.php';</script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js?v=<?php echo time(); ?>"></script>
<script>
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