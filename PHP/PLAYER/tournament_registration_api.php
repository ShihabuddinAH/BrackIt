<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../connect.php';
session_start();

try {
    // Check if user is logged in as player
    if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'player') {
        throw new Exception('Unauthorized access');
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Handle session check request (doesn't need player data)
    if (isset($input['action']) && $input['action'] === 'check_session') {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ]);
        exit;
    }

    // Get player data
    $username = $_SESSION['username'];
    $player_query = "SELECT p.id_player, p.username, p.nickname, p.email, p.idGame 
                     FROM player p WHERE p.username = ?";
    $stmt = $conn->prepare($player_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $player_data = $result->fetch_assoc();

    if (!$player_data) {
        throw new Exception('Player not found');
    }

    // Handle different actions
    if ($method === 'POST') {
        if (isset($input['action'])) {
            // Debug: Log received data
            error_log("Tournament Registration API - Action: " . $input['action']);
            error_log("Tournament Registration API - Full Input: " . json_encode($input));
            
            // Validate tournament_id for actions that need it
            if (in_array($input['action'], ['check_eligibility', 'register'])) {
                if (!isset($input['tournament_id']) || empty($input['tournament_id'])) {
                    throw new Exception('Tournament ID is required');
                }
                
                // Ensure tournament_id is numeric
                if (!is_numeric($input['tournament_id'])) {
                    throw new Exception('Tournament ID must be numeric, received: ' . $input['tournament_id']);
                }
            }
            
            switch ($input['action']) {
                case 'check_eligibility':
                    $result = checkTournamentEligibility($conn, $input['tournament_id'], $player_data);
                    echo json_encode($result);
                    break;

                case 'register':
                    $result = registerTournament($conn, $input, $player_data);
                    echo json_encode($result);
                    break;

                default:
                    throw new Exception('Invalid action: ' . $input['action']);
            }
        } else {
            throw new Exception('Action parameter is required');
        }
    } else {
        throw new Exception('Only POST method is allowed, received: ' . $method);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function checkTournamentEligibility($conn, $tournament_id, $player_data) {
    // Debug logging
    error_log("Checking eligibility for tournament_id: " . $tournament_id . " and player: " . $player_data['id_player']);
    
    // Validate tournament_id
    if (!$tournament_id || !is_numeric($tournament_id)) {
        return [
            'success' => false,
            'error' => 'Invalid tournament ID: ' . $tournament_id
        ];
    }
    
    // Get tournament details
    $tournament_query = "SELECT id_turnamen, nama_turnamen, format, status, 
                        tanggal_mulai, tanggal_selesai, max_participants, 
                        pendaftar as current_participants, aturan 
                        FROM turnamen WHERE id_turnamen = ?";
    $stmt = $conn->prepare($tournament_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    // Debug: log found tournament
    error_log("Tournament found: " . ($tournament ? json_encode($tournament) : "NULL"));

    if (!$tournament) {
        return [
            'success' => false,
            'error' => 'Tournament not found with ID: ' . $tournament_id
        ];
    }

    // Check if player is already registered
    $registration_query = "SELECT id_registration FROM tournament_registrations 
                          WHERE id_turnamen = ? AND id_player = ?";
    $stmt = $conn->prepare($registration_query);
    $stmt->bind_param("ii", $tournament_id, $player_data['id_player']);
    $stmt->execute();
    $existing_registration = $stmt->get_result()->fetch_assoc();

    if ($existing_registration) {
        return [
            'success' => true,
            'can_register' => false,
            'tournament' => $tournament,
            'message' => 'You are already registered for this tournament'
        ];
    }

    // Check if tournament is full
    if ($tournament['current_participants'] >= $tournament['max_participants']) {
        return [
            'success' => true,
            'can_register' => false,
            'tournament' => $tournament,
            'message' => 'Tournament is full'
        ];
    }

    // Check tournament status
    if ($tournament['status'] !== 'aktif') {
        return [
            'success' => true,
            'can_register' => false,
            'tournament' => $tournament,
            'message' => 'Tournament registration is not open'
        ];
    }

    // Get player's team if tournament format is team
    $team_data = null;
    if ($tournament['format'] === 'team') {
        $team_query = "SELECT t.id_team, t.nama_team, 
                      (SELECT COUNT(*) FROM team_player tp WHERE tp.id_team = t.id_team) as member_count
                      FROM team t 
                      JOIN team_player tp ON t.id_team = tp.id_team 
                      WHERE tp.id_player = ?";
        $stmt = $conn->prepare($team_query);
        $stmt->bind_param("i", $player_data['id_player']);
        $stmt->execute();
        $team_data = $stmt->get_result()->fetch_assoc();

        if (!$team_data) {
            return [
                'success' => true,
                'can_register' => false,
                'tournament' => $tournament,
                'message' => 'You must be part of a team to register for team tournaments'
            ];
        }
    }

    return [
        'success' => true,
        'can_register' => true,
        'tournament' => $tournament,
        'team' => $team_data,
        'registration_options' => $tournament['format'] === 'team' 
            ? [['type' => 'team', 'label' => 'Daftar dengan Tim', 'description' => 'Daftar menggunakan tim: ' . $team_data['nama_team']]]
            : [['type' => 'individual', 'label' => 'Daftar Individual', 'description' => 'Daftar sebagai pemain individual']],
        'message' => 'You are eligible to register for this tournament'
    ];
}

function registerTournament($conn, $input, $player_data) {
    $tournament_id = $input['tournament_id'];
    $registration_type = $input['registration_type'] ?? 'individual';

    error_log("Starting tournament registration for player: " . $player_data['id_player'] . " tournament: " . $tournament_id);

    // Start transaction
    $conn->autocommit(false);

    try {
        // Check eligibility again
        $eligibility = checkTournamentEligibility($conn, $tournament_id, $player_data);
        if (!$eligibility['success'] || !$eligibility['can_register']) {
            throw new Exception($eligibility['message'] ?? 'Not eligible to register');
        }

        $tournament = $eligibility['tournament'];
        $team_data = $eligibility['team'];

        // Validate registration type matches tournament format
        if ($tournament['format'] === 'team' && $registration_type !== 'team') {
            throw new Exception('This tournament requires team registration');
        }

        if ($tournament['format'] === 'individu' && $registration_type !== 'individual') {
            throw new Exception('This tournament requires individual registration');
        }

        // Prepare values for insertion
        $id_player = null;
        $id_party = null;
        $id_team = null;

        if ($registration_type === 'individual') {
            $id_player = $player_data['id_player'];
        } elseif ($registration_type === 'team') {
            $id_team = $team_data ? $team_data['id_team'] : null;
            if (!$id_team) {
                throw new Exception('Team data not found for team registration');
            }
        } elseif ($registration_type === 'party') {
            // Handle party registration if needed
            $id_party = $input['party_id'] ?? null;
            if (!$id_party) {
                throw new Exception('Party ID required for party registration');
            }
        }

        error_log("Registration values - player: $id_player, party: $id_party, team: $id_team, type: $registration_type");

        // Create registration record
        $insert_query = "INSERT INTO tournament_registrations 
                        (id_turnamen, id_player, id_party, id_team, registration_type, registered_by, registration_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'registered')";
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare registration statement: ' . $conn->error);
        }

        $stmt->bind_param("iiiisi", $tournament_id, $id_player, $id_party, $id_team, $registration_type, $player_data['id_player']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create registration: ' . $stmt->error);
        }

        $registration_id = $conn->insert_id;
        error_log("Registration created with ID: " . $registration_id);

        // Update tournament participant count
        $update_query = "UPDATE turnamen SET pendaftar = pendaftar + 1 
                        WHERE id_turnamen = ?";
        $stmt = $conn->prepare($update_query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }

        $stmt->bind_param("i", $tournament_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update participant count: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();
        $conn->autocommit(true);

        error_log("Tournament registration successful for player: " . $player_data['id_player']);

        return [
            'success' => true,
            'message' => 'Successfully registered for tournament: ' . $tournament['nama_turnamen'],
            'registration_id' => $registration_id,
            'tournament_name' => $tournament['nama_turnamen'],
            'registration_type' => $registration_type,
            'team_name' => $team_data ? $team_data['nama_team'] : null
        ];

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $conn->autocommit(true);
        error_log("Tournament registration failed: " . $e->getMessage());
        throw $e;
    }
}
?>
