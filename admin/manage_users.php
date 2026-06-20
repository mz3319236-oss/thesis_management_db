<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check permissions for the current page
checkPagePermission($conn);

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    if ($del_id !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id='$del_id'");
    }
    header("Location: manage_users.php");
    exit();
}

if (isset($_GET['approve_id'])) {
    $approve_id = intval($_GET['approve_id']);
    $conn->query("UPDATE users SET is_approved = 1 WHERE id='$approve_id'");
    header("Location: manage_users.php");
    exit();
}

// Handle Password Reset by Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $reset_uid = intval($_POST['reset_user_id']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    if (strlen($new_pass) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE id='$reset_uid'");
        $success_msg = "Password has been reset successfully.";
    }
}

// Handle Edit Supervisor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_supervisor'])) {
    $edit_id = intval($_POST['supervisor_id']);
    $new_name = $conn->real_escape_string($_POST['full_name']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_uid = $conn->real_escape_string($_POST['university_id']);
    $new_dept = intval($_POST['department_id']);
    
    $conn->query("UPDATE users SET full_name='$new_name', email='$new_email', university_id='$new_uid', department_id='$new_dept' WHERE id='$edit_id' AND role='supervisor'");
    $success_msg = "Supervisor details updated successfully.";
}

// Handle student editing by admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $edit_id = intval($_POST['student_id']);
    $new_name = $conn->real_escape_string($_POST['full_name']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_uid = $conn->real_escape_string($_POST['university_id']);
    
    $conn->query("UPDATE users SET full_name='$new_name', email='$new_email', university_id='$new_uid' WHERE id='$edit_id' AND role='student'");
    $success_msg = "Student details updated successfully.";
}

// Handle class editing by admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_class_admin'])) {
    $edit_c_id = intval($_POST['class_id']);
    $new_class_name = $conn->real_escape_string($_POST['class_name']);
    $new_session_name = $conn->real_escape_string($_POST['session_name']);
    $new_section_name = $conn->real_escape_string($_POST['section_name']);
    
    $conn->query("UPDATE classes SET class_name='$new_class_name', session_name='$new_session_name', section_name='$new_section_name' WHERE id='$edit_c_id'");
    $success_msg = "Class details updated successfully.";
}

