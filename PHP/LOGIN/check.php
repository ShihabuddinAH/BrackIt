<?php
include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "All fields are required!";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Check password confirmation
    if ($password !== $confirm_password) {
        $error = "Password and confirmation password do not match!";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Check password length
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Hash password
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if role is valid
        if (!in_array($role, ['player', 'eo'])) {
            throw new Exception("Invalid role selected!");
        }

        if ($role === 'player') {
            // Player registration
            $nickname = trim($_POST['nickname']);
            $idGame = trim($_POST['idGame']);

            if (empty($nickname) || empty($idGame)) {
                throw new Exception("Nickname and Game ID are required for players!");
            }

            // Check if username already exists in player table
            $sql_check = "SELECT username FROM player WHERE username = ?";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception("Username already exists. Please choose another username.");
            }

            // Check if email already exists in player table
            $sql_check_email = "SELECT email FROM player WHERE email = ?";
            $stmt = $conn->prepare($sql_check_email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception("Email already exists. Please use another email.");
            }

            // Insert into player table
            $sql_insert = "INSERT INTO player (username, email, password, nickname, idGame, role, status) 
                           VALUES (?, ?, ?, ?, ?, 'player', 'inactive')";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("sssss", $username, $email, $password_hashed, $nickname, $idGame);

        } else if ($role === 'eo') {
            // EO registration
            $organisasi = trim($_POST['organisasi']);

            if (empty($organisasi)) {
                throw new Exception("Organization name is required for Event Organizers!");
            }

            // Check if username already exists in eo table
            $sql_check = "SELECT username FROM eo WHERE username = ?";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception("Username already exists. Please choose another username.");
            }

            // Check if email already exists in eo table
            $sql_check_email = "SELECT email FROM eo WHERE email = ?";
            $stmt = $conn->prepare($sql_check_email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception("Email already exists. Please use another email.");
            }

            // Insert into eo table
            $sql_insert = "INSERT INTO eo (username, email, password, organisasi, role, status) 
                           VALUES (?, ?, ?, ?, 'eo', 'inactive')";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("ssss", $username, $email, $password_hashed, $organisasi);
        }

        if ($stmt->execute()) {
            $success = "Account created successfully! Please login with your credentials.";
            header("Location: login.php?success=" . urlencode($success));
            exit();
        } else {
            throw new Exception("Failed to create account. Please try again.");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        header("Location: register.php?error=" . urlencode($error));
        exit();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }
}
?>