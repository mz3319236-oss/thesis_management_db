<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$error = ''; $success = '';

if(!isset($_GET['id'])) { header("Location: pending_requests.php"); exit(); }
$thesis_id = intval($_GET['id']);
$supervisor_id = $_SESSION['user_id'];

$check = $conn->query("SELECT t.*, u.full_name as student_name, u.email as student_email, u.university_id FROM theses t JOIN users u ON t.student_id = u.id WHERE t.id='$thesis_id' AND t.supervisor_id='$supervisor_id'");
if($check->num_rows == 0) {
    die("Error: Thesis not found or you don't have permission to review this.");
}
$thesis = $check->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status'])) {
    $new_status = $conn->real_escape_string($_POST['status']);
    $feedback = $conn->real_escape_string($_POST['feedback']);
    
    if (($new_status == 'rejected' || $new_status == 'revision_required') && empty($feedback)) {
        $error = "Feedback is required when marking as Reject or Return to Student.";
    } else {
        $sql = "UPDATE theses SET status='$new_status', feedback='$feedback' WHERE id='$thesis_id'";
        if ($conn->query($sql) === TRUE) {
            $success = "Thesis status successfully updated!";
            $thesis['status'] = $new_status;
            $thesis['feedback'] = $feedback;
            
            // Handle Action Attachment
            $action_attached_file = NULL;
            if(isset($_FILES['action_attachment']) && $_FILES['action_attachment']['error'] == 0) {
                $file_name = $_FILES['action_attachment']['name'];
                $file_tmp = $_FILES['action_attachment']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_file_name = time() . '_sup_att_' . rand(1000, 9999) . '.' . $file_ext;
                $upload_dir = '../uploads/proposals/';
                if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $action_attached_file = $new_file_name;
                    $action_comment = "Supervisor updated status and attached a file for review.";
                    $conn->query("INSERT INTO thesis_comments (thesis_id, user_id, comment, attached_file) VALUES ('$thesis_id', '$supervisor_id', '$action_comment', '$action_attached_file')");
                }
            }
            
            // Push Notification to Student
            $status_msg = $new_status == 'revision_required' ? 'Returned to Student' : str_replace('_', ' ', $new_status);
            $message = "Your thesis status has been updated to: " . $status_msg;
            $link = "../student/my_thesis.php";
            $student_id = $thesis['student_id'];
            $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ('$student_id', '$message', '$link')");
            
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_comment'])) {
    $comment = isset($_POST['comment_text']) ? $conn->real_escape_string($_POST['comment_text']) : '';
    $attached_file = NULL;

    if(isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] == 0) {
        $file_name = $_FILES['attached_file']['name'];
        $file_tmp = $_FILES['attached_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = time() . '_sup_att_' . rand(1000, 9999) . '.' . $file_ext;
        $upload_dir = '../uploads/proposals/';
        if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $attached_file = $new_file_name;
        }
    }

    if (!empty($comment) || $attached_file) {
        if ($attached_file) {
            $res = $conn->query("INSERT INTO thesis_comments (thesis_id, user_id, comment, attached_file) VALUES ('$thesis_id', '$supervisor_id', '$comment', '$attached_file')");
        } else {
            $res = $conn->query("INSERT INTO thesis_comments (thesis_id, user_id, comment) VALUES ('$thesis_id', '$supervisor_id', '$comment')");
        }
        
        if ($res) {
            // Notify student
            $student_id = $thesis['student_id'];
            $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ('$student_id', 'Your supervisor left a comment on your thesis.', '../student/my_thesis.php')");
            $success = "Comment posted successfully.";
        } else {
            $error = "Failed to post comment: " . $conn->error;
        }
    }
} else {
    // auto-progress to under_review
    if ($thesis['status'] == 'pending') {
        $conn->query("UPDATE theses SET status='under_review' WHERE id='$thesis_id'");
        $thesis['status'] = 'under_review';
    }
}

// Fetch comments
$comments_q = $conn->query("SELECT c.*, u.full_name as speaker_name, u.role FROM thesis_comments c JOIN users u ON c.user_id = u.id WHERE c.thesis_id='$thesis_id' ORDER BY c.created_at ASC");

