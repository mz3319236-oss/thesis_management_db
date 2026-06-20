<?php
function syncPermissions($conn) {
    // 0. Ensure table exists with file_path
    $createTable = "CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(100),
        file_path VARCHAR(255) UNIQUE,
        student TINYINT(1) DEFAULT 0,
        supervisor TINYINT(1) DEFAULT 0,
        admin TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($createTable);

    $directories = [
        'admin' => ['admin'],
        'supervisor' => ['supervisor'],
        'student' => ['student'],
        'auth' => ['student', 'supervisor', 'admin']
    ];

    $ignore_files = [
        'login.php', 'logout.php', 'register.php', 'mark_read.php', 'db_connect.php', 
        'header.php', 'footer.php', 'sidebar.php', 'navbar.php', 'fix_table.php', 
        'cleanup.php', 'add_versioning.php', 'restore_original.php', 'add_examiners_table.php', 
        'add_log_table.php', 'drop_perm.php', 'fix_dashboard.php', 'functions.php', 'init.php'
    ];
    
    $found_files = [];
    foreach ($directories as $dir => $default_roles) {
        $path = __DIR__ . "/../$dir";
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'php' && !in_array($file, $ignore_files)) {
                    $file_rel_path = "$dir/$file";
                    $found_files[] = $file_rel_path;
                    $pure_title = ucwords(str_replace(['_', '.php'], [' ', ''], $file));
                    
                    $stmt = $conn->prepare("SELECT id FROM role_permissions WHERE file_path = ?");
                    if ($stmt) {
                        $stmt->bind_param("s", $file_rel_path);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows == 0) {
                            $is_admin = in_array('admin', $default_roles) ? 1 : 0;
                            $is_super = in_array('supervisor', $default_roles) ? 1 : 0;
                            $is_stud  = in_array('student', $default_roles) ? 1 : 0;
                            
                            $insert = $conn->prepare("INSERT INTO role_permissions (page_name, file_path, student, supervisor, admin) VALUES (?, ?, ?, ?, ?)");
                            if($insert) {
                                $insert->bind_param("ssiii", $pure_title, $file_rel_path, $is_stud, $is_super, $is_admin);
                                $insert->execute();
                            }
                        } else {
                            $update = $conn->prepare("UPDATE role_permissions SET page_name = ? WHERE file_path = ?");
                            if($update) {
                                $update->bind_param("ss", $pure_title, $file_rel_path);
                                $update->execute();
                            }
                        }
                    }
                }
            }
        }
    }

    $q = $conn->query("SELECT id, file_path FROM role_permissions");
    if($q) {
        while ($row = $q->fetch_assoc()) {
            if (!empty($row['file_path']) && !in_array($row['file_path'], $found_files)) {
                $id = $row['id'];
                $conn->query("DELETE FROM role_permissions WHERE id = $id");
            }
        }
    }
}

function checkPagePermission($conn) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['role'])) return;
    $current_file = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $file_path = "$current_dir/$current_file";
    
    $always_allowed = ['login.php', 'logout.php', 'register.php', 'index.php'];
    if (in_array($current_file, $always_allowed)) return;

    $role = $_SESSION['role'];
    
    // Explicitly allow supervisor to access deadlines and guidelines
    if ($role === 'supervisor' && in_array($file_path, ['admin/manage_deadlines.php', 'admin/manage_resources.php'])) {
        return; // Bypass database check and allow
    }

    $stmt = $conn->prepare("SELECT $role FROM role_permissions WHERE file_path = ?");
    if ($stmt) {
        $stmt->bind_param("s", $file_path);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row[$role] == 0) {
                $redirect_path = ($role === 'supervisor') ? '../supervisor/dashboard.php' : '../admin/dashboard.php';
                echo "<script>alert('You do not have permission to access $current_file'); window.location.href='$redirect_path';</script>";
                exit();
            }
        }
    }
}
