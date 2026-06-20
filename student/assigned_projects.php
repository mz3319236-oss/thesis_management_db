<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$success = ''; $error = '';

// Handle Work Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_work'])) {
    $thesis_id = intval($_POST['thesis_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $abstract = $conn->real_escape_string($_POST['abstract']);
    
    if(isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $file_name = $_FILES['document']['name'];
        $file_tmp = $_FILES['document']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $new_file_name = "WORK_" . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $upload_dir = '../uploads/proposals/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $conn->query("UPDATE theses SET title='$title', abstract='$abstract', document_path='$new_file_name', status='pending' WHERE id='$thesis_id'");
            $success = "Project details and work submitted successfully!";
        } else {
            $error = "File upload failed.";
        }
    } else {
        // Just update details if no file was uploaded yet (or if they want to update details first)
        $conn->query("UPDATE theses SET title='$title', abstract='$abstract' WHERE id='$thesis_id'");
        $success = "Project details updated successfully!";
    }
}

$assigned = $conn->query("SELECT t.*, u.full_name as supervisor_name FROM theses t JOIN users u ON t.supervisor_id=u.id WHERE t.student_id='$student_id' AND t.status='assigned_to_student'");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">My Assigned Tasks / Projects</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <?php if($assigned->num_rows > 0): ?>
            <?php while($proj = $assigned->fetch_assoc()): ?>
                <div class="data-table-card" style="margin-bottom:25px;">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($proj['title']); ?></h3>
                        <span class="status-badge status-pending">ASSIGNED</span>
                    </div>
                    <div style="padding:20px;">
                        <p style="color:var(--text-main); margin-bottom:15px;"><b>Assigned By:</b> <?php echo htmlspecialchars($proj['supervisor_name']); ?></p>
                        
                        <form action="" method="POST" enctype="multipart/form-data" class="dashboard-form" style="padding:0; border:none; background:transparent;">
                            <input type="hidden" name="thesis_id" value="<?php echo $proj['id']; ?>">
                            
                            <div class="form-group">
                                <label>Project Title</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($proj['title'] == 'Pending Student Input' ? '' : $proj['title']); ?>" required placeholder="Enter your project title...">
                            </div>

                            <div class="form-group">
                                <label>Project Description</label>
                                <textarea name="abstract" required placeholder="Provide a detailed description of your project..." style="min-height: 120px;"><?php echo htmlspecialchars($proj['abstract'] == 'Student will provide the project description.' ? '' : $proj['abstract']); ?></textarea>
                            </div>

                            <h4 style="color:var(--text-heading); margin-bottom:10px; margin-top:20px;">Upload Your Work (Optional if only updating details)</h4>
                            <div class="form-group">
                                <input type="file" name="document">
                                <small style="color:#64748b; display:block; margin-top:5px;">You can upload your initial files here.</small>
                            </div>
                            
                            <div style="display:flex; gap:15px; margin-top:20px;">
                                <button type="submit" name="submit_work" class="btn-primary"><i class="fa-solid fa-save"></i> Save Details & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="data-table-card" style="text-align:center; padding:40px;">
                <i class="fa-solid fa-folder-open" style="font-size:48px; color:#334155; margin-bottom:15px;"></i>
                <h3 style="color:var(--text-heading);">No Projects Assigned</h3>
                <p style="color:#64748b;">You don't have any assigned tasks from your supervisor at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
