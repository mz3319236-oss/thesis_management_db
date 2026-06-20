<footer style="background: #0f172a; padding: 40px 30px; margin-top: auto; color:var(--text-main); border-top: 1px solid #1e293b;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-bottom: 30px;">
        <!-- Column 1 -->
        <div>
            <h4 style="color: #ffffff; font-size: 16px; margin-bottom: 15px; font-weight: 600; text-transform: uppercase;">Thesis Pro</h4>
            <ul style="list-style: none; padding: 0;">
                <?php $role = $_SESSION['role'] ?? 'student'; ?>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?><?php echo $role; ?>/dashboard.php" style="color: #38bdf8; text-decoration: none; font-size: 14px; transition: 0.3s;">Dashboard</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?><?php echo ($role=='student')?'student/my_thesis.php':'admin/all_theses.php'; ?>" style="color: #38bdf8; text-decoration: none; font-size: 14px; transition: 0.3s;">Manage Theses</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>student/submit_proposal.php" style="color: #38bdf8; text-decoration: none; font-size: 14px; transition: 0.3s;">Submit Proposal</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>supervisor/pending_requests.php" style="color: #38bdf8; text-decoration: none; font-size: 14px; transition: 0.3s;">Review Operations</a></li>
            </ul>
        </div>
        
        <!-- Column 2 -->
        <div>
            <h4 style="color: #ffffff; font-size: 16px; margin-bottom: 15px; font-weight: 600; text-transform: uppercase;">Administration</h4>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>admin/reports.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">System Reports</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>admin/manage_users.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">User Management</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>admin/permissions.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Role Permissions</a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo $baseUrl; ?>admin/manage_departments.php" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Departments Setup</a></li>
            </ul>
        </div>

        <!-- Column 3 -->
        <div>
            <h4 style="color: #ffffff; font-size: 16px; margin-bottom: 15px; font-weight: 600; text-transform: uppercase;">Help & Support</h4>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 8px;"><a href="#" style="color: #38bdf8; text-decoration: none; font-size: 14px;">User Manual</a></li>
                <li style="margin-bottom: 8px;"><a href="#" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Contact Administrator</a></li>
                <li style="margin-bottom: 8px;"><a href="#" style="color: #38bdf8; text-decoration: none; font-size: 14px;">System FAQs</a></li>
                <li style="margin-bottom: 8px;"><a href="#" style="color: #38bdf8; text-decoration: none; font-size: 14px;">Report an Issue</a></li>
            </ul>
        </div>

        <!-- Column 4 -->
        <div style="text-align: right;">
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-bottom: 15px;">
                <i class="fa-solid fa-graduation-cap" style="color: #818cf8; font-size: 24px;"></i>
                <h3 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 600;">Thesis Pro</h3>
            </div>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 5px;">v2.4.0 <span style="color:#64748b;">System Version</span></p>
            <p style="font-size: 13px;"><span style="color: #22c55e;">Online & Secure</span> <span style="color:#64748b;">System Status</span></p>
        </div>
    </div>
    
    <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="#" style="color: #38bdf8; text-decoration: none; font-size: 13px; margin-right: 10px;">Privacy Policy</a>
            <a href="#" style="color: #38bdf8; text-decoration: none; font-size: 13px; margin-right: 10px;">Terms of Service</a>
            <a href="#" style="color: #38bdf8; text-decoration: none; font-size: 13px; margin-right: 10px;">License Info</a>
            <a href="#" style="color: #38bdf8; text-decoration: none; font-size: 13px;">Sitemap</a>
        </div>
        <div style="text-align: right; color: #94a3b8; font-size: 13px;">
            <p>Access is authenticated and securely monitored.</p>
            <p>&copy; <?php echo date('Y'); ?> Thesis Pro. All rights reserved.</p>
        </div>
    </div>
</footer>
