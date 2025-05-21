<?php
require_once 'config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Redirect to dashboard
redirect('dashboard.php');
?>
