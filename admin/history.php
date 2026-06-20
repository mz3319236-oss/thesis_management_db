<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';



// Fetch Logs
$logs = $conn->query("SELECT l.*, u.full_name, u.role, u.email 
                     FROM activity_log l 
                     JOIN users u ON l.user_id = u.id 
                     ORDER BY l.created_at DESC LIMIT 100");

?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">System Audit & Activity Logs</h2>

        <div class="data-table-card">
            <div class="card-header">
                <h3>Global Action History</h3>
                <span style="font-size:12px; color:#64748b;">Showing last 100 activities</span>
            </div>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($logs->num_rows > 0): ?>
                            <?php while($row = $logs->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size:12px; white-space:nowrap;">
                                    <?php echo date('d M, h:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td>
                                    <b style="color:var(--text-heading); font-size:13px;"><?php echo htmlspecialchars($row['full_name']); ?></b>
                                    <div style="font-size:11px; color:#64748b;"><?php echo ucfirst($row['role']); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge" style="background:rgba(56, 189, 248, 0.1); color:#38bdf8; border:1px solid rgba(56, 189, 248, 0.2);">
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td style="font-size:13px; color:var(--text-main);">
                                    <?php echo htmlspecialchars($row['details']); ?>
                                </td>
                                <td style="font-size:11px; color:#64748b; font-family:monospace;">
                                    <?php echo $row['ip_address']; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No activity logs found yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
