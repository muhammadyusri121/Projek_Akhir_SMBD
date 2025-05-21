<?php
require_once '../config/init.php';

// Destroy session and redirect to login page
session_destroy();
redirect('../auth/login.php');
?>
