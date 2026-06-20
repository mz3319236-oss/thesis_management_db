<?php
// Function to get icon based on page name
function getPageIcon($name) {
    $icons = [
        'Dashboard' => 'fa-gauge',
        'All Theses' => 'fa-copy',
        'Manage Users' => 'fa-users',
        'External Examiners' => 'fa-user-tie',
        'Manage Departments' => 'fa-building',
        'Reports' => 'fa-chart-line',
        'Re-assign Thesis' => 'fa-handshake',
        'Permissions' => 'fa-user-lock',
        'Assign Project' => 'fa-plus-circle',
        'Guidelines' => 'fa-folder-tree',
        'Deadlines' => 'fa-calendar-alt',
        'Audit History' => 'fa-clock-rotate-left'
    ];
    return $icons[$name] ?? 'fa-circle-dot';
}

// Helper to check if any sub-item is active
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fa-solid fa-graduation-cap"></i> Thesis Pro</h2>
    </div>
    
    <div class="user-info">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?></div>
        <div class="details">
            <h4><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h4>
            <p><?php echo ucfirst($_SESSION['role'] ?? 'Role'); ?></p>
        </div>
    </div>

    <ul class="nav-links">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <!-- Dashboard (standalone) -->
            <li class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>

            <!-- Thesis Management Group -->
            <?php $thesisGroupActive = in_array($currentPage, ['all_theses.php', 'assign_new_project.php', 'assign_thesis.php']); ?>
            <li class="nav-group <?php echo $thesisGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-book-open" style="color:#38bdf8;"></i> Thesis Management</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'all_theses.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/all_theses.php"><i class="fa-solid fa-copy"></i> All Theses</a>
                    </li>
                    <li class="<?php echo $currentPage == 'assign_new_project.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/assign_new_project.php"><i class="fa-solid fa-plus-circle"></i> Assign Project</a>
                    </li>
                    <li class="<?php echo $currentPage == 'assign_thesis.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/assign_thesis.php"><i class="fa-solid fa-handshake"></i> Re-assign Thesis</a>
                    </li>
                </ul>
            </li>

            <!-- User Management Group -->
            <?php $userGroupActive = in_array($currentPage, ['manage_users.php', 'manage_examiners.php', 'permissions.php']); ?>
            <li class="nav-group <?php echo $userGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-users-gear" style="color:#a855f7;"></i> User Management</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'manage_users.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_users.php"><i class="fa-solid fa-users"></i> Users</a>
                    </li>
                    <li class="<?php echo $currentPage == 'manage_examiners.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_examiners.php"><i class="fa-solid fa-user-tie"></i> External Examiners</a>
                    </li>
                    <li class="<?php echo $currentPage == 'permissions.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/permissions.php"><i class="fa-solid fa-user-lock"></i> Permissions</a>
                    </li>
                </ul>
            </li>

            <!-- Academic Group -->
            <?php $academicGroupActive = in_array($currentPage, ['manage_departments.php', 'view_department.php', 'manage_resources.php', 'manage_deadlines.php']); ?>
            <li class="nav-group <?php echo $academicGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-landmark" style="color:#22c55e;"></i> Academic</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'manage_departments.php' || $currentPage == 'view_department.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_departments.php"><i class="fa-solid fa-building"></i> Departments</a>
                    </li>
                    <li class="<?php echo $currentPage == 'manage_resources.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_resources.php"><i class="fa-solid fa-folder-tree"></i> Guidelines</a>
                    </li>
                    <li class="<?php echo $currentPage == 'manage_deadlines.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_deadlines.php"><i class="fa-solid fa-calendar-alt"></i> Deadlines</a>
                    </li>
                </ul>
            </li>

            <!-- Reports Group -->
            <?php $reportsGroupActive = in_array($currentPage, ['reports.php', 'history.php']); ?>
            <li class="nav-group <?php echo $reportsGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-chart-pie" style="color:#f97316;"></i> Reports</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>
                    </li>
                    <li class="<?php echo $currentPage == 'history.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/history.php"><i class="fa-solid fa-clock-rotate-left"></i> Audit History</a>
                    </li>
                </ul>
            </li>
        
        <?php elseif ($_SESSION['role'] == 'supervisor'): ?>
            <!-- Dashboard -->
            <li class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>

            <!-- Supervision Group -->
            <?php $supGroupActive = in_array($currentPage, ['manage_classes.php', 'view_class.php', 'pending_requests.php', 'my_students.php', 'assign_new_project.php']); ?>
            <li class="nav-group <?php echo $supGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-chalkboard-user" style="color:#38bdf8;"></i> Supervision</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'manage_classes.php' || $currentPage == 'view_class.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>supervisor/manage_classes.php"><i class="fa-solid fa-users-rectangle"></i> Manage Classes</a>
                    </li>
                    <li class="<?php echo $currentPage == 'pending_requests.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>supervisor/pending_requests.php"><i class="fa-solid fa-clock"></i> Pending Requests</a>
                    </li>
                    <li class="<?php echo $currentPage == 'my_students.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>supervisor/my_students.php"><i class="fa-solid fa-list-check"></i> My Assigned Thesis</a>
                    </li>
                    <li class="<?php echo $currentPage == 'assign_new_project.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>supervisor/assign_new_project.php"><i class="fa-solid fa-paper-plane"></i> Assign Project</a>
                    </li>
                </ul>
            </li>

            <!-- Resources Group -->
            <?php $resGroupActive = in_array($currentPage, ['manage_resources.php', 'manage_deadlines.php']); ?>
            <li class="nav-group <?php echo $resGroupActive ? 'open' : ''; ?>">
                <div class="nav-group-toggle" onclick="this.parentElement.classList.toggle('open')">
                    <span><i class="fa-solid fa-book-bookmark" style="color:#22c55e;"></i> Resources</span>
                    <i class="fa-solid fa-chevron-right nav-chevron"></i>
                </div>
                <ul class="nav-sublinks">
                    <li class="<?php echo $currentPage == 'manage_resources.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_resources.php"><i class="fa-solid fa-folder-tree"></i> Guidelines</a>
                    </li>
                    <li class="<?php echo $currentPage == 'manage_deadlines.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?>admin/manage_deadlines.php"><i class="fa-solid fa-calendar-alt"></i> Deadlines</a>
                    </li>
                </ul>
            </li>

            <!-- Dynamic Admin Pages for Supervisor -->
            <?php 
            $res = $conn->query("SELECT * FROM role_permissions WHERE supervisor = 1 AND file_path NOT LIKE 'supervisor/%' AND file_path NOT IN ('admin/dashboard.php', 'admin/assign_new_project.php', 'admin/manage_deadlines.php', 'admin/manage_resources.php', 'auth/deadlines.php', 'auth/resources.php')");
            if ($res):
                while($p = $res->fetch_assoc()): ?>
                    <li class="<?php echo $currentPage == basename($p['file_path']) ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?><?php echo $p['file_path']; ?>"><i class="fa-solid <?php echo getPageIcon($p['page_name']); ?>"></i> <?php echo $p['page_name']; ?></a>
                    </li>
                <?php endwhile;
            endif; ?>

        <?php elseif ($_SESSION['role'] == 'student'): ?>
            <li class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
            <li class="<?php echo $currentPage == 'my_thesis.php' ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>student/my_thesis.php"><i class="fa-solid fa-book"></i> My Thesis Status</a>
            </li>
            
            <!-- Dynamic Permitted Admin Pages for Student -->
            <?php 
            $res = $conn->query("SELECT * FROM role_permissions WHERE student = 1 AND file_path NOT LIKE 'student/%' AND file_path != 'admin/dashboard.php'");
            if ($res):
                while($p = $res->fetch_assoc()): ?>
                    <li class="<?php echo $currentPage == basename($p['file_path']) ? 'active' : ''; ?>">
                        <a href="<?php echo $baseUrl; ?><?php echo $p['file_path']; ?>"><i class="fa-solid <?php echo getPageIcon($p['page_name']); ?>"></i> <?php echo $p['page_name']; ?></a>
                    </li>
                <?php endwhile;
            endif; ?>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <a href="<?php echo $baseUrl; ?>auth/profile.php" class="logout-btn" style="background:rgba(56,189,248,0.1); color:#38bdf8; margin-bottom:10px;"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="<?php echo $baseUrl; ?>auth/logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>
