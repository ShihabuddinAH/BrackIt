<?php
// This file should only be included by protected pages (admin/eo dashboards)
session_start();
include '../connect.php';

$timeout_duration = 1 * 60;

if (isset($_SESSION['last_activity'])) {
    $inactive_duration = time() - $_SESSION['last_activity'];

    if ($inactive_duration > $timeout_duration) {
        // Check if session variables exist before using them
        if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
            $username = $_SESSION['username'];
            $role = $_SESSION['role'];
            
            // Only update status if role is valid (not empty)
            if (!empty($role)) {
                $updateStatusSql = "UPDATE $role SET status = 'inactive' WHERE username = ?";
                $stmt = $conn->prepare($updateStatusSql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        session_unset(); 
        session_destroy();

        // Redirect to login page since session expired
        header('Location: ../../index.php');
        exit();
        
    }
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: ../../index.php');
    exit();
}

// Redirect to appropriate dashboard based on role
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Only redirect if not already on the correct page
if ($role === 'admin' && $current_page !== 'dashboardAdmin.php') {
    header('Location: ../ADMIN/dashboardAdmin.php');
    exit();
} elseif ($role === 'eo' && $current_page !== 'dashboardEO.php') {
    header('Location: ../EO/dashboardEO.php');
    exit();
} elseif ($role === 'player') {
    // Players should not access admin/eo dashboards, redirect to main page
    header('Location: ../../index.php');
    exit();
}

// Tambahkan header no-cache
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
?>
