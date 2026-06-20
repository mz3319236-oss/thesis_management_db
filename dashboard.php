<?php
require_once 'includes/header.php';
require_once 'config/db_connect.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

?>
<?php require_once 'includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once 'includes/navbar.php'; ?>
    <div class="content-area">
        
        <?php if ($role == 'admin'): ?>
            <!-- ADMIN DASHBOARD - FunFairERP Style -->
            <?php 
            $q_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'");
            $q_supervisors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='supervisor'");
            $q_theses = $conn->query("SELECT COUNT(*) as c FROM theses");
            $q_pending = $conn->query("SELECT COUNT(*) as c FROM theses WHERE status='pending'");
            $q_approved = $conn->query("SELECT COUNT(*) as c FROM theses WHERE status='approved'");
            $q_departments = $conn->query("SELECT COUNT(*) as c FROM departments");
            $q_total_users = $conn->query("SELECT COUNT(*) as c FROM users");
            $q_domains = $conn->query("SELECT COUNT(DISTINCT domain) as c FROM theses");

            $stats = [
                'students' => ($q_students) ? $q_students->fetch_assoc()['c'] : 0,
                'supervisors' => ($q_supervisors) ? $q_supervisors->fetch_assoc()['c'] : 0,
                'theses' => ($q_theses) ? $q_theses->fetch_assoc()['c'] : 0,
                'pending' => ($q_pending) ? $q_pending->fetch_assoc()['c'] : 0,
                'approved' => ($q_approved) ? $q_approved->fetch_assoc()['c'] : 0,
                'departments' => ($q_departments) ? $q_departments->fetch_assoc()['c'] : 0,
                'total_users' => ($q_total_users) ? $q_total_users->fetch_assoc()['c'] : 0,
                'domains' => ($q_domains) ? $q_domains->fetch_assoc()['c'] : 0,
            ];
            ?>

            <!-- Welcome Header -->
            <div class="dashboard-welcome">
                <h2>Dashboard Overview</h2>
                <p>Welcome back, <?php echo htmlspecialchars($full_name); ?>. Here's what's happening today.</p>
            </div>

            <!-- 6 Stat Cards - 3x2 Grid (FunFairERP Style) -->
            <div class="dashboard-stats-grid">
                <!-- Card 1: Total Students -->
                <div class="dashboard-stat-card card-blue card-link" onclick="window.location='admin/manage_users.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $stats['students']; ?></div>
                        <div class="dashboard-stat-label">Total Students</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                </div>

                <!-- Card 2: Total Theses -->
                <div class="dashboard-stat-card card-green card-link" onclick="window.location='admin/all_theses.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $stats['theses']; ?></div>
                        <div class="dashboard-stat-label">Thesis Submissions</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>

                <!-- Card 3: Registered Users -->
                <div class="dashboard-stat-card card-yellow card-link" onclick="window.location='admin/manage_users.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="dashboard-stat-label">Registered Users</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- Card 4: Departments -->
                <div class="dashboard-stat-card card-red card-link" onclick="window.location='admin/manage_departments.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $stats['departments']; ?></div>
                        <div class="dashboard-stat-label">Active Departments</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                </div>

                <!-- Card 5: Supervisors -->
                <div class="dashboard-stat-card card-cyan card-link" onclick="window.location='admin/manage_users.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number">Supervisors</div>
                        <div class="dashboard-stat-label"><?php echo $stats['supervisors']; ?> Active Faculty Members</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                </div>

                <!-- Card 6: Pending Reviews -->
                <div class="dashboard-stat-card card-orange card-link" onclick="window.location='admin/all_theses.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="dashboard-stat-label">Awaiting Review</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>


            <?php
            // Fetch status distribution for chart
            $status_data = $conn->query("SELECT status, COUNT(*) as count FROM theses GROUP BY status");
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

            // Fetch department stats
            $dept_stats = $conn->query("SELECT d.name, COUNT(t.id) as count 
                                        FROM departments d 
                                        LEFT JOIN users u ON d.id = u.department_id AND u.role = 'student'
                                        LEFT JOIN theses t ON u.id = t.student_id 
                                        GROUP BY d.id");
            $dept_labels = []; $dept_counts = [];
            if ($dept_stats) {
                while($row = $dept_stats->fetch_assoc()){
                    $dept_labels[] = $row['name'];
                    $dept_counts[] = $row['count'];
                }
            }

            // Fetch monthly submission trend (last 6 months)
            $monthly_data = $conn->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month, 
                                           COUNT(*) as count,
                                           SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_count,
                                           SUM(CASE WHEN status IN ('pending','under_review') THEN 1 ELSE 0 END) as pending_count
                                           FROM theses 
                                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                                           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                           ORDER BY MIN(created_at)");
            $month_labels = []; $month_totals = []; $month_approved = []; $month_pending = [];
            if ($monthly_data) {
                while($row = $monthly_data->fetch_assoc()){
                    $month_labels[] = $row['month'];
                    $month_totals[] = $row['count'];
                    $month_approved[] = $row['approved_count'];
                    $month_pending[] = $row['pending_count'];
                }
            }
            ?>

            <!-- Charts Row -->
            <div class="dashboard-charts-grid">
                <!-- Chart 1: Thesis Submissions Overview (like Revenue & Financial Overview) -->
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-header">
                        <div class="dashboard-chart-title">
                            <i class="fa-solid fa-chart-column"></i>
                            Submissions Overview
                        </div>
                        <div class="dashboard-chart-legends">
                            <span class="dashboard-chart-legend-item">
                                <span class="dashboard-chart-legend-dot" style="background:#6366f1;"></span> Total
                            </span>
                            <span class="dashboard-chart-legend-item">
                                <span class="dashboard-chart-legend-dot" style="background:#22c55e;"></span> Approved
                            </span>
                            <span class="dashboard-chart-legend-item">
                                <span class="dashboard-chart-legend-dot" style="background:#f97316;"></span> Pending
                            </span>
                        </div>
                    </div>
                    <div class="dashboard-chart-body">
                        <canvas id="adminSubmissionsChart"></canvas>
                    </div>
                </div>

                <!-- Chart 2: Department Activity (like Tickets & Rides Activity) -->
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-header">
                        <div class="dashboard-chart-title">
                            <i class="fa-solid fa-chart-bar"></i>
                            Department Activity
                        </div>
                        <div class="dashboard-chart-legends">
                            <span class="dashboard-chart-legend-item">
                                <span class="dashboard-chart-legend-dot" style="background:#38bdf8;"></span> Submissions
                            </span>
                            <span class="dashboard-chart-legend-item">
                                <span class="dashboard-chart-legend-dot" style="background:#eab308;"></span> Departments
                            </span>
                        </div>
                    </div>
                    <div class="dashboard-chart-body">
                        <canvas id="adminDeptChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Info Grid: Recent Submissions + Quick Insights -->
            <div class="dashboard-info-grid">
                <!-- Recent Thesis Submissions Table -->
                <div class="dashboard-recent-card" style="margin-top: 0;">
                    <div class="dashboard-recent-header">
                        <h3><i class="fa-solid fa-file-circle-check" style="color:#38bdf8;"></i> Recent Submissions</h3>
                        <a href="admin/all_theses.php">View All →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recent = $conn->query("SELECT t.*, u.full_name FROM theses t JOIN users u ON t.student_id = u.id ORDER BY t.created_at DESC LIMIT 5");
                                if($recent && $recent->num_rows > 0):
                                    while($row = $recent->fetch_assoc()): 
                                        $status_class = '';
                                        $status_bg = '';
                                        switch($row['status']) {
                                            case 'approved': $status_bg = 'rgba(34,197,94,0.1)'; $status_color = '#22c55e'; break;
                                            case 'pending': $status_bg = 'rgba(234,179,8,0.1)'; $status_color = '#eab308'; break;
                                            case 'under_review': $status_bg = 'rgba(56,189,248,0.1)'; $status_color = '#38bdf8'; break;
                                            case 'rejected': $status_bg = 'rgba(239,68,68,0.1)'; $status_color = '#ef4444'; break;
                                            case 'revision_required': $status_bg = 'rgba(168,85,247,0.1)'; $status_color = '#a855f7'; break;
                                            default: $status_bg = 'rgba(100,116,139,0.1)'; $status_color = '#64748b';
                                        }
                                ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($row['full_name']); ?></b></td>
                                    <td><?php echo htmlspecialchars($row['domain']); ?></td>
                                    <td>
                                        <span class="status-badge" style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>;">
                                            <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                        </span>
                                    </td>
                                    <td style="color:#64748b; font-size:13px;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No submissions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Insights Panel -->
                <div class="dashboard-quick-panel" style="margin-top: 0;">
                    <div class="panel-title"><i class="fa-solid fa-bolt" style="color:#eab308;"></i> Quick Insights</div>
                    
                    <div class="dashboard-insight-box insight-blue">
                        <h4>System Activity</h4>
                        <p><?php echo $stats['theses']; ?> total thesis submissions in the system across <?php echo $stats['departments']; ?> departments.</p>
                    </div>

                    <div class="dashboard-insight-box insight-green">
                        <h4>Approval Rate</h4>
                        <p>
                            <?php 
                            $rate = $stats['theses'] > 0 ? round(($stats['approved'] / $stats['theses']) * 100) : 0;
                            echo $rate . "% of submitted theses have been approved.";
                            ?>
                        </p>
                    </div>

                    <div class="dashboard-insight-box insight-orange">
                        <h4>Pending Queue</h4>
                        <p><?php echo $stats['pending']; ?> theses are currently awaiting supervisor review.</p>
                    </div>

                    <div class="dashboard-insight-box insight-purple">
                        <h4>Research Domains</h4>
                        <p><?php echo $stats['domains']; ?> unique research domains are being explored.</p>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Chart 1: Submissions Overview (Mixed bar chart like Revenue & Financial)
                const subCtx = document.getElementById('adminSubmissionsChart').getContext('2d');
                const monthLabels = <?php echo json_encode($month_labels); ?>;
                const hasMonthlyData = monthLabels.length > 0;
                
                new Chart(subCtx, {
                    type: 'bar',
                    data: {
                        labels: hasMonthlyData ? monthLabels : <?php echo json_encode($labels); ?>,
                        datasets: hasMonthlyData ? [
                            {
                                label: 'Total',
                                data: <?php echo json_encode($month_totals); ?>,
                                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            },
                            {
                                label: 'Approved',
                                data: <?php echo json_encode($month_approved); ?>,
                                backgroundColor: 'rgba(34, 197, 94, 0.85)',
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            },
                            {
                                label: 'Pending',
                                data: <?php echo json_encode($month_pending); ?>,
                                backgroundColor: 'rgba(249, 115, 22, 0.85)',
                                borderRadius: 6,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            }
                        ] : [{
                            label: 'Count',
                            data: <?php echo json_encode($counts); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>,
                            borderRadius: 6,
                            barPercentage: 0.5
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
                                displayColors: true,
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
                        },
                        interaction: { intersect: false, mode: 'index' }
                    }
                });

                // Chart 2: Department Activity (Grouped bar chart like Tickets & Rides)
                const deptCtx = document.getElementById('adminDeptChart').getContext('2d');
                new Chart(deptCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($dept_labels); ?>,
                        datasets: [{
                            label: 'Submissions',
                            data: <?php echo json_encode($dept_counts); ?>,
                            backgroundColor: [
                                'rgba(56, 189, 248, 0.85)',
                                'rgba(234, 179, 8, 0.85)',
                                'rgba(168, 85, 247, 0.85)',
                                'rgba(34, 197, 94, 0.85)',
                                'rgba(249, 115, 22, 0.85)',
                                'rgba(239, 68, 68, 0.85)'
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
                                ticks: { color: '#64748b', font: { family: 'Outfit', size: 11 }, maxRotation: 45, minRotation: 0 },
                                border: { display: false }
                            }
                        }
                    }
                });
            });
            </script>

        <?php elseif ($role == 'supervisor'): ?>
            <?php
            // Redirect to the new dedicated supervisor dashboard
            echo "<script>window.location.href = 'supervisor/dashboard.php';</script>";
            exit();
            ?>

        <?php elseif ($role == 'student'): ?>
            <!-- STUDENT DASHBOARD - FunFairERP Style -->
            <?php
            $q_my_thesis = $conn->query("SELECT COUNT(*) as c FROM theses WHERE student_id='$user_id'");
            $q_my_approved = $conn->query("SELECT COUNT(*) as c FROM theses WHERE student_id='$user_id' AND status='approved'");
            $q_my_pending = $conn->query("SELECT COUNT(*) as c FROM theses WHERE student_id='$user_id' AND status IN ('pending','under_review')");
            
            $student_stats = [
                'total' => ($q_my_thesis) ? $q_my_thesis->fetch_assoc()['c'] : 0,
                'approved' => ($q_my_approved) ? $q_my_approved->fetch_assoc()['c'] : 0,
                'pending' => ($q_my_pending) ? $q_my_pending->fetch_assoc()['c'] : 0,
            ];
            ?>

            <div class="dashboard-welcome">
                <h2>Dashboard Overview</h2>
                <p>Welcome back, <?php echo htmlspecialchars($full_name); ?>. Track your thesis progress here.</p>
            </div>

            <div class="dashboard-stats-grid">
                <div class="dashboard-stat-card card-blue card-link" onclick="window.location='student/my_thesis.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $student_stats['total']; ?></div>
                        <div class="dashboard-stat-label">My Submissions</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card card-green card-link" onclick="window.location='student/my_thesis.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $student_stats['approved']; ?></div>
                        <div class="dashboard-stat-label">Approved</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                </div>

                <div class="dashboard-stat-card card-orange card-link" onclick="window.location='student/my_thesis.php'">
                    <div class="dashboard-stat-content">
                        <div class="dashboard-stat-number"><?php echo $student_stats['pending']; ?></div>
                        <div class="dashboard-stat-label">Under Review</div>
                    </div>
                    <div class="dashboard-stat-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>

            <!-- Student's Thesis Details -->
            <div class="dashboard-recent-card">
                <div class="dashboard-recent-header">
                    <h3><i class="fa-solid fa-book" style="color:#a855f7;"></i> Your Thesis Status</h3>
                    <a href="student/my_thesis.php">View Details →</a>
                </div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Domain</th>
                                <th>Status</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_theses = $conn->query("SELECT * FROM theses WHERE student_id='$user_id' ORDER BY updated_at DESC");
                            if($my_theses && $my_theses->num_rows > 0):
                                while($row = $my_theses->fetch_assoc()):
                                    $status_bg = ''; $status_color = '';
                                    switch($row['status']) {
                                        case 'approved': $status_bg = 'rgba(34,197,94,0.1)'; $status_color = '#22c55e'; break;
                                        case 'pending': $status_bg = 'rgba(234,179,8,0.1)'; $status_color = '#eab308'; break;
                                        case 'under_review': $status_bg = 'rgba(56,189,248,0.1)'; $status_color = '#38bdf8'; break;
                                        case 'rejected': $status_bg = 'rgba(239,68,68,0.1)'; $status_color = '#ef4444'; break;
                                        case 'revision_required': $status_bg = 'rgba(168,85,247,0.1)'; $status_color = '#a855f7'; break;
                                        default: $status_bg = 'rgba(100,116,139,0.1)'; $status_color = '#64748b';
                                    }
                            ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars(substr($row['title'], 0, 50)); ?><?php echo strlen($row['title']) > 50 ? '...' : ''; ?></b></td>
                                <td><?php echo htmlspecialchars($row['domain']); ?></td>
                                <td>
                                    <span class="status-badge" style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>;">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </td>
                                <td style="color:#64748b; font-size:13px;"><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px; color: #64748b;">
                                    <i class="fa-solid fa-inbox" style="font-size:28px; margin-bottom:10px; display:block; color:#38bdf8;"></i>
                                    No thesis submitted yet. Use the sidebar to submit your proposal.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php require_once 'includes/footer.php'; ?>
</main>
</div> <!-- Close app-container -->
</body>
</html>
