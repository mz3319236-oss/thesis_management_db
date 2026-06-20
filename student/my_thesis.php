<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$sql = "SELECT t.*, u.full_name as supervisor_name FROM theses t 
        LEFT JOIN users u ON t.supervisor_id = u.id 
        WHERE t.student_id='$student_id' ORDER BY t.created_at DESC";
$result = $conn->query($sql);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_comment'])) {
    $comment = isset($_POST['comment_text']) ? $conn->real_escape_string($_POST['comment_text']) : '';
    $thesis_id = intval($_POST['thesis_id']);
    $attached_file = NULL;

    if(isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] == 0) {
        $file_name = $_FILES['attached_file']['name'];
        $file_tmp = $_FILES['attached_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = time() . '_stu_att_' . rand(1000, 9999) . '.' . $file_ext;
        $upload_dir = '../uploads/proposals/';
        if(move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $attached_file = $new_file_name;
        }
    }

    if (!empty($comment) || $attached_file) {
        if ($attached_file) {
            $res = $conn->query("INSERT INTO thesis_comments (thesis_id, user_id, comment, attached_file) VALUES ('$thesis_id', '$student_id', '$comment', '$attached_file')");
        } else {
            $res = $conn->query("INSERT INTO thesis_comments (thesis_id, user_id, comment) VALUES ('$thesis_id', '$student_id', '$comment')");
        }
        
        if ($res) {
            // Notify supervisor
            $sup_q = $conn->query("SELECT supervisor_id FROM theses WHERE id='$thesis_id'");
            if ($sup_q->num_rows > 0) {
                $sup_id = $sup_q->fetch_assoc()['supervisor_id'];
                $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ('$sup_id', 'Student posted a comment with attachment on their thesis.', '../supervisor/review_thesis.php?id=$thesis_id')");
            }
            $success = "Comment posted successfully.";
        } else {
            $error = "Failed to post comment: " . $conn->error;
            echo "<script>alert('Error: " . addslashes($error) . "');</script>";
        }
    }
}