// Handle class creation by admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_class_admin'])) {
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $session_name = $conn->real_escape_string($_POST['session_name']);
    $section_name = $conn->real_escape_string($_POST['section_name']);
    $dept_id_post = intval($_POST['department_id']);
    
    $class_sup_id = 0;
    
    // Check if new supervisor details are provided
    if (!empty($_POST['new_sup_name']) && !empty($_POST['new_sup_email']) && !empty($_POST['new_sup_uid'])) {
        $sup_name = $conn->real_escape_string($_POST['new_sup_name']);
        $sup_email = $conn->real_escape_string($_POST['new_sup_email']);
        $sup_uid = $conn->real_escape_string($_POST['new_sup_uid']);
        $password = password_hash('supervisor123', PASSWORD_DEFAULT);
        
        $check_email = $conn->query("SELECT id FROM users WHERE email='$sup_email' OR university_id='$sup_uid'");
        if ($check_email->num_rows > 0) {
            $error_msg = "Error: A supervisor with this Email or University ID already exists.";
        } else {
            $conn->query("INSERT INTO users (full_name, email, university_id, password, role, department_id, is_approved) VALUES ('$sup_name', '$sup_email', '$sup_uid', '$password', 'supervisor', '$dept_id_post', 1)");
            $class_sup_id = $conn->insert_id;
        }
    } else if (isset($_POST['supervisor_id']) && intval($_POST['supervisor_id']) > 0) {
        $class_sup_id = intval($_POST['supervisor_id']);
    } else {
        $error_msg = "Error: Please select an existing supervisor or register a new one.";
    }

    if ($class_sup_id > 0) {
        $sql = "INSERT INTO classes (supervisor_id, class_name, session_name, section_name) VALUES ('$class_sup_id', '$class_name', '$session_name', '$section_name')";
        if ($conn->query($sql) === TRUE) {
            $success_msg = "Class successfully created.";
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

$supervisor_dept_id = 0;
if ($_SESSION['role'] == 'supervisor') {
    $user_id_session = $_SESSION['user_id'];
    $dept_res = $conn->query("SELECT department_id FROM users WHERE id='$user_id_session'");
    if ($dept_res && $dept_res->num_rows > 0) {
        $supervisor_dept_id = $dept_res->fetch_assoc()['department_id'];
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Manage Users</h2>

        <?php if(isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>

        <?php 
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'supervisors';
        ?>

        <!-- SEARCH BAR -->
        <div style="margin: 30px 0;">
            <form method="GET" action="" style="display: flex; gap: 10px; max-width: 600px;">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <input type="text" name="search" placeholder="Search in this category..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); outline: none; transition: 0.3s;" onfocus="this.style.borderColor='#38bdf8'">
                <button type="submit" class="btn-primary" style="padding: 0 25px;"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <?php if($search): ?>
                    <a href="manage_users.php?tab=<?php echo $active_tab; ?>" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; text-decoration: none; padding: 0 15px;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="tabs-container" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; overflow-x: auto; white-space: nowrap;">
            <?php 
            $depts_sql = "SELECT * FROM departments";
            if($_SESSION['role'] == 'supervisor') $depts_sql .= " WHERE id = $supervisor_dept_id";
            $depts_sql .= " ORDER BY name ASC";
            $depts_tabs = $conn->query($depts_sql);
            
            // Auto-select first tab if active_tab is 'supervisors' (which is now deleted)
            if($active_tab == 'supervisors' && $depts_tabs && $depts_tabs->num_rows > 0) {
                $first_dept = $depts_tabs->fetch_assoc();
                $active_tab = 'dept_' . $first_dept['id'];
                $depts_tabs->data_seek(0);
            }
            
            while($d = $depts_tabs->fetch_assoc()): ?>
                <a href="?tab=dept_<?php echo $d['id']; ?>" class="tab-item <?php echo $active_tab == 'dept_'.$d['id'] ? 'active' : ''; ?>" style="padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 500; transition: 0.3s; display: flex; align-items: center; gap: 8px; color: <?php echo $active_tab == 'dept_'.$d['id'] ? '#fff' : '#64748b'; ?>; background: <?php echo $active_tab == 'dept_'.$d['id'] ? '#38bdf8' : 'transparent'; ?>;">
                    <i class="fa-solid fa-building-columns"></i> <?php echo htmlspecialchars($d['name']); ?>
                </a>
            <?php endwhile; ?>

            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="?tab=others" class="tab-item <?php echo $active_tab == 'others' ? 'active' : ''; ?>" style="padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 500; transition: 0.3s; display: flex; align-items: center; gap: 8px; color: <?php echo $active_tab == 'others' ? '#fff' : '#64748b'; ?>; background: <?php echo $active_tab == 'others' ? '#38bdf8' : 'transparent'; ?>;">
                    <i class="fa-solid fa-circle-question"></i> Others
                </a>
            <?php endif; ?>
        </div>

        <style>
            .tab-item:hover { background: rgba(56, 189, 248, 0.1); color: #38bdf8 !important; }
            .tab-item.active:hover { background: #38bdf8; color: #fff !important; }
        </style>

        <!-- CONTENT AREA -->
        <?php if(strpos($active_tab, 'dept_') === 0): ?>
            <!-- DEPARTMENT DETAILS -->
            <?php 
            $d_id = intval(str_replace('dept_', '', $active_tab));
            $d_info = $conn->query("SELECT name FROM departments WHERE id = $d_id")->fetch_assoc();
            
            // Handle Add Supervisor directly in this tab
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supervisor_tab'])) {
                $full_name = $conn->real_escape_string($_POST['full_name']);
                $email = $conn->real_escape_string($_POST['email']);
                $university_id = $conn->real_escape_string($_POST['university_id']);
                $password = password_hash('supervisor123', PASSWORD_DEFAULT);
                
                $check_email = $conn->query("SELECT id FROM users WHERE email='$email'");
                if($check_email->num_rows > 0) {
                    echo "<div class='alert alert-danger'>Error: A user with this Email already exists.</div>";
                } else {
                    $sql = "INSERT INTO users (full_name, email, university_id, password, role, department_id, is_approved) 
                            VALUES ('$full_name', '$email', '$university_id', '$password', 'supervisor', '$d_id', 1)";
                    if($conn->query($sql) === TRUE) {
                        echo "<div class='alert alert-success'>Supervisor successfully added to this department! Default Password: supervisor123</div>";
                    }
                }
            }

            $st_sql = "SELECT * FROM users WHERE role='student' AND department_id = $d_id";
            if($search) $st_sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR university_id LIKE '%$search%')";
            $st_sql .= " ORDER BY full_name ASC";
            $st_res = $conn->query($st_sql);

            $sup_sql = "SELECT * FROM users WHERE role='supervisor' AND department_id = $d_id ORDER BY full_name ASC";
            $sup_res = $conn->query($sup_sql);

            $classes_sql = "SELECT c.*, u.full_name as supervisor_name FROM classes c JOIN users u ON c.supervisor_id = u.id WHERE u.department_id = $d_id";
            $classes_res = $conn->query($classes_sql);
            ?>

            <div style="display:flex; justify-content:flex-end; margin-bottom: 20px; gap: 10px;">
                <button onclick="document.getElementById('add-class-modal-tab').style.display='block'" class="btn-primary" style="background:#22c55e; border-color:#22c55e;"><i class="fa-solid fa-plus"></i> Create Class</button>
                <button onclick="document.getElementById('add-supervisor-modal-tab').style.display='block'" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Add Supervisor to <?php echo htmlspecialchars($d_info['name']); ?></button>
            </div>

            <!-- Create Class Modal -->
            <div id="add-class-modal-tab" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto;">
                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:450px; margin: 50px auto; box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                        <h3 style="color:var(--text-heading); margin:0;">Create New Class</h3>
                        <button onclick="document.getElementById('add-class-modal-tab').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="department_id" value="<?php echo $d_id; ?>">
                        
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Program / Class Name</label>
                            <input type="text" name="class_name" required placeholder="e.g. BS Computer Science">
                        </div>
                        <div style="display:flex; gap:10px; margin-bottom:15px;">
                            <div class="form-group" style="flex:1;">
                                <label>Session</label>
                                <input type="text" name="session_name" required placeholder="e.g. Fall 2023 - 2027">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Section</label>
                                <input type="text" name="section_name" required placeholder="e.g. Section A">
                            </div>
                        </div>

                        <!-- Supervisor Selection Options -->
                        <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 20px;">
                            <h4 style="color:#38bdf8; margin-bottom:10px; font-size:14px;"><i class="fa-solid fa-link"></i> Option 1: Assign Existing Supervisor</h4>
                            <div class="form-group" style="margin-bottom:10px;">
                                <select name="supervisor_id" id="sup_dropdown_<?php echo $d_id; ?>" onchange="document.getElementById('new_sup_fields_<?php echo $d_id; ?>').style.opacity = this.value ? '0.5' : '1';">
                                    <option value="">-- Choose Existing Supervisor --</option>
                                    <?php 
                                    if($sup_res && $sup_res->num_rows > 0) {
                                        $sup_res->data_seek(0);
                                        while($s = $sup_res->fetch_assoc()) {
                                            echo "<option value='".$s['id']."'>".htmlspecialchars($s['full_name'])."</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No supervisors available in this department.</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div style="text-align:center; margin: 10px 0; color:#64748b; font-size:12px; font-weight:bold;">OR</div>
                            
                            <h4 style="color:#22c55e; margin-bottom:10px; font-size:14px;"><i class="fa-solid fa-user-plus"></i> Option 2: Register New Supervisor</h4>
                            <div id="new_sup_fields_<?php echo $d_id; ?>" style="transition:0.3s;">
                                <div class="form-group" style="margin-bottom:10px;">
                                    <input type="text" name="new_sup_name" placeholder="Full Name (e.g. Dr. Jane)" style="padding:8px;">
                                </div>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <input type="text" name="new_sup_uid" placeholder="University ID (e.g. FAC-001)" style="padding:8px;">
                                </div>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <input type="email" name="new_sup_email" placeholder="Email Address" style="padding:8px;">
                                </div>
                                <p style="font-size:11px; color:#94a3b8; margin:0;"><i class="fa-solid fa-info-circle"></i> Default password will be: <b>supervisor123</b></p>
                            </div>
                        </div>

                        <button type="submit" name="create_class_admin" class="btn-primary" style="width:100%;">Create Class</button>
                    </form>
                </div>
            </div>

            <!-- Add Supervisor Modal -->
            <div id="add-supervisor-modal-tab" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                        <h3 style="color:var(--text-heading); margin:0;">Add Supervisor</h3>
                        <button onclick="document.getElementById('add-supervisor-modal-tab').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                    </div>
                    <form action="" method="POST">
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Full Name</label>
                            <input type="text" name="full_name" required placeholder="e.g. Dr. Jane Smith">
                        </div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Faculty ID / Employee Code</label>
                            <input type="text" name="university_id" required placeholder="e.g. FAC-001">
                        </div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Email Address</label>
                            <input type="email" name="email" required placeholder="e.g. jane@faculty.edu">
                        </div>
                        <p style="font-size:12px; color:#94a3b8; margin-bottom:15px;"><i class="fa-solid fa-info-circle"></i> Default password: <b>supervisor123</b></p>
                        <button type="submit" name="add_supervisor_tab" class="btn-primary" style="width:100%;">Create Supervisor</button>
                    </form>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-bottom:30px;">
                <!-- Supervisors -->
                <div class="data-table-card">
                    <div class="card-header"><h3><i class="fa-solid fa-user-tie"></i> Supervisors</h3></div>
                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if($sup_res && $sup_res->num_rows > 0): ?>
                                    <?php 
                                    $sup_res->data_seek(0);
                                    while($row = $sup_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                <button onclick="document.getElementById('edit-sup-tab-<?php echo $row['id']; ?>').style.display='block'" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; padding:4px 8px; font-size:11px;">Edit</button>
                                                <button onclick="document.getElementById('reset-pass-<?php echo $row['id']; ?>').style.display='block'" class="btn-primary" style="background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.4); color:#fbbf24; padding:4px 8px; font-size:11px;"><i class="fa-solid fa-key"></i> Reset Pass</button>
                                                <a href="manage_users.php?delete_id=<?php echo $row['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn-primary" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); text-decoration:none; padding:4px 8px; font-size:11px;" onclick="return confirm('Delete supervisor?')">Delete</a>
                                            </div>

                                            <!-- Edit Supervisor Modal -->
                                            <div id="edit-sup-tab-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                                                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3); text-align:left;">
                                                    <h3 style="color:var(--text-heading); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Supervisor</h3>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="supervisor_id" value="<?php echo $row['id']; ?>">
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Full Name</label>
                                                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>University ID</label>
                                                            <input type="text" name="university_id" value="<?php echo htmlspecialchars($row['university_id']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Email Address</label>
                                                            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Department</label>
                                                            <select name="department_id" required>
                                                                <?php 
                                                                $d_opts = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                                                while($d = $d_opts->fetch_assoc()){
                                                                    $sel = ($d['id'] == $row['department_id']) ? 'selected' : '';
                                                                    echo "<option value='".$d['id']."' $sel>".htmlspecialchars($d['name'])."</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div style="text-align:right;">
                                                            <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-sup-tab-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                            <button type="submit" name="edit_supervisor" class="btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            <!-- Reset Password Modal (Supervisor) -->
                                            <div id="reset-pass-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000;">
                                                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:25px; width:90%; max-width:380px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 8px 30px rgba(0,0,0,0.4); text-align:left;">
                                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                                                        <h3 style="color:#fbbf24; margin:0;"><i class="fa-solid fa-key"></i> Reset Password</h3>
                                                        <button onclick="document.getElementById('reset-pass-<?php echo $row['id']; ?>').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                                                    </div>
                                                    <p style="font-size:13px; color:#94a3b8; margin-bottom:15px;">Setting new password for: <strong style="color:var(--text-heading);"><?php echo htmlspecialchars($row['full_name']); ?></strong></p>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="reset_user_id" value="<?php echo $row['id']; ?>">
                                                        <div class="form-group" style="margin-bottom:12px;">
                                                            <label>New Password</label>
                                                            <input type="password" name="new_password" required placeholder="Min 6 characters" style="padding:10px;">
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Confirm Password</label>
                                                            <input type="password" name="confirm_password" required placeholder="Re-enter password" style="padding:10px;">
                                                        </div>
                                                        <div style="text-align:right;">
                                                            <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:8px;" onclick="document.getElementById('reset-pass-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                            <button type="submit" name="reset_password" class="btn-primary" style="background:#fbbf24; color:#1e293b; border-color:#fbbf24;"><i class="fa-solid fa-rotate"></i> Reset</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align:center;">No supervisors found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Classes -->
                <div class="data-table-card">
                    <div class="card-header"><h3><i class="fa-solid fa-chalkboard"></i> Classes</h3></div>
                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead><tr><th>Class Name</th><th>Session</th><th>Supervisor</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if($classes_res && $classes_res->num_rows > 0): ?>
                                    <?php while($c = $classes_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($c['class_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($c['session_name'] . ' - ' . $c['section_name']); ?></td>
                                        <td><?php echo htmlspecialchars($c['supervisor_name']); ?></td>
                                        <td>
                                            <div style="display:flex; gap:10px;">
                                                <a href="../supervisor/view_class.php?id=<?php echo $c['id']; ?>" class="btn-primary" style="padding:4px 8px; font-size:11px; text-decoration:none;"><i class="fa-solid fa-folder-open"></i> Manage</a>
                                                <button onclick="document.getElementById('edit-class-tab-<?php echo $c['id']; ?>').style.display='block'" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; padding:4px 8px; font-size:11px;"><i class="fa-solid fa-pen"></i> Edit</button>
                                            </div>

                                            <!-- Edit Class Modal (Admin) -->
                                            <div id="edit-class-tab-<?php echo $c['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                                                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:20px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3); text-align:left;">
                                                    <h3 style="color:var(--text-heading); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Class Details</h3>
                                                    <form action="" method="POST" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                                                        <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Program / Class Name</label>
                                                            <input type="text" name="class_name" value="<?php echo htmlspecialchars($c['class_name']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Session</label>
                                                            <input type="text" name="session_name" value="<?php echo htmlspecialchars($c['session_name']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Section</label>
                                                            <input type="text" name="section_name" value="<?php echo htmlspecialchars($c['section_name']); ?>" required>
                                                        </div>
                                                        <div style="text-align:right;">
                                                            <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-class-tab-<?php echo $c['id']; ?>').style.display='none'">Cancel</button>
                                                            <button type="submit" name="edit_class_admin" class="btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center;">No classes created yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="data-table-card">
                <div class="card-header"><h3><i class="fa-solid fa-graduation-cap"></i> Students</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead><tr><th>University ID</th><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if($st_res && $st_res->num_rows > 0): ?>
                                <?php while($row = $st_res->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['university_id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo $row['is_approved'] ? '<span class="status-badge status-approved">Approved</span>' : '<span class="status-badge status-pending">Pending</span>'; ?></td>
                                        <td>
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                <?php if($row['is_approved'] == 0): ?>
                                                    <a href="manage_users.php?approve_id=<?php echo $row['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn-primary" style="background:rgba(34,197,94,0.1); color:#22c55e; border:1px solid rgba(34,197,94,0.2); text-decoration:none; padding:6px 10px; font-size:12px;">Approve</a>
                                                <?php endif; ?>
                                                <button onclick="document.getElementById('edit-stu-<?php echo $row['id']; ?>').style.display='block'" class="btn-primary" style="background:transparent; border:1px solid #38bdf8; color:#38bdf8; padding:6px 10px; font-size:12px;">Edit</button>
                                                <button onclick="document.getElementById('reset-stu-pass-<?php echo $row['id']; ?>').style.display='block'" class="btn-primary" style="background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.4); color:#fbbf24; padding:6px 10px; font-size:12px;"><i class="fa-solid fa-key"></i> Reset Pass</button>
                                                <a href="manage_users.php?delete_id=<?php echo $row['id']; ?>&tab=<?php echo $active_tab; ?>" class="btn-primary" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); text-decoration:none; padding:6px 10px; font-size:12px;" onclick="return confirm('Delete student?')">Delete</a>
                                            </div>

                                            <!-- Edit Student Modal -->
                                            <div id="edit-stu-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
                                                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:25px; width:90%; max-width:400px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 4px 15px rgba(0,0,0,0.3); text-align:left;">
                                                    <h3 style="color:var(--text-heading); margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">Edit Student</h3>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Full Name</label>
                                                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>University ID</label>
                                                            <input type="text" name="university_id" value="<?php echo htmlspecialchars($row['university_id']); ?>" required>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Email Address</label>
                                                            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                        </div>
                                                        <div style="text-align:right;">
                                                            <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:10px;" onclick="document.getElementById('edit-stu-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                            <button type="submit" name="edit_student" class="btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Reset Password Modal (Student) -->
                                            <div id="reset-stu-pass-<?php echo $row['id']; ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000;">
                                                <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:25px; width:90%; max-width:380px; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); box-shadow:0 8px 30px rgba(0,0,0,0.4); text-align:left;">
                                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                                                        <h3 style="color:#fbbf24; margin:0;"><i class="fa-solid fa-key"></i> Reset Password</h3>
                                                        <button onclick="document.getElementById('reset-stu-pass-<?php echo $row['id']; ?>').style.display='none'" style="background:none; border:none; color:var(--text-main); font-size:18px; cursor:pointer;">&times;</button>
                                                    </div>
                                                    <p style="font-size:13px; color:#94a3b8; margin-bottom:15px;">Setting new password for: <strong style="color:var(--text-heading);"><?php echo htmlspecialchars($row['full_name']); ?></strong></p>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="reset_user_id" value="<?php echo $row['id']; ?>">
                                                        <div class="form-group" style="margin-bottom:12px;">
                                                            <label>New Password</label>
                                                            <input type="password" name="new_password" required placeholder="Min 6 characters" style="padding:10px;">
                                                        </div>
                                                        <div class="form-group" style="margin-bottom:15px;">
                                                            <label>Confirm Password</label>
                                                            <input type="password" name="confirm_password" required placeholder="Re-enter password" style="padding:10px;">
                                                        </div>
                                                        <div style="text-align:right;">
                                                            <button type="button" class="btn-primary" style="background:transparent; border:1px solid var(--border-color); color:var(--text-main); margin-right:8px;" onclick="document.getElementById('reset-stu-pass-<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                                                            <button type="submit" name="reset_password" class="btn-primary" style="background:#fbbf24; color:#1e293b; border-color:#fbbf24;"><i class="fa-solid fa-rotate"></i> Reset</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No students found in this department.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif($active_tab == 'others' && $_SESSION['role'] == 'admin'): ?>
            <!-- OTHERS -->
            <?php 
            $other_sql = "SELECT * FROM users WHERE (department_id IS NULL OR department_id = 0) AND role != 'admin'";
            if($search) $other_sql .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";
            $other_res = $conn->query($other_sql);
            ?>
            <div class="data-table-card">
                <div class="card-header"><h3>Other / Unassigned Users</h3></div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if($other_res && $other_res->num_rows > 0): ?>
                                <?php while($row = $other_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo ucfirst($row['role']); ?></td>
                                        <td><a href="manage_users.php?delete_id=<?php echo $row['id']; ?>&tab=others" style="color:#ef4444;" onclick="return confirm('Delete user?')"><i class="fa-solid fa-trash"></i></a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">No unassigned users.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
