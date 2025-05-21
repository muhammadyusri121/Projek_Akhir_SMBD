<?php
// Start session
session_start();

// Include database connection
require_once 'database.php';

// Include helper functions
require_once 'helpers.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to display error message
function showError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Function to display success message
function showSuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}

// Function to get current page
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

// Set error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    
    $level = 'ERROR';
    switch ($errno) {
        case E_WARNING:
        case E_USER_WARNING:
            $level = 'WARNING';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $level = 'NOTICE';
            break;
    }
    
    logError($errstr, $level, $errfile, $errline);
    
    // Don't execute PHP internal error handler
    return true;
});

// Set exception handling
set_exception_handler(function($exception) {
    logError($exception->getMessage(), 'EXCEPTION', $exception->getFile(), $exception->getLine());
    
    // Display error message to user
    echo showError("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
});
?>