// Handle New Version Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_version'])) {
    $thesis_id = intval($_POST['thesis_id']);
    
    if(isset($_FILES['new_document']) && $_FILES['new_document']['error'] == 0) {
        $file_name = $_FILES['new_document']['name'];
        $file_tmp = $_FILES['new_document']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $new_file_name = time() . '_v_upd_' . rand(1000, 9999) . '.' . $file_ext;
        $upload_dir = '../uploads/proposals/';
        $upload_path = $upload_dir . $new_file_name;
        
        if(move_uploaded_file($file_tmp, $upload_path)) {
            // Get current max version
            $v_q = $conn->query("SELECT COUNT(*) as v_count FROM thesis_versions WHERE thesis_id='$thesis_id'");
            $v_num = $v_q->fetch_assoc()['v_count'] + 1;
            $v_label = "v" . $v_num . " - Revision";

            // Insert into history
            $conn->query("INSERT INTO thesis_versions (thesis_id, file_path, version_label) VALUES ('$thesis_id', '$new_file_name', '$v_label')");
            
            // Update main record path
            $conn->query("UPDATE theses SET document_path='$new_file_name', status='under_review' WHERE id='$thesis_id'");
            
            // Notify supervisor
            $sup_q = $conn->query("SELECT supervisor_id FROM theses WHERE id='$thesis_id'");
            if ($sup_q->num_rows > 0) {
                $sup_id = $sup_q->fetch_assoc()['supervisor_id'];
                $conn->query("INSERT INTO notifications (user_id, message, link) VALUES ('$sup_id', 'Student uploaded a new version of their thesis.', '../supervisor/review_thesis.php?id=$thesis_id')");
            }
            $success = "New version uploaded successfully!";
        } else {
            $error = "File save failed on the server. Please check folder permissions.";
        }
    } else {
        if (isset($_FILES['new_document'])) {
            $upload_err = $_FILES['new_document']['error'];
            if ($upload_err == UPLOAD_ERR_INI_SIZE) {
                $error = "File too large (exceeds php.ini size limit).";
            } elseif ($upload_err == UPLOAD_ERR_NO_FILE) {
                $error = "No file was selected for upload.";
            } else {
                $error = "Upload error code: " . $upload_err;
            }
        } else {
            $error = "No valid file found in the request.";
        }
        
        // Let's pass the error through a flash message or simple javascript alert
        echo "<script>alert('Error: " . addslashes($error) . "');</script>";
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">My Thesis Status</h2>

        <?php if($result->num_rows > 0): ?>
            <?php while($thesis = $result->fetch_assoc()): ?>
                <div class="data-table-card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($thesis['title']); ?></h3>
                        <span class="status-badge status-<?php echo strtolower($thesis['status']); ?>">
                            <?php echo $thesis['status'] == 'revision_required' ? 'Returned to Student' : str_replace('_', ' ', htmlspecialchars($thesis['status'])); ?>
                        </span>
                    </div>
                    <div style="padding:0 20px 20px 20px; color:var(--text-main); font-size:14px; line-height: 1.6;">
                        <p><strong>Domain:</strong> <?php echo htmlspecialchars($thesis['domain']); ?></p>
                        <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($thesis['supervisor_name'] ?? 'Not Assigned Yet (Pending Admin Approval)'); ?></p>
                        <p><strong>Submitted On:</strong> <?php echo date('d M Y, h:i A', strtotime($thesis['created_at'])); ?></p>
                        <hr style="border:0; border-top:1px solid rgba(255,255,255,0.05); margin:15px 0;">
                        <p><strong>Abstract:</strong><br> <?php echo nl2br(htmlspecialchars($thesis['abstract'])); ?></p>
                        
                        <?php if($thesis['feedback']): ?>
                            <div class="alert alert-danger" style="margin-top:20px; background: rgba(249, 115, 22, 0.1); border-color: rgba(249, 115, 22, 0.3); color: #f97316;">
                                <strong>Supervisor Feedback:</strong><br>
                                <?php echo nl2br(htmlspecialchars($thesis['feedback'])); ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:20px; display:flex; gap:15px; flex-wrap:wrap;">
                            <?php if(!empty($thesis['document_path'])): ?>
                                <a href="../uploads/proposals/<?php echo urlencode($thesis['document_path']); ?>" target="_blank" class="btn-primary" style="background:rgba(56,189,248,0.2); color:#38bdf8; text-decoration:none;">
                                    <i class="fa-solid fa-download"></i> View Latest Project File
                                </a>
                            <?php else: ?>
                                <span style="background:rgba(255,255,255,0.05); color:#94a3b8; padding:8px 15px; border-radius:8px; font-size:14px; border:1px solid rgba(255,255,255,0.1);">
                                    <i class="fa-solid fa-info-circle"></i> Waiting for Supervisor Acceptance to Upload File
                                </span>
                            <?php endif; ?>
                            
                            <?php if($thesis['status'] !== 'rejected'): ?>
                                <button onclick="document.getElementById('upload-area-<?php echo $thesis['id']; ?>').style.display='block'" class="btn-primary" style="background:rgba(34,197,94,0.2); color:#22c55e; border:1px solid rgba(34,197,94,0.3);">
                                    <i class="fa-solid fa-upload"></i> <?php echo empty($thesis['document_path']) ? 'Upload Project File' : 'Upload New Version'; ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Area (Hidden by default) -->
                        <div id="upload-area-<?php echo $thesis['id']; ?>" style="display:none; margin-top:20px; padding:20px; background:rgba(255,255,255,0.02); border:1px dashed rgba(255,255,255,0.1); border-radius:10px;">
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="thesis_id" value="<?php echo $thesis['id']; ?>">
                                <label style="display:block; margin-bottom:10px; font-weight:500;">Select Project Document:</label>
                                <input type="file" name="new_document" required style="margin-bottom:15px; display:block; width:100%;">
                                <button type="submit" name="upload_version" class="btn-primary">Confirm & Upload</button>
                                <button type="button" onclick="this.parentElement.parentElement.style.display='none'" style="background:transparent; border:none; color:#94a3b8; cursor:pointer; margin-left:10px;">Cancel</button>
                            </form>
                        </div>

                        <!-- Version History -->
                        <div style="margin-top:30px;">
                            <h4 style="color:var(--text-heading); margin-bottom:15px;"><i class="fa-solid fa-clock-rotate-left"></i> Version History</h4>
                            <div class="table-responsive">
                                <table class="premium-table" style="font-size:12px;">
                                    <thead>
                                        <tr>
                                            <th>Version</th>
                                            <th>Date Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $versions = $conn->query("SELECT * FROM thesis_versions WHERE thesis_id='".$thesis['id']."' ORDER BY uploaded_at DESC");
                                        while($v = $versions->fetch_assoc()): ?>
                                        <tr>
                                            <td><b><?php echo htmlspecialchars($v['version_label']); ?></b></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($v['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="../uploads/proposals/<?php echo urlencode($v['file_path']); ?>" target="_blank" style="color:#38bdf8;"><i class="fa-solid fa-download"></i> View</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Discussion Thread -->
                        <?php 
                        $t_id = $thesis['id'];
                        $comments_q = $conn->query("SELECT c.*, u.full_name as speaker_name, u.role FROM thesis_comments c JOIN users u ON c.user_id = u.id WHERE c.thesis_id='$t_id' ORDER BY c.created_at ASC");
                        ?>
                        <div style="margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                            <h4 style="color:var(--text-heading); margin-bottom:15px;"><i class="fa-regular fa-comments"></i> Discussion Thread</h4>
                            <div style="background: rgba(0,0,0,0.02); max-height: 250px; overflow-y: auto; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--border-color);">
                                <?php if(!$comments_q): ?>
                                    <p style="text-align:center; color:red;">Database Error: <?php echo $conn->error; ?></p>
                                <?php elseif($comments_q->num_rows > 0): ?>
                                    <?php while($c = $comments_q->fetch_assoc()): 
                                        $is_me = ($c['user_id'] == $student_id);
                                        $bg = $is_me ? 'rgba(56, 189, 248, 0.1)' : 'rgba(15, 23, 42, 0.05)';
                                        $align = $is_me ? 'margin-left:auto;' : 'margin-right:auto;';
                                    ?>
                                    <div style="width:80%; <?php echo $align; ?> background: <?php echo $bg; ?>; border: 1px solid var(--border-color); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
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
                                    <p style="text-align:center; color:#64748b; font-size:12px;">No comments yet.</p>
                                <?php endif; ?>
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
                                <input type="hidden" name="thesis_id" value="<?php echo $thesis['id']; ?>">
                                <input type="file" name="attached_file" style="max-width: 200px; background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); padding:6px; border-radius:5px; font-size:12px;">
                                <input type="text" name="comment_text" placeholder="Reply to supervisor..." style="flex:1; background:var(--input-bg); border:1px solid var(--border-color); color:var(--text-heading); padding:8px 15px; border-radius:5px;">
                                <button type="submit" name="post_comment" class="btn-primary" style="padding:8px 15px;"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="data-table-card">
                <p style="color: #94a3b8; padding: 20px;">You haven't submitted any thesis proposals yet.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
