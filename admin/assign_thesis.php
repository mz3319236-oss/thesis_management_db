<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';



$success = ''; $error = '';

// Handle Re-assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_now'])) {
    $thesis_id = intval($_POST['thesis_id']);
    $new_supervisor_id = intval($_POST['supervisor_id']);
    
    $update = $conn->query("UPDATE theses SET supervisor_id = '$new_supervisor_id' WHERE id = '$thesis_id'");
    if ($update) {
        $success = "Thesis successfully reassigned to the new supervisor!";
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

$whereSup = "";
$whereTheses = "";
if ($_SESSION['role'] == 'supervisor') {
    $user_id_session = $_SESSION['user_id'];
    $dept_res = $conn->query("SELECT department_id FROM users WHERE id='$user_id_session'");
    if ($dept_res && $dept_res->num_rows > 0) {
        $supervisor_dept = $dept_res->fetch_assoc()['department_id'];
        $whereSup = " AND department_id = '$supervisor_dept' ";
        $whereTheses = " WHERE u_s.department_id = '$supervisor_dept' ";
    }
}

// Fetch all supervisors
$supervisors = $conn->query("SELECT id, full_name FROM users WHERE role='supervisor' $whereSup");
$sup_list = [];
while($s = $supervisors->fetch_assoc()) $sup_list[] = $s;

// Fetch all theses with current supervisor info
$theses = $conn->query("SELECT t.id, t.title, u_s.full_name as student_name, u_sup.full_name as supervisor_name, t.status 
                        FROM theses t 
                        JOIN users u_s ON t.student_id = u_s.id 
                        LEFT JOIN users u_sup ON t.supervisor_id = u_sup.id 
                        $whereTheses
                        ORDER BY t.created_at DESC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Assign / Transfer Thesis</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="data-table-card">
            <div class="card-header">
                <h3>Management Console</h3>
            </div>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Thesis Details</th>
                            <th>Student</th>
                            <th>Current Supervisor</th>
                            <th>New Assignment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($theses->num_rows > 0): ?>
                            <?php while($row = $theses->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <b style="color:var(--text-heading);"><?php echo htmlspecialchars(substr($row['title'], 0, 40)); ?>...</b><br>
                                    <span class="status-badge" style="font-size:10px; padding:2px 6px;"><?php echo $row['status']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><span style="color:#94a3b8;"><?php echo htmlspecialchars($row['supervisor_name'] ?? 'Not Assigned'); ?></span></td>
                                <td>
                                    <form action="" method="POST" style="display:flex; gap:10px;">
                                        <input type="hidden" name="thesis_id" value="<?php echo $row['id']; ?>">
                                        <select name="supervisor_id" required style="background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); border-radius:5px; padding:5px; font-size:12px;">
                                            <option value="">Select Supervisor</option>
                                            <?php foreach($sup_list as $sl): ?>
                                                <option value="<?php echo $sl['id']; ?>"><?php echo htmlspecialchars($sl['full_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_now" class="btn-primary" style="padding:5px 10px; font-size:11px;">Assign</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No thesis records found.</td></tr>
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
