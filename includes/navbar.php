<?php
$user_id_nav = $_SESSION['user_id'];

// Auto-read notifications if the notification dropdown is opened via JS, but for now we just show
$notif_query = $conn->query("SELECT * FROM notifications WHERE user_id='$user_id_nav' ORDER BY created_at DESC LIMIT 5");
$unread_q = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE user_id='$user_id_nav' AND is_read=0");

$unread_count = 0;
if ($unread_q && $unread_q->num_rows > 0) {
    $row = $unread_q->fetch_assoc();
    $unread_count = $row['unread'] ?? 0;
}
?>
<header class="top-navbar">
    <div class="nav-left">
        <button class="menu-toggle"><i class="fa-solid fa-bars"></i></button>
        <span class="page-title">
            <?php echo isset($page_title) ? $page_title : "Dashboard"; ?>
        </span>
    </div>
    <div class="nav-right" style="display:flex; align-items:center; gap:25px;">
        <!-- Theme Toggle -->
        <i class="fa-solid fa-moon theme-toggle-btn" style="font-size:20px; cursor:pointer; color:#94a3b8; transition:color 0.3s;" onclick="toggleTheme()" title="Toggle Dark/Light Theme"></i>

        <div class="notification dropdown">
            <i class="fa-regular fa-bell" onclick="document.getElementById('notif-menu').classList.toggle('show');" style="font-size:22px; transition:color 0.3s; color:#94a3b8;" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='#94a3b8'"></i>
            <?php if($unread_count > 0): ?>
                <span class="badge" style="background:#ef4444; color:#fff; border-radius:50%; padding:3px 7px; font-size:11px; top:-8px; right:-10px; border:2px solid #1e293b;"><?php echo $unread_count; ?></span>
            <?php endif; ?>
            
            <div id="notif-menu" class="dropdown-content" style="position:absolute; right:0; top:45px; background:var(--bg-card); border:1px solid var(--border-color); min-width:320px; border-radius:12px; z-index:1000; box-shadow: var(--shadow-md);">
                <div style="padding:15px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; background: var(--table-hover); border-radius: 12px 12px 0 0;">
                    <b style="color:var(--text-heading);">Notifications</b>
                    <a href="<?php echo $baseUrl; ?>auth/mark_read.php" style="color:#38bdf8; font-size:12px; text-decoration:none;">Mark all read</a>
                </div>
                <div style="max-height: 300px; overflow-y:auto; padding:5px;">
                    <?php if($notif_query && $notif_query->num_rows > 0): ?>
                        <?php while($notif = $notif_query->fetch_assoc()): ?>
                            <div style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05); <?php echo ($notif['is_read'] == 0) ? 'background:rgba(56,189,248,0.05);' : ''; ?>">
                                <a href="<?php echo htmlspecialchars($notif['link'] ? $notif['link'] : '#'); ?>" style="text-decoration:none; color:var(--text-main); font-size:13px; display:block;">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </a>
                                <span style="font-size:10px; color:#64748b;"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="padding:15px; text-align:center; color:#64748b; font-size:13px;">No new notifications.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Header User Profile -->
        <div style="display:flex; align-items:center; gap:12px; border-left:1px solid rgba(255,255,255,0.1); padding-left:25px;">
            <div style="text-align:right;">
                <h4 style="font-size:14px; color:var(--text-heading); font-weight:600; margin:0; line-height:1.2;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h4>
                <span style="font-size:11px; color:#38bdf8; text-transform:capitalize;"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></span>
            </div>
            <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #38bdf8, #818cf8); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; font-size:16px; margin-right: 10px;">
                <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
            </div>
            
            <!-- Logout Button -->
            <a href="<?php echo $baseUrl; ?>auth/logout.php" title="Logout" style="display:flex; align-items:center; justify-content:center; width:35px; height:35px; border-radius:8px; background:rgba(239, 68, 68, 0.1); color:#ef4444; text-decoration:none; transition:0.3s;" onmouseover="this.style.background='#ef4444'; this.style.color='#fff';" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>
    </div>
</header>
<style>
.dropdown-content { display: none; }
.dropdown-content.show { display: block !important; }
.badge { position:absolute; top:-5px; right:-5px; background:#ef4444; color:white; border-radius:50%; padding:2px 6px; font-size:10px; }
.notification { position:relative; cursor:pointer; }
</style>
<script>
    // Theme setup based on localStorage
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.querySelector('.theme-toggle-btn').classList.replace('fa-moon', 'fa-sun');
        } else {
            document.documentElement.removeAttribute('data-theme');
            document.querySelector('.theme-toggle-btn').classList.replace('fa-sun', 'fa-moon');
        }
    }

    // Check on load
    const currentTheme = localStorage.getItem('theme') || 'light';
    applyTheme(currentTheme);

    // Toggle button function
    function toggleTheme() {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'dark') {
            localStorage.setItem('theme', 'light');
            applyTheme('light');
        } else {
            localStorage.setItem('theme', 'dark');
            applyTheme('dark');
        }
    }
</script>
