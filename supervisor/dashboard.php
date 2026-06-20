<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

// Ensure only supervisor can access
if ($_SESSION['role'] !== 'supervisor') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Fetch department name
$dept_name = 'Not Assigned';
$dept_query = $conn->query("SELECT d.name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = '$user_id'");
if ($dept_query && $dept_query->num_rows > 0) {
    $dept_row = $dept_query->fetch_assoc();
    $dept_name = $dept_row['name'] ?? 'Not Assigned';
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        
        <!-- Welcome Header -->
        <div class="dashboard-welcome" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">
            <div>
                <h2>Dashboard Overview</h2>
                <p>Welcome back, <?php echo htmlspecialchars($full_name); ?>. Here's your supervision summary.</p>
                <p style="color: #38bdf8; font-size: 13px; font-weight: 600; margin-top: 4px;"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($dept_name); ?></p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="../admin/manage_deadlines.php" class="btn-primary" style="background:rgba(249,115,22,0.1); color:#f97316; border:1px solid rgba(249,115,22,0.2); text-decoration:none; font-size:13px;"><i class="fa-solid fa-calendar-plus"></i> Add Deadline</a>
                <a href="../admin/manage_resources.php" class="btn-primary" style="background:rgba(168,85,247,0.1); color:#a855f7; border:1px solid rgba(168,85,247,0.2); text-decoration:none; font-size:13px;"><i class="fa-solid fa-file-circle-plus"></i> Add Guideline</a>
            </div>
        </div>

        <?php 
        $q_my_students = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM theses WHERE supervisor_id='$user_id'");
        $q_to_review = $conn->query("SELECT COUNT(*) as c FROM theses WHERE supervisor_id='$user_id' AND status IN ('pending', 'under_review')");
        $q_approved = $conn->query("SELECT COUNT(*) as c FROM theses WHERE supervisor_id='$user_id' AND status='approved'");
        $q_total_theses = $conn->query("SELECT COUNT(*) as c FROM theses WHERE supervisor_id='$user_id'");

        $stats = [
            'my_students' => ($q_my_students) ? $q_my_students->fetch_assoc()['c'] : 0,
            'to_review' => ($q_to_review) ? $q_to_review->fetch_assoc()['c'] : 0,
            'approved' => ($q_approved) ? $q_approved->fetch_assoc()['c'] : 0,
            'total' => ($q_total_theses) ? $q_total_theses->fetch_assoc()['c'] : 0,
        ];
        ?>

        <!-- 3 Stat Cards Row (FunFairERP Style) -->
        <div class="dashboard-stats-grid">
            <div class="dashboard-stat-card card-blue card-link" onclick="window.location='my_students.php'">
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-number"><?php echo $stats['my_students']; ?></div>
                    <div class="dashboard-stat-label">My Students</div>
                </div>
                <div class="dashboard-stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="dashboard-stat-card card-orange card-link" onclick="window.location='pending_requests.php'">
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-number"><?php echo $stats['to_review']; ?></div>
                    <div class="dashboard-stat-label">Pending Reviews</div>
                </div>
                <div class="dashboard-stat-icon">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>

            <div class="dashboard-stat-card card-green card-link" onclick="window.location='my_students.php'">
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="dashboard-stat-label">Approved Theses</div>
                </div>
                <div class="dashboard-stat-icon">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="dashboard-charts-grid">
            <!-- CHART 1: STATUS -->
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-header">
                    <div class="dashboard-chart-title">
                        <i class="fa-solid fa-chart-pie"></i>
                        Status Distribution
                    </div>
                    <div class="dashboard-chart-legends">
                        <span class="dashboard-chart-legend-item"><span class="dashboard-chart-legend-dot" style="background:#22c55e;"></span> Approved</span>
                        <span class="dashboard-chart-legend-item"><span class="dashboard-chart-legend-dot" style="background:#f97316;"></span> Pending</span>
                        <span class="dashboard-chart-legend-item"><span class="dashboard-chart-legend-dot" style="background:#a855f7;"></span> Revision</span>
                    </div>
                </div>
                <div class="dashboard-chart-body">
                    <canvas id="supervisorStatusChart"></canvas>
                </div>
            </div>

            <!-- CHART 2: CLASS WISE -->
            <div class="dashboard-chart-card">
                <div class="dashboard-chart-header">
                    <div class="dashboard-chart-title">
                        <i class="fa-solid fa-chart-bar"></i>
                        Projects by Class
                    </div>
                    <div class="dashboard-chart-legends">
                        <span class="dashboard-chart-legend-item"><span class="dashboard-chart-legend-dot" style="background:#38bdf8;"></span> Projects</span>
                    </div>
                </div>
                <div class="dashboard-chart-body">
                    <canvas id="supervisorClassChart"></canvas>
                </div>
            </div>

            <?php
            // Fetch status distribution
            $status_data = $conn->query("SELECT status, COUNT(*) as count FROM theses WHERE supervisor_id='$user_id' GROUP BY status");
            $labels = []; $counts = []; $colors = [];
            $color_map = [
                'pending' => '#f97316',
                'under_review' => '#38bdf8',
                'approved' => '#22c55e',
                'rejected' => '#ef4444',
                'revision_required' => '#a855f7',
                'assigned_to_student' => '#64748b'
            ];

            if ($status_data) {
                while ($row = $status_data->fetch_assoc()) {
                    $labels[] = ucfirst(str_replace('_', ' ', $row['status']));
                    $counts[] = $row['count'];
                    $colors[] = $color_map[$row['status']] ?? '#CBD5E1';
                }
            }

            // Fetch class distribution
            $class_stats = $conn->query("SELECT c.class_name, COUNT(t.id) as count 
                                         FROM classes c 
                                         LEFT JOIN users u ON c.id = u.class_id AND u.role = 'student'
                                         LEFT JOIN theses t ON u.id = t.student_id 
                                         WHERE c.department_id = (SELECT department_id FROM users WHERE id='$user_id') 
                                         GROUP BY c.id");
            $class_labels = []; $class_counts = [];
            if ($class_stats) {
                while($row = $class_stats->fetch_assoc()){
                    $class_labels[] = $row['class_name'];
                    $class_counts[] = $row['count'];
                }
            }
            ?>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Status Chart (Bar instead of Pie for FunFairERP consistency)
                const ctx = document.getElementById('supervisorStatusChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($counts); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>,
                            borderWidth: 0,
                            hoverOffset: 20
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#64748b',
                                    padding: 16,
                                    font: { size: 11, family: 'Outfit' },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleFont: { family: 'Outfit', size: 13 },
                                bodyFont: { family: 'Outfit', size: 12 },
                                padding: 12,
                                cornerRadius: 8
                            }
                        }
                    }
                });

                // Class Chart (Bar)
                const classCtx = document.getElementById('supervisorClassChart').getContext('2d');
                new Chart(classCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($class_labels); ?>,
                        datasets: [{
                            label: 'Projects',
                            data: <?php echo json_encode($class_counts); ?>,
                            backgroundColor: [
                                'rgba(56, 189, 248, 0.85)',
                                'rgba(234, 179, 8, 0.85)',
                                'rgba(168, 85, 247, 0.85)',
                                'rgba(34, 197, 94, 0.85)',
                                'rgba(249, 115, 22, 0.85)'
                            ],
                            borderRadius: 6,
                            barPercentage: 0.55,
                            categoryPercentage: 0.7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleFont: { family: 'Outfit', size: 13 },
                                bodyFont: { family: 'Outfit', size: 12 },
                                padding: 12,
                                cornerRadius: 8,
                                boxPadding: 4
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.06)', drawBorder: false },
                                ticks: { color: '#64748b', font: { family: 'Outfit', size: 11 }, stepSize: 1 },
                                border: { display: false }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#64748b', font: { family: 'Outfit', size: 11 } },
                                border: { display: false }
                            }
                        }
                    }
                });
            });
            </script>
        </div>

        <!-- Info Grid: Recent Submissions + Quick Insights -->
        <div class="dashboard-info-grid">
            <!-- RECENT SUBMISSIONS -->
            <div class="dashboard-recent-card" style="margin-top: 0;">
                <div class="dashboard-recent-header">
                    <h3><i class="fa-solid fa-file-circle-check" style="color:#38bdf8;"></i> Recent Submissions Ready for Review</h3>
                    <a href="pending_requests.php">Review All →</a>
                </div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Project Domain</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $list = $conn->query("SELECT t.*, u.full_name FROM theses t JOIN users u ON t.student_id = u.id WHERE t.supervisor_id='$user_id' AND t.status IN ('pending', 'under_review') LIMIT 5");
                            if($list && $list->num_rows > 0):
                                while($row = $list->fetch_assoc()): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($row['full_name']); ?></b></td>
                                    <td><?php echo htmlspecialchars($row['domain']); ?></td>
                                    <td><span class="status-badge status-pending"><?php echo str_replace('_', ' ', $row['status']); ?></span></td>
                                    <td><a href="review_thesis.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration:none;">Review Now</a></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No pending submissions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- QUICK INSIGHTS -->
            <div class="dashboard-quick-panel" style="margin-top: 0;">
                <div class="panel-title"><i class="fa-solid fa-bolt" style="color:#eab308;"></i> Quick Insights</div>

                <div class="dashboard-insight-box insight-blue">
                    <h4>Recent Activity</h4>
                    <p>You have <?php echo $stats['to_review']; ?> new submissions waiting for your feedback.</p>
                </div>

                <div class="dashboard-insight-box insight-green">
                    <h4>Completion Rate</h4>
                    <p>
                        <?php 
                        $total = $stats['total'];
                        $rate = $total > 0 ? round(($stats['approved'] / $total) * 100) : 0;
                        echo $rate . "% of your assigned projects are successfully completed.";
                        ?>
                    </p>
                </div>

                <div class="dashboard-insight-box insight-orange">
                    <h4>Department</h4>
                    <p>You are assigned to the <?php echo htmlspecialchars($dept_name); ?> department.</p>
                </div>
            </div>
        </div>

    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div> <!-- Close app-container -->
</body>
</html>
