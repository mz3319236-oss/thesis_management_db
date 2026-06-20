<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$deadlines = $conn->query("SELECT * FROM deadlines ORDER BY deadline_date ASC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="text-align:center; padding: 20px 0;">
            <i class="fa-regular fa-calendar-alt" style="font-size: 50px; color:#38bdf8; margin-bottom:15px;"></i>
            <h2 style="color: var(--text-heading);">Important Dates & Deadlines</h2>
            <p style="color:#94a3b8;">Keep track of the official academic timeline below.</p>
        </div>

        <div style="max-width: 800px; margin: 0 auto; margin-top:30px;">
            <?php if($deadlines->num_rows > 0): ?>
                <div style="position:relative; border-left: 2px solid rgba(56,189,248,0.2) ; margin-left: 20px; padding-left: 20px;">
                    <?php while($d = $deadlines->fetch_assoc()): 
                        $date_time = strtotime($d['deadline_date']);
                        $is_past = ($date_time < time());
                        $icon_color = $is_past ? '#ef4444' : '#22c55e';
                    ?>
                    <div style="margin-bottom:30px; position:relative;">
                        <div style="position:absolute; left:-32px; top:0; width:20px; height:20px; background:var(--input-bg); border:3px solid <?php echo $icon_color; ?>; border-radius:50%;"></div>
                        <div class="data-table-card" style="margin:0; padding:20px; border-left: 4px solid <?php echo $icon_color; ?>;">
                            <h3 style="color:var(--text-heading); margin-bottom:5px;"><?php echo htmlspecialchars($d['title']); ?></h3>
                            <h4 style="color:<?php echo $icon_color; ?>; margin-bottom:15px;"><i class="fa-regular fa-clock"></i> <?php echo date('l, d F Y', $date_time); ?></h4>
                            <p style="color:var(--text-main); font-size:14px; line-height:1.5;"><?php echo nl2br(htmlspecialchars($d['description'])); ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="data-table-card" style="text-align:center; padding:50px;">
                    <h3 style="color:var(--text-heading);">No Deadlines Set</h3>
                    <p style="color:#64748b;">The administration hasn't announced any official deadlines yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
