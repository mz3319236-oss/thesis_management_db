<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$dept_filter = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;

$join_users = " JOIN users u ON t.student_id = u.id ";
$where_clause = "";
if ($dept_filter > 0) {
    $where_clause = " AND u.department_id = $dept_filter ";
}

// Status Distribution for the selected scope
$status_stats = $conn->query("SELECT t.status, COUNT(t.id) as cnt 
                              FROM theses t 
                              $join_users 
                              WHERE 1=1 $where_clause 
                              GROUP BY t.status");

// Recent Activity Log
$recent_activity = $conn->query("SELECT t.title, u.full_name as student_name, t.status, t.updated_at 
                                FROM theses t 
                                $join_users 
                                WHERE 1=1 $where_clause 
                                ORDER BY t.updated_at DESC LIMIT 10");

// Student Completion Rates
$total_th = $conn->query("SELECT COUNT(t.id) as total FROM theses t $join_users WHERE 1=1 $where_clause")->fetch_assoc()['total'];
$approved_th = $conn->query("SELECT COUNT(t.id) as approved FROM theses t $join_users WHERE t.status='approved' $where_clause")->fetch_assoc()['approved'];
$completion_rate = ($total_th > 0) ? round(($approved_th / $total_th) * 100, 1) : 0;

$dept_name = "Overall System Report";
if ($dept_filter > 0) {
    $dname_q = $conn->query("SELECT name FROM departments WHERE id = $dept_filter");
    if($dname_q->num_rows > 0) {
        $dept_name = "Report: " . $dname_q->fetch_assoc()['name'];
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<style>
.progress-bar-container { background: rgba(56, 189, 248, 0.1); border-radius: 10px; height: 10px; margin-top: 10px; overflow: hidden; }
.progress-bar { background: linear-gradient(to right, #38bdf8, #818cf8); height: 100%; transition: width 0.5s ease; }
.activity-item { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; color: #94a3b8; }
.activity-item b { color: var(--text-heading); }
@media print {
    body { background: white !important; color: black !important; }
    .sidebar, .top-navbar, .no-print { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; flex: none !important; }
    .content-area { padding: 0 !important; }
    .data-table-card { border: 1px solid #ccc !important; box-shadow: none !important; break-inside: avoid; }
    * { color: black !important; text-shadow: none !important; }
    .progress-bar-container { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .progress-bar { background: #38bdf8 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;" class="print-header">
            <h2 style="color: var(--text-heading); margin: 0;"><?php echo htmlspecialchars($dept_name); ?></h2>
            <div style="display: flex; gap: 15px;" class="no-print">
                <form method="GET" action="" style="display: flex; align-items: center; gap: 10px;">
                    <select name="dept_id" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); font-size: 14px;" onchange="this.form.submit()">
                        <option value="0">All Departments</option>
                        <?php while($d = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($dept_filter == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
                <button onclick="window.print()" class="btn-primary" style="padding: 10px 20px;"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px;">
            <!-- Status Distribution -->
            <div class="data-table-card">
                <div class="card-header"><h3>Status Distribution</h3></div>
                <div style="padding: 10px 0;">
                    <?php while($row = $status_stats->fetch_assoc()): ?>
                        <?php $percent = ($total_th > 0) ? ($row['cnt'] / $total_th) * 100 : 0; ?>
                        <div style="margin-bottom: 20px;">
                            <div style="display:flex; justify-content:space-between; font-size:14px; color:var(--text-main); text-transform: capitalize;">
                                <span><?php echo str_replace('_', ' ', $row['status']); ?></span>
                                <span><?php echo $row['cnt']; ?> (<?php echo round($percent, 1); ?>%)</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php if($status_stats->num_rows == 0): ?>
                        <p style="text-align:center; padding: 20px; color: #94a3b8;">No data available for this department.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Global Stats & Completion Row -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                 <div class="data-table-card">
                    <div class="card-header"><h3>Completion Rate</h3></div>
                    <div style="text-align: center; padding: 20px 0;">
                        <h1 style="font-size: 48px; color: #38bdf8;"><?php echo $completion_rate; ?>%</h1>
                        <p style="color: #94a3b8; margin-top: 5px;">Of all proposals have been officially approved.</p>
                        <div class="progress-bar-container" style="height: 15px; margin-top: 25px;">
                            <div class="progress-bar" style="width: <?php echo $completion_rate; ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="data-table-card">
                    <div class="card-header"><h3>Recent Activity Feed</h3></div>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <?php if($recent_activity->num_rows > 0): ?>
                            <?php while($act = $recent_activity->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <b><?php echo htmlspecialchars($act['student_name']); ?>'s</b> thesis status changed to 
                                    <span style="color: #38bdf8; text-transform: capitalize;"><?php echo str_replace('_', ' ', $act['status']); ?></span>
                                    <br><small><?php echo date('M d, H:i', strtotime($act['updated_at'])); ?></small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="padding: 10px; color:#64748b; text-align: center;">No recent activity.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
