<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$sql = "SELECT t.*, u.full_name as student_name, u.university_id, u.email 
        FROM theses t 
        JOIN users u ON t.student_id = u.id 
        WHERE t.supervisor_id='$supervisor_id' 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">My Assigned Students</h2>

        <div class="data-table-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Thesis Title</th>
                            <th>Current Status</th>
                            <th>Submitted On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                    <span style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($row['university_id']); ?> - <?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($row['title'], 0, 45)) . '...'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                                <td style="font-size:13px; color:var(--text-main);"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="review_thesis.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size:12px; text-decoration:none;"><i class="fa-regular fa-comments"></i> View & Chat</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">No students assigned yet.</td></tr>
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
