<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = '';

// Handle Permission Toggle
if (isset($_GET['toggle']) && isset($_GET['role']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $role = $conn->real_escape_string($_GET['role']); 
    $current_val = intval($_GET['toggle']);
    $new_val = $current_val ? 0 : 1;

    if (in_array($role, ['student', 'supervisor', 'admin'])) {
        $conn->query("UPDATE role_permissions SET $role = $new_val WHERE id = $id");
        $success = "Permission for ".ucfirst($role)." updated!";
    }
}

$permissions = $conn->query("SELECT * FROM role_permissions ORDER BY page_name ASC");
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Role Access Permissions</h2>

        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="data-table-card">
            <div class="card-header">
                <h3>Global Access Matrix</h3>
                <p style="font-size:11px; color:#94a3b8; margin-top:5px;">Toggle switches to enable/disable access for specific roles.</p>
            </div>
            <div class="table-responsive">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Page / Module</th>
                            <th style="text-align:center;">Student</th>
                            <th style="text-align:center;">Supervisor</th>
                            <th style="text-align:center;">Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $permissions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <b style="color:var(--text-heading); font-size:15px;"><?php echo htmlspecialchars($row['page_name']); ?></b>
                            </td>
                            
                            <!-- Student Toggle -->
                            <td style="text-align:center;">
                                <a href="?id=<?php echo $row['id']; ?>&role=student&toggle=<?php echo $row['student']; ?>">
                                    <i class="fa-solid <?php echo $row['student'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="font-size:26px; color:<?php echo $row['student'] ? '#22c55e' : '#64748b'; ?>;"></i>
                                </a>
                            </td>

                            <!-- Supervisor Toggle -->
                            <td style="text-align:center;">
                                <a href="?id=<?php echo $row['id']; ?>&role=supervisor&toggle=<?php echo $row['supervisor']; ?>">
                                    <i class="fa-solid <?php echo $row['supervisor'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="font-size:26px; color:<?php echo $row['supervisor'] ? '#22c55e' : '#64748b'; ?>;"></i>
                                </a>
                            </td>

                            <!-- Admin Toggle -->
                            <td style="text-align:center;">
                                <a href="?id=<?php echo $row['id']; ?>&role=admin&toggle=<?php echo $row['admin']; ?>">
                                    <i class="fa-solid <?php echo $row['admin'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="font-size:26px; color:<?php echo $row['admin'] ? '#22c55e' : '#64748b'; ?>;"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
