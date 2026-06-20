<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';



$success = ''; $error = '';

// Handle Add Examiner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_examiner'])) {
    $name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $org = $conn->real_escape_string($_POST['organization']);
    $spec = $conn->real_escape_string($_POST['specialization']);
    $contact = $conn->real_escape_string($_POST['contact_no']);
    $dept_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;

    $check = $conn->query("SELECT id FROM external_examiners WHERE email='$email'");
    if($check->num_rows > 0) {
        $error = "Examiner with this email already exists!";
    } else {
        $sql = "INSERT INTO external_examiners (full_name, email, organization, specialization, contact_no, department_id) 
                VALUES ('$name', '$email', '$org', '$spec', '$contact', " . ($dept_id ? "'$dept_id'" : "NULL") . ")";
        if($conn->query($sql)) {
            $success = "External examiner added successfully!";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM external_examiners WHERE id=$id");
    $success = "Examiner removed successfully.";
}

$examiners = $conn->query("SELECT e.*, d.name as dept_name FROM external_examiners e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.created_at DESC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">External Examiners Management</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:25px; align-items: start;">
            <!-- Add Examiner Form -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3>Register New Examiner</h3>
                </div>
                <form action="" method="POST" class="dashboard-form" style="border:none; padding:15px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required placeholder="Dr. John Doe">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="email@university.edu">
                    </div>
                    <div class="form-group">
                        <label>Organization / University</label>
                        <input type="text" name="organization" required placeholder="University of XYZ">
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="specialization" placeholder="e.g. Machine Learning">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                            <option value="">Select Department (Optional)</option>
                            <?php 
                            $dept_list = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                            while($dl = $dept_list->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $dl['id']; ?>"><?php echo htmlspecialchars($dl['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact No</label>
                        <input type="text" name="contact_no" placeholder="+92-xxx-xxxxxxx">
                    </div>
                    <button type="submit" name="add_examiner" class="btn-primary" style="width:100%; mt-10;">
                        <i class="fa-solid fa-plus-circle"></i> Register Examiner
                    </button>
                </form>
            </div>

            <!-- Examiners List -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3>Registered External Examiners</h3>
                </div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Name & specialization</th>
                                <th>Organization</th>
                                <th>Contact</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($examiners->num_rows > 0): ?>
                                <?php while($row = $examiners->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <b style="color:var(--text-heading);"><?php echo htmlspecialchars($row['full_name']); ?></b>
                                        <div style="font-size:11px; color:#94a3b8;"><?php echo htmlspecialchars($row['specialization']); ?></div>
                                        <div style="font-size:11px; color:#38bdf8; font-weight:bold;"><?php echo htmlspecialchars($row['dept_name'] ?? 'No Department'); ?></div>
                                    </td>
                                    <td style="font-size:13px;"><?php echo htmlspecialchars($row['organization']); ?></td>
                                    <td>
                                        <div style="font-size:12px; color:#38bdf8;"><?php echo htmlspecialchars($row['email']); ?></div>
                                        <div style="font-size:11px; color:#94a3b8;"><?php echo htmlspecialchars($row['contact_no']); ?></div>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Remove this examiner?')" style="color:#ef4444; font-size:18px;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">No external examiners registered yet.</td></tr>
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
