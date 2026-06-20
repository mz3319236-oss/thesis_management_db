<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$supervisor_id = $_SESSION['user_id'];
// Get pending and revision required theses
$sql = "SELECT t.*, u.full_name as student_name, u.university_id 
        FROM theses t 
        JOIN users u ON t.student_id = u.id 
        WHERE t.supervisor_id='$supervisor_id' 
        AND t.status IN ('pending', 'revision_required', 'under_review') 
        ORDER BY t.created_at ASC";
$result = $conn->query($sql);
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Thesis Verification Queue</h2>

        <div class="data-table-card">
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size:12px;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br>
                                    <span style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($row['university_id']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($row['title'], 0, 40)) . '...'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status'] == 'revision_required' ? 'Returned to Student' : str_replace('_', ' ', htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="review_thesis.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size:12px; text-decoration:none;">Review</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No pending requests found.</td></tr>
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
