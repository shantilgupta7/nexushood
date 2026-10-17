<?php
session_start();
session_destroy();  // Destroy the session
header("Location: adm_home.php");  // Redirect to login page
exit();
?>