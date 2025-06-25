<?php
function verifySession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Check if user is logged in (using either method)
    if (!isset($_SESSION['logged_in']) && !isset($_SESSION['email'])) {
        header("Location: login.php");
        exit();
    }
    
    // Determine user type based on available session variables
    $userType = 'student'; // Default to student
    if (isset($_SESSION['user_type'])) {
        $userType = $_SESSION['user_type'];
    } elseif (isset($_SESSION['employee_id'])) {
        $userType = 'faculty';
    }
    
    return [
        'type' => $userType,
        'email' => $_SESSION['email'] ?? '',
        'id' => $userType === 'student' ? ($_SESSION['usn'] ?? '') : ($_SESSION['employee_id'] ?? ''),
        'name' => $_SESSION['name'] ?? ''
    ];
}