?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
            <h2 style="color: var(--text-heading);">Review Proposal</h2>
            <a href="pending_requests.php" style="color:#38bdf8; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Queue</a>
        </div>

        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:25px;">
            <!-- Thesis Details -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3>Proposal Details</h3>
                </div>
                <div style="color:var(--text-main); font-size:14px; line-height: 1.6;">
                    <p><strong>Title:</strong> <?php echo htmlspecialchars($thesis['title']); ?></p>
                    <p><strong>Domain:</strong> <?php echo htmlspecialchars($thesis['domain']); ?></p>
                    <p><strong>Current Status:</strong> 
                        <span class="status-badge status-<?php echo strtolower($thesis['status']); ?>">
                            <?php echo $thesis['status'] == 'revision_required' ? 'Returned to Student' : str_replace('_', ' ', htmlspecialchars($thesis['status'])); ?>
                        </span>
                    </p>
                    <br>
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($thesis['student_name']); ?> (<?php echo htmlspecialchars($thesis['university_id']); ?>)</p>
                    <hr style="margin:20px 0; border:0; border-top:1px solid rgba(255,255,255,0.05);">
                    <p><strong>Abstract:</strong></p>
                    <p style="background: rgba(15,23,42,0.6); padding:15px; border-radius:8px; margin-top:10px;">
                        <?php echo nl2br(htmlspecialchars($thesis['abstract'])); ?>
                    </p>
                </div>
                    <?php if(!empty($thesis['document_path'])): ?>
                        <a href="../uploads/proposals/<?php echo urlencode($thesis['document_path']); ?>" target="_blank" class="btn-primary" style="display:block; text-align:center; text-decoration:none;">
                            <i class="fa-solid fa-file-pdf"></i> Download & View Project File
                        </a>
                    <?php else: ?>
                        <div style="background:rgba(255,193,7,0.1); color:#ffc107; padding:15px; border-radius:8px; text-align:center; margin-top:15px; border:1px solid rgba(255,193,7,0.2);">
                            <i class="fa-solid fa-hourglass-start"></i> No project file uploaded yet. Review the proposal details above.
                        </div>
                    <?php endif; ?>

                    <!-- Version History for Supervisor -->
                    <div style="margin-top:30px;">
                        <h4 style="color:var(--text-heading); margin-bottom:15px;"><i class="fa-solid fa-clock-rotate-left"></i> All Submitted Versions</h4>
                        <div class="table-responsive">
                            <table class="premium-table" style="font-size:12px;">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $versions_q = $conn->query("SELECT * FROM thesis_versions WHERE thesis_id='$thesis_id' ORDER BY uploaded_at DESC");
                                    while($v = $versions_q->fetch_assoc()): ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($v['version_label']); ?></b></td>
                                        <td><?php echo date('d M, Y', strtotime($v['uploaded_at'])); ?></td>
                                        <td>
                                            <a href="../uploads/proposals/<?php echo urlencode($v['file_path']); ?>" target="_blank" style="color:#38bdf8;"><i class="fa-solid fa-download"></i> View</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div> <!-- Closes data-table-card -->

            <!-- Action Area -->
            <div class="data-table-card">
                <div class="card-header">
                    <h3>Supervisor Action</h3>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" class="dashboard-form" style="padding:20px; border:none; background:transparent;">
                    <div class="form-group">
                        <label>Decision / Status</label>
                        <select name="status" required>
                            <option value="under_review" <?php if($thesis['status']=='under_review') echo 'selected'; ?>>Under Review</option>
                            <option value="revision_required" <?php if($thesis['status']=='revision_required') echo 'selected'; ?>>Return to Student (Revision Required)</option>
                            <option value="approved" <?php if($thesis['status']=='approved') echo 'selected'; ?>>Approve Proposal</option>
                            <option value="rejected" <?php if($thesis['status']=='rejected') echo 'selected'; ?>>Reject Completely</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Feedback / Notes (Visible to student)</label>
                        <textarea name="feedback" placeholder="Provide reason for rejection or details for required revisions..."><?php echo htmlspecialchars($thesis['feedback'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Attach Checked File (Optional)</label>
                        <input type="file" name="action_attachment" style="background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); padding:10px; border-radius:8px; width:100%;">
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Save Decision</button>
                </form>
            </div>
        </div> <!-- Closes grid -->

        <!-- Discussion Thread -->
        <div class="data-table-card" style="margin-top: 25px;">
            <div class="card-header">
                <h3><i class="fa-regular fa-comments"></i> Discussion & Comments</h3>
            </div>
            <div style="padding: 20px; background: rgba(0,0,0,0.02); max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <?php if(!$comments_q): ?>
                    <p style="text-align:center; color:red;">Database Error: <?php echo $conn->error; ?></p>
                <?php elseif($comments_q->num_rows > 0): ?>
                    <?php while($c = $comments_q->fetch_assoc()): 
                        $is_me = ($c['user_id'] == $supervisor_id);
                        $bg = $is_me ? 'rgba(56, 189, 248, 0.1)' : 'rgba(15, 23, 42, 0.05)';
                        $align = $is_me ? 'margin-left:auto;' : 'margin-right:auto;';
                    ?>
                    <div style="width:70%; <?php echo $align; ?> background: <?php echo $bg; ?>; border: 1px solid var(--border-color); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                            <b style="color: <?php echo $is_me ? '#38bdf8' : 'var(--text-heading)'; ?>; font-size:13px;"><?php echo htmlspecialchars($c['speaker_name']); ?> (<?php echo ucfirst($c['role']); ?>)</b>
                            <span style="font-size:11px; color:#64748b;"><?php echo date('d M, H:i', strtotime($c['created_at'])); ?></span>
                        </div>
                        <p style="color:var(--text-main); font-size:14px; line-height:1.5;"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                        <?php if(!empty($c['attached_file'])): ?>
                            <div style="margin-top:10px;">
                                <a href="../uploads/proposals/<?php echo urlencode($c['attached_file']); ?>" target="_blank" style="color:#38bdf8; font-size:13px; text-decoration:none; background:rgba(56,189,248,0.1); padding:5px 10px; border-radius:5px; display:inline-block;">
                                    <i class="fa-solid fa-paperclip"></i> View Attached File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#64748b;">No comments yet. Start the discussion!</p>
                <?php endif; ?>
            </div>
            <div style="padding: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                <form action="" method="POST" enctype="multipart/form-data" style="display:flex; gap:15px; align-items:center;">
                    <input type="file" name="attached_file" style="max-width: 250px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); padding:8px; border-radius:8px;">
                    <input type="text" name="comment_text" placeholder="Type your comment here..." style="flex:1; background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); padding:10px 15px; border-radius:8px;">
                    <button type="submit" name="post_comment" class="btn-primary" style="padding:10px 20px;"><i class="fa-solid fa-paper-plane"></i> Send</button>
                </form>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
