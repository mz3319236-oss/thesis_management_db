<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

// Only student or admin allowed
if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';
$student_id = $_SESSION['user_id'];

// Get all supervisors for dropdown
$supervisors = [];
$sup_q = $conn->query("SELECT id, full_name, email FROM users WHERE role='supervisor'");
while($row = $sup_q->fetch_assoc()) {
    $supervisors[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $abstract = $conn->real_escape_string($_POST['abstract']);
    $domain = $conn->real_escape_string($_POST['domain']);
    $supervisor_id = $conn->real_escape_string($_POST['supervisor_id']);
    
    // Check if the student already submitted a pending or approved thesis
    $check_dup = $conn->query("SELECT id FROM theses WHERE student_id='$student_id' AND status IN ('pending', 'under_review', 'approved')");
    
    if ($check_dup->num_rows > 0) {
        $error = "You already have an active thesis proposal in the system!";
    } else {
        // Insert into database without file
        $sql = "INSERT INTO theses (student_id, supervisor_id, title, abstract, domain, document_path) 
                VALUES ('$student_id', '$supervisor_id', '$title', '$abstract', '$domain', NULL)";
                
        if ($conn->query($sql) === TRUE) {
            $success = "Project proposal submitted successfully! Once the supervisor accepts it, you will be able to upload your project files.";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>
<?php require_once '../includes/sidebar.php'; ?>
<main class="main-content">
    <?php require_once '../includes/navbar.php'; ?>
    <div class="content-area">
        <h2 style="margin-bottom: 25px; color: var(--text-heading);">Submit Project Proposal</h2>

        <div class="data-table-card" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header">
                <h3>New Project Application</h3>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="dashboard-form" style="border:none; padding:10px;">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" required placeholder="Enter your project title (Max 250 characters)" maxlength="250">
                </div>
                
                <div class="form-group">
                    <label>Project Description</label>
                    <textarea name="abstract" required placeholder="Write a detailed description of your project..." style="min-height: 150px;"></textarea>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Research Domain / Area</label>
                        <select name="domain" required>
                            <option value="">Select Domain</option>
                            <option value="Artificial Intelligence">Artificial Intelligence</option>
                            <option value="Cyber Security">Cyber Security</option>
                            <option value="Software Engineering">Software Engineering</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Web Development">Web Development</option>
                            <option value="Mobile App Development">Mobile App Development</option>
                            <option value="Internet of Things (IoT)">Internet of Things (IoT)</option>
                            <option value="Cloud Computing">Cloud Computing</option>
                            <option value="Blockchain">Blockchain</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Supervisor</label>
                        <select name="supervisor_id" required>
                            <option value="">Choose a Supervisor</option>
                            <?php foreach($supervisors as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['full_name']); ?> (<?php echo htmlspecialchars($sup['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="text-align: right; margin-top:20px;">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Proposal</button>
                </div>
            </form>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
</main>
</div>
</body>
</html>
