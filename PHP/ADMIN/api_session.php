<?php
// API session check - tidak melakukan redirect otomatis
session_start();
include '../connect.php';

$timeout_duration = 1 * 60;

function checkAPISession() {
    $timeout_duration = 1 * 60; // Define timeout within function
    
    if (isset($_SESSION['last_activity'])) {
        $inactive_duration = time() - $_SESSION['last_activity'];

        if ($inactive_duration > $timeout_duration) {
            // Check if session variables exist before using them
            if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
                $username = $_SESSION['username'];
                $role = $_SESSION['role'];
                
                // Only update status if role is valid (not empty)
                if (!empty($role)) {
                    global $conn;
                    $updateStatusSql = "UPDATE $role SET status = 'inactive' WHERE username = ?";
                    $stmt = $conn->prepare($updateStatusSql);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            session_unset(); 
            session_destroy();
            return false;
        }
    }

    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        return false;
    }

    // Check if user has admin access
    $role = $_SESSION['role'];
    if ($role !== 'admin') {
        return false;
    }

    return true;
}

// Check session but return JSON error instead of redirect
if (!checkAPISession()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Unauthorized access or session expired'
    ]);
    exit();
}

// Add no-cache headers
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
