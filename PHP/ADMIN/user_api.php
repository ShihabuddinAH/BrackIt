<?php
include 'api_session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userType = $_GET['type'] ?? $_POST['type'] ?? '';

switch ($method) {
    case 'POST':
        addUser($conn, $userType);
        break;
    case 'PUT':
        updateUser($conn, $userType);
        break;
    case 'DELETE':
        deleteUser($conn, $userType);
        break;
    case 'GET':
        $action = $_GET['action'] ?? '';
        if (isset($_GET['id'])) {
            getUser($conn, $userType, $_GET['id']);
        } elseif ($action === 'getAll' || empty($action)) {
            getAllUsers($conn, $userType);
        } else {
            getAllUsers($conn, $userType);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function addUser($conn, $userType) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'status'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field $field is required");
            }
        }
        
        // Validate passwords match
        if ($data['password'] !== $data['confirmPassword']) {
            throw new Exception("Passwords do not match");
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Prepare data based on user type
        switch ($userType) {
            case 'admin':
                $query = "INSERT INTO admin (username, email, password, status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $data['username'], $data['email'], $hashedPassword, $data['status']);
                break;
                
            case 'eo':
                $organisasi = $data['organisasi'] ?? '';
                $pendapatan = 0;
                $query = "INSERT INTO eo (username, email, password, organisasi, pendapatan, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssis", $data['username'], $data['email'], $hashedPassword, $organisasi, $pendapatan, $data['status']);
                break;
                
            case 'player':
                $nickname = $data['nickname'] ?? '';
                $idGame = $data['idGame'] ?? '';
                $query = "INSERT INTO player (username, email, password, nickname, idGame, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssss", $data['username'], $data['email'], $hashedPassword, $nickname, $idGame, $data['status']);
                break;
                
            default:
                throw new Exception("Invalid user type");
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($userType) . ' berhasil ditambahkan',
                'user_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to insert user');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function updateUser($conn, $userType) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = intval($data['id']);
        
        // Validate required fields
        $required_fields = ['username', 'email', 'status'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field $field is required");
            }
        }
        
        // Prepare data based on user type
        switch ($userType) {
            case 'admin':
                if (!empty($data['password'])) {
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE admin SET username = ?, email = ?, password = ?, status = ? WHERE id_admin = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssssi", $data['username'], $data['email'], $hashedPassword, $data['status'], $user_id);
                } else {
                    $query = "UPDATE admin SET username = ?, email = ?, status = ? WHERE id_admin = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssi", $data['username'], $data['email'], $data['status'], $user_id);
                }
                break;
                
            case 'eo':
                $organisasi = $data['organisasi'] ?? '';
                if (!empty($data['password'])) {
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE eo SET username = ?, email = ?, password = ?, organisasi = ?, status = ? WHERE id_eo = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssssi", $data['username'], $data['email'], $hashedPassword, $organisasi, $data['status'], $user_id);
                } else {
                    $query = "UPDATE eo SET username = ?, email = ?, organisasi = ?, status = ? WHERE id_eo = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssssi", $data['username'], $data['email'], $organisasi, $data['status'], $user_id);
                }
                break;
                
            case 'player':
                $nickname = $data['nickname'] ?? '';
                $idGame = $data['idGame'] ?? '';
                if (!empty($data['password'])) {
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    $query = "UPDATE player SET username = ?, email = ?, password = ?, nickname = ?, idGame = ?, status = ? WHERE id_player = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssssssi", $data['username'], $data['email'], $hashedPassword, $nickname, $idGame, $data['status'], $user_id);
                } else {
                    $query = "UPDATE player SET username = ?, email = ?, nickname = ?, idGame = ?, status = ? WHERE id_player = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("sssssi", $data['username'], $data['email'], $nickname, $idGame, $data['status'], $user_id);
                }
                break;
                
            default:
                throw new Exception("Invalid user type");
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($userType) . ' berhasil diupdate'
            ]);
        } else {
            throw new Exception('Failed to update user');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function deleteUser($conn, $userType) {
    try {
        $user_id = intval($_GET['id'] ?? 0);
        
        if ($user_id === 0) {
            throw new Exception('User ID is required');
        }
        
        // Prevent deleting main admin
        if ($userType === 'admin' && $user_id === 1) {
            throw new Exception('Cannot delete main administrator');
        }
        
        // Prepare query based on user type
        switch ($userType) {
            case 'admin':
                $query = "DELETE FROM admin WHERE id_admin = ?";
                break;
            case 'eo':
                $query = "DELETE FROM eo WHERE id_eo = ?";
                break;
            case 'player':
                $query = "DELETE FROM player WHERE id_player = ?";
                break;
            default:
                throw new Exception("Invalid user type");
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($userType) . ' berhasil dihapus'
            ]);
        } else {
            throw new Exception('Failed to delete user');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getUser($conn, $userType, $user_id) {
    try {
        $user_id = intval($user_id);
        
        if ($user_id === 0) {
            throw new Exception('User ID is required');
        }
        
        // Prepare query based on user type
        switch ($userType) {
            case 'admin':
                $query = "SELECT id_admin as id, username, email, role, status, created_at, last_login FROM admin WHERE id_admin = ?";
                break;
            case 'eo':
                $query = "SELECT id_eo as id, username, email, organisasi, pendapatan, role, status, created_at, last_login FROM eo WHERE id_eo = ?";
                break;
            case 'player':
                $query = "SELECT id_player as id, username, email, nickname, idGame, role, status, created_at, last_login FROM player WHERE id_player = ?";
                break;
            default:
                throw new Exception("Invalid user type");
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'user' => $row
            ]);
        } else {
            throw new Exception('User not found');
        }
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getAllUsers($conn, $userType) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        // Debug logging
        error_log("getAllUsers called with userType: " . $userType);
        
        // Prepare query based on user type
        switch ($userType) {
            case 'admin':
                $query = "SELECT id_admin as id, username, email, role, status, created_at, last_login FROM admin ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $countQuery = "SELECT COUNT(*) as total FROM admin";
                break;
            case 'eo':
                $query = "SELECT id_eo as id, username, email, organisasi, pendapatan, role, status, created_at, last_login FROM eo ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $countQuery = "SELECT COUNT(*) as total FROM eo";
                break;
            case 'player':
                $query = "SELECT id_player as id, username, email, nickname, idGame, role, status, created_at, last_login FROM player ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $countQuery = "SELECT COUNT(*) as total FROM player";
                break;
            default:
                throw new Exception("Invalid user type: " . $userType);
        }
        
        // Debug logging
        error_log("Query: " . $query);
        error_log("Count Query: " . $countQuery);
        
        // Get total count
        $totalResult = $conn->query($countQuery);
        if (!$totalResult) {
            throw new Exception("Count query failed: " . $conn->error);
        }
        $total = $totalResult->fetch_assoc()['total'];
        
        // Get users
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $limit, $offset);
        $success = $stmt->execute();
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        error_log("Found " . count($users) . " users");
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("getAllUsers error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
