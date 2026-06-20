<?php
// Redirect users to login page immediately when they visit the main URL
header("Location: auth/login.php");
exit();
?>
