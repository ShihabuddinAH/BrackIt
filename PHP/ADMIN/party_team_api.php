<?php
include 'api_session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$dataType = $_GET['type'] ?? $_POST['type'] ?? '';

switch ($method) {
    case 'POST':
        addPartyTeam($conn, $dataType);
        break;
    case 'PUT':
        updatePartyTeam($conn, $dataType);
        break;
    case 'DELETE':
        deletePartyTeam($conn, $dataType);
        break;
    case 'GET':
        if (isset($_GET['id'])) {
            getPartyTeam($conn, $dataType, $_GET['id']);
        } else {
            getAllPartyTeam($conn, $dataType);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function addPartyTeam($conn, $dataType) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($dataType === 'party') {
            // Validate required fields for party
            $required_fields = ['nama_party', 'id_leader'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            
            // Check if leader exists
            $leader_check = $conn->prepare("SELECT id_player FROM player WHERE id_player = ?");
            $leader_check->bind_param("i", $data['id_leader']);
            $leader_check->execute();
            if ($leader_check->get_result()->num_rows === 0) {
                throw new Exception("Leader player not found");
            }
            
            // Check if leader is already in a party
            $party_check = $conn->prepare("SELECT id_party FROM party_player WHERE id_player = ?");
            $party_check->bind_param("i", $data['id_leader']);
            $party_check->execute();
            if ($party_check->get_result()->num_rows > 0) {
                throw new Exception("Leader is already in a party");
            }
            
            // Create party
            $stmt = $conn->prepare("INSERT INTO party (nama_party, id_leader) VALUES (?, ?)");
            $stmt->bind_param("si", $data['nama_party'], $data['id_leader']);
            $stmt->execute();
            $party_id = $conn->insert_id;
            
            // Add leader to party_player
            $stmt = $conn->prepare("INSERT INTO party_player (id_party, id_player) VALUES (?, ?)");
            $stmt->bind_param("ii", $party_id, $data['id_leader']);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Party created successfully',
                'id' => $party_id
            ]);
            
        } elseif ($dataType === 'team') {
            // Validate required fields for team
            $required_fields = ['nama_team', 'id_leader'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            
            // Check if leader exists
            $leader_check = $conn->prepare("SELECT id_player FROM player WHERE id_player = ?");
            $leader_check->bind_param("i", $data['id_leader']);
            $leader_check->execute();
            if ($leader_check->get_result()->num_rows === 0) {
                throw new Exception("Leader player not found");
            }
            
            // Check if leader is already in a team
            $team_check = $conn->prepare("SELECT id_team FROM team_player WHERE id_player = ?");
            $team_check->bind_param("i", $data['id_leader']);
            $team_check->execute();
            if ($team_check->get_result()->num_rows > 0) {
                throw new Exception("Leader is already in a team");
            }
            
            $logo_team = $data['logo_team'] ?? 'default.png';
            $deskripsi_team = $data['deskripsi_team'] ?? '';
            
            // Create team
            $stmt = $conn->prepare("INSERT INTO team (nama_team, logo_team, win, point, deskripsi_team, id_leader) VALUES (?, ?, 0, 0, ?, ?)");
            $stmt->bind_param("sssi", $data['nama_team'], $logo_team, $deskripsi_team, $data['id_leader']);
            $stmt->execute();
            $team_id = $conn->insert_id;
            
            // Add leader to team_player
            $stmt = $conn->prepare("INSERT INTO team_player (id_team, id_player) VALUES (?, ?)");
            $stmt->bind_param("ii", $team_id, $data['id_leader']);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Team created successfully',
                'id' => $team_id
            ]);
        } else {
            throw new Exception("Invalid data type");
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function updatePartyTeam($conn, $dataType) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        if ($dataType === 'party') {
            $stmt = $conn->prepare("UPDATE party SET nama_party = ? WHERE id_party = ?");
            $stmt->bind_param("si", $data['nama_party'], $id);
            
        } elseif ($dataType === 'team') {
            $logo_team = $data['logo_team'] ?? 'default.png';
            $deskripsi_team = $data['deskripsi_team'] ?? '';
            
            $stmt = $conn->prepare("UPDATE team SET nama_team = ?, logo_team = ?, deskripsi_team = ? WHERE id_team = ?");
            $stmt->bind_param("sssi", $data['nama_team'], $logo_team, $deskripsi_team, $id);
            
        } else {
            throw new Exception("Invalid data type");
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($dataType) . ' updated successfully'
            ]);
        } else {
            throw new Exception("Failed to update " . $dataType);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function deletePartyTeam($conn, $dataType) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        if ($dataType === 'party') {
            // Delete party members first
            $stmt = $conn->prepare("DELETE FROM party_player WHERE id_party = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete party invitations
            $stmt = $conn->prepare("DELETE FROM party_invitations WHERE id_party = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete party
            $stmt = $conn->prepare("DELETE FROM party WHERE id_party = ?");
            $stmt->bind_param("i", $id);
            
        } elseif ($dataType === 'team') {
            // Delete team members first
            $stmt = $conn->prepare("DELETE FROM team_player WHERE id_team = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete team invitations
            $stmt = $conn->prepare("DELETE FROM team_invitations WHERE id_team = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete team
            $stmt = $conn->prepare("DELETE FROM team WHERE id_team = ?");
            $stmt->bind_param("i", $id);
            
        } else {
            throw new Exception("Invalid data type");
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => ucfirst($dataType) . ' deleted successfully'
            ]);
        } else {
            throw new Exception("Failed to delete " . $dataType);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getPartyTeam($conn, $dataType, $id) {
    try {
        if ($dataType === 'party') {
            $query = "SELECT p.*, pl.username as leader_username, pl.nickname as leader_nickname,
                             (SELECT COUNT(*) FROM party_player pp WHERE pp.id_party = p.id_party) as member_count
                      FROM party p 
                      LEFT JOIN player pl ON p.id_leader = pl.id_player 
                      WHERE p.id_party = ?";
        } elseif ($dataType === 'team') {
            $query = "SELECT t.*, pl.username as leader_username, pl.nickname as leader_nickname,
                             (SELECT COUNT(*) FROM team_player tp WHERE tp.id_team = t.id_team) as member_count
                      FROM team t 
                      LEFT JOIN player pl ON t.id_leader = pl.id_player 
                      WHERE t.id_team = ?";
        } else {
            throw new Exception("Invalid data type");
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'data' => $row
            ]);
        } else {
            throw new Exception(ucfirst($dataType) . " not found");
        }
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getAllPartyTeam($conn, $dataType) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        error_log("getAllPartyTeam called with dataType: " . $dataType);
        
        if ($dataType === 'party') {
            $query = "SELECT p.id_party as id, p.nama_party, p.win, p.lose, (p.win + p.lose) as total_match, p.created_at,
                             pl.username as leader_username, pl.nickname as leader_nickname,
                             (SELECT COUNT(*) FROM party_player pp WHERE pp.id_party = p.id_party) as member_count
                      FROM party p 
                      LEFT JOIN player pl ON p.id_leader = pl.id_player 
                      ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            $countQuery = "SELECT COUNT(*) as total FROM party";
            
        } elseif ($dataType === 'team') {
            $query = "SELECT t.id_team as id, t.nama_team, t.logo_team, t.win, t.lose, (t.win + t.lose) as total_match, t.point, t.deskripsi_team,
                             pl.username as leader_username, pl.nickname as leader_nickname,
                             (SELECT COUNT(*) FROM team_player tp WHERE tp.id_team = t.id_team) as member_count,
                             t.created_at
                      FROM team t 
                      LEFT JOIN player pl ON t.id_leader = pl.id_player 
                      ORDER BY t.id_team DESC LIMIT ? OFFSET ?";
            $countQuery = "SELECT COUNT(*) as total FROM team";
            
        } else {
            throw new Exception("Invalid data type: " . $dataType);
        }
        
        error_log("Query: " . $query);
        error_log("Count Query: " . $countQuery);
        
        // Get total count
        $totalResult = $conn->query($countQuery);
        if (!$totalResult) {
            throw new Exception("Count query failed: " . $conn->error);
        }
        $total = $totalResult->fetch_assoc()['total'];
        
        // Get data
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
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        error_log("Found " . count($items) . " " . $dataType . "s");
        
        echo json_encode([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("getAllPartyTeam error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
