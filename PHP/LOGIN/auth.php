<?php
session_start();
include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    

    $sql = "SELECT * FROM $role WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Set user_id based on role
            if ($role === 'eo') {
                $_SESSION['user_id'] = $user['id_eo'];
            } elseif ($role === 'admin') {
                $_SESSION['user_id'] = $user['id_admin'];
            } elseif ($role === 'player') {
                $_SESSION['user_id'] = $user['id_player'];
            }

            $updateStatusSql = "UPDATE $role SET status = 'active' WHERE username = '$username'";
            $conn->query($updateStatusSql);
            if ($role === 'player') {
                header('Location: ../../index.php');
            } elseif ($role === 'eo') {
                header('Location: ../EO/dashboardEO.php');
            } elseif ($role === 'admin') {
                header('Location: ../ADMIN/dashboardAdmin.php');
            } else {
                echo "<script>alert('Role tidak sesuai'); window.location.href = 'login.php';</script>";
                session_unset(); 
                session_destroy();  
                exit();
            }
            exit(); // Add exit after successful login
        } else {
            echo "<script>alert('Password salah'); window.location.href = 'login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Username atau password salah'); window.location.href = 'login.php';</script>";
        exit();
    }
}
?>