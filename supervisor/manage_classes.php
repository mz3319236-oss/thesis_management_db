<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Handle class creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_class'])) {
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $session_name = $conn->real_escape_string($_POST['session_name']);
    $section_name = $conn->real_escape_string($_POST['section_name']);
    
    $sql = "INSERT INTO classes (supervisor_id, class_name, session_name, section_name) VALUES ('$supervisor_id', '$class_name', '$session_name', '$section_name')";
    if ($conn->query($sql) === TRUE) {
        $success_msg = "Class successfully created.";
    } else {
        $error_msg = "Database Error: " . $conn->error;
    }
}

// Handle class editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_class'])) {
    $edit_id = intval($_POST['class_id']);
    $new_class_name = $conn->real_escape_string($_POST['class_name']);
    $new_session_name = $conn->real_escape_string($_POST['session_name']);
    $new_section_name = $conn->real_escape_string($_POST['section_name']);
    
    $check_q = $conn->query("SELECT id FROM classes WHERE id='$edit_id' AND supervisor_id='$supervisor_id'");
    if ($check_q->num_rows > 0) {
        $conn->query("UPDATE classes SET class_name='$new_class_name', session_name='$new_session_name', section_name='$new_section_name' WHERE id='$edit_id'");
        $success_msg = "Class details updated successfully.";
    } else {
        $error_msg = "Error: Unauthorized action.";
    }
}

// Fetch supervisor's classes
$classes_q = $conn->query("SELECT * FROM classes WHERE supervisor_id='$supervisor_id' ORDER BY created_at DESC");

?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Manage Classes & Sessions</h2>

        <?php if(isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if(isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:25px;">
            <!-- Create Class Form -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-plus-circle"></i> Create New Class</h3>
                </div>
                <form action="" method="POST" class="dashboard-form" style="padding:20px; border:none; background:transparent;">
                    <div class="form-group">
                        <label>Program / Class Name</label>
                        <input type="text" name="class_name" required placeholder="e.g. BS Computer Science">
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <input type="text" name="session_name" required placeholder="e.g. Fall 2023 - 2027">
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section_name" required placeholder="e.g. Section A">
                    </div>
                    <button type="submit" name="create_class" class="btn-primary" style="width:100%;">Create Class</button>
                </form>
            </div>

            <!-- List of Classes -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-chalkboard-user"></i> My Assigned Classes</h3>
                </div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Class Details</th>
                                <th>Session & Section</th>
                                <th>Total Students</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($classes_q->num_rows > 0): ?>
                                <?php while($row = $classes_q->fetch_assoc()): 
                                    $c_id = $row['id'];
                                    $s_count = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE class_id='$c_id'")->fetch_assoc()['cnt'];
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                                    <td>
                                        <span style="font-size:12px; color:var(--text-heading); font-weight:500;">
                                            Session: <span style="color:#38bdf8;"><?php echo htmlspecialchars($row['session_name']); ?></span><br>
                                            Section: <span style="color:#38bdf8;"><?php echo htmlspecialchars($row['section_name']); ?></span>
                                        </span>
                                    </td>
                                    <td><span class="status-badge" style="background:rgba(34,197,94,0.1); color:#22c55e;"><?php echo $s_count; ?> Students</span></td>
                                    <td>
                                        <div style="display:flex; gap:10px;">
                                            <a href="view_class.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding:6px 12px; font-size:12px; text-decoration:none; white-space:nowrap;"><i class="fa-solid fa-folder-open"></i> Manage</a>
                                            <button onclick="document.getElementById('edit-class-modal-<?php echo $row['id']; ?>').style.display='block'" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; padding:6px 12px; font-size:12px; white-space:nowrap;"><i class="fa-solid fa-pen"></i> Edit</button>
                                        </div>

                                        <!-- Edit Class Modal -->
                                        <div id="edit-class-modal-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                                            <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:20px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3); text-align:left;">
                                                <h3 style="color:var(--text-heading); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Class Details</h3>
                                                <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                                                    <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                                                    <div class="form-group" style="margin-bottom:15px;">
                                                        <label>Program / Class Name</label>
                                                        <input type="text" name="class_name" value="<?php echo htmlspecialchars($row['class_name']); ?>" required>
                                                    </div>
                                                    <div class="form-group" style="margin-bottom:15px;">
                                                        <label>Session</label>
                                                        <input type="text" name="session_name" value="<?php echo htmlspecialchars($row['session_name']); ?>" required>
                                                    </div>
                                                    <div class="form-group" style="margin-bottom:15px;">
                                                        <label>Section</label>
                                                        <input type="text" name="section_name" value="<?php echo htmlspecialchars($row['section_name']); ?>" required>
                                                    </div>
                                                    <div style="text-align:right;">
                                                        <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-class-modal-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                        <button type="submit" name="edit_class" class="btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; padding:20px; color:#64748b;">You have not created any classes yet.</td></tr>
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
