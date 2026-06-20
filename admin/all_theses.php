<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

$filter_dept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

$is_supervisor = ($_SESSION['role'] == 'supervisor');
if ($is_supervisor) {
    $user_id_session = $_SESSION['user_id'];
    $dept_res = $conn->query("SELECT department_id FROM users WHERE id='$user_id_session'");
    if ($dept_res && $dept_res->num_rows > 0) {
        $filter_dept = $dept_res->fetch_assoc()['department_id']; // Force filter to supervisor's dept
    }
}

$whereClause = "";
if ($filter_dept > 0) {
    $whereClause = " WHERE u.department_id = $filter_dept ";
}

$sql = "SELECT t.*, u.full_name as student_name, s.full_name as supervisor_name, d.name as department_name
        FROM theses t 
        JOIN users u ON t.student_id = u.id 
        JOIN users s ON t.supervisor_id = s.id 
        LEFT JOIN departments d ON u.department_id = d.id
        $whereClause
        ORDER BY d.name ASC, t.created_at DESC";
$result = $conn->query($sql);

$dept_query = $conn->query("SELECT * FROM departments ORDER BY name ASC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--text-heading); margin: 0;">Complete Repository</h2>
            
            <?php if (!$is_supervisor): ?>
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <select name="department_id" class="form-control" style="width: 250px; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none;" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php while($d = $dept_query->fetch_assoc()): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $filter_dept == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <div class="data-table-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Department</th>
                            <th>Student</th>
                            <th>Supervisor</th>
                            <th>Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size:12px; color: #64748b;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td><span style="background: rgba(56, 189, 248, 0.1); color: #0284c7; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;"><?php echo htmlspecialchars($row['department_name'] ?? 'Not Assigned'); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)) . (strlen($row['title']) > 40 ? '...' : ''); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">No theses found for the selected criteria.</td></tr>
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
