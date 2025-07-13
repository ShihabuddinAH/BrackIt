<?php
session_start();
include '../connect.php';

// Check if user is logged in
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Update user status to 'inactive' based on role
    if (!empty($role)) {
        $updateStatusSql = "UPDATE $role SET status = 'inactive' WHERE username = ?";
        $stmt = $conn->prepare($updateStatusSql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
    }
}

// Clear all session variables
session_unset();
// Destroy the session
session_destroy();

// Send JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
    exit();
}

// For normal requests, redirect to login page
header('Location: ../../index.php');
exit();
?>