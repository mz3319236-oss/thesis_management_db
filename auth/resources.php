<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$role = $_SESSION['role'];
$resources = $conn->query("SELECT * FROM resources WHERE user_role = 'all' OR user_role = '$role' ORDER BY created_at DESC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Guidelines & Resources</h2>
        
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            <?php if($resources->num_rows > 0): ?>
                <?php while($r = $resources->fetch_assoc()): ?>
                <div class="data-table-card" style="padding:20px; display:flex; flex-direction:column; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <i class="fa-solid fa-file-pdf" style="font-size:40px; color:#38bdf8; margin-bottom:15px;"></i>
                        <h3 style="color:var(--text-heading); margin-bottom:10px;"><?php echo htmlspecialchars($r['title']); ?></h3>
                        <p style="color:#64748b; font-size:12px; margin-bottom:20px;">Added on: <?php echo date('d M, Y', strtotime($r['created_at'])); ?></p>
                    </div>
                    <a href="../uploads/resources/<?php echo $r['file_path']; ?>" target="_blank" class="btn-primary" style="width:100%; text-align:center; text-decoration:none;">
                        <i class="fa-solid fa-download"></i> Download Resource
                    </a>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="data-table-card" style="grid-column: 1 / -1; text-align:center; padding:50px;">
                    <i class="fa-solid fa-box-open" style="font-size:48px; color:#334155; margin-bottom:15px;"></i>
                    <h3 style="color:var(--text-heading);">No Resources Available</h3>
                    <p style="color:#64748b;">The administration hasn't uploaded any guidelines yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
