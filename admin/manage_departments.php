<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';



$error = ''; $success = '';

// Handle Add Department
if (isset($_POST['add_dept'])) {
    $dept_name = $conn->real_escape_string($_POST['dept_name']);
    if (empty($dept_name)) {
        $error = "Department name is required.";
    } else {
        $conn->query("INSERT INTO departments (name) VALUES ('$dept_name')");
        $success = "Department added successfully!";
    }
}

// Handle Delete Department
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    // Check if department is being used by users
    $check = $conn->query("SELECT id FROM users WHERE department_id='$del_id'");
    if ($check->num_rows > 0) {
        $error = "Cannot delete department. It is currently assigned to users.";
    } else {
        $conn->query("DELETE FROM departments WHERE id='$del_id'");
        $success = "Department deleted successfully!";
    }
}

$departments = $conn->query("SELECT d.*, (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count FROM departments d");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Manage Departments</h2>

        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
            <!-- Add Department Form -->
            <div class="data-table-card">
                <div class="card-header"><h3>Add New</h3></div>
                <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                    <div class="form-group">
                        <label>Department Name</label>
                        <input type="text" name="dept_name" required placeholder="e.g. Mechanical Engineering">
                    </div>
                    <button type="submit" name="add_dept" class="btn-primary" style="width:100%;">Create Department</button>
                </form>
            </div>

            <!-- Department List -->
            <div class="data-table-card">
                <div class="card-header"><h3>Existing Departments</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Department Name</th>
                                <th>Users</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($departments->num_rows > 0): ?>
                                <?php $i=1; while($row = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <a href="view_department.php?id=<?php echo $row['id']; ?>" style="color: #38bdf8; text-decoration: none; font-weight: bold;">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </a>
                                    </td>
                                    <td><span class="status-badge" style="background:rgba(56,189,248,0.1); color:#38bdf8;"><?php echo $row['user_count']; ?> Users</span></td>
                                    <td>
                                        <a href="manage_departments.php?delete_id=<?php echo $row['id']; ?>" class="btn-primary" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); padding:5px 10px; font-size:12px; text-decoration:none;" onclick="return confirm('Delete this department?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;">No departments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
