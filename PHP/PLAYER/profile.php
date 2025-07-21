<?php
session_start();
require_once '../connect.php';

// Auto-expire old invitations on page load
require_once '../cleanup_invitations.php';
cleanup_expired_invitations($conn);

// Auto-expire old team invitations on page load
require_once '../cleanup_team_invitations.php';
cleanup_expired_team_invitations($conn);

// Check if user is logged in as player
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../index.php');
    exit();
}

$username = $_SESSION['username'];

// Get player data with party and team information
$player_query = "SELECT p.*, 
                        pt.nama_party, 
                        pt.id_party,
                        pt.win as party_win,
                        pt.lose as party_lose,
                        (pt.win + pt.lose) as party_total_match,
                        t.nama_team, 
                        t.id_team,
                        t.win as team_win,
                        t.lose as team_lose,
                        (t.win + t.lose) as team_total_match,
                        (SELECT COUNT(*) FROM party_player pp2 WHERE pp2.id_party = pt.id_party) as party_members_count
                 FROM player p
                 LEFT JOIN party_player pp ON p.id_player = pp.id_player
                 LEFT JOIN party pt ON pp.id_party = pt.id_party
                 LEFT JOIN team_player tp ON p.id_player = tp.id_player
                 LEFT JOIN team t ON tp.id_team = t.id_team
                 WHERE p.username = ?";

$stmt = $conn->prepare($player_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$player_data = $result->fetch_assoc();

if (!$player_data) {
    echo "<script>alert('Player data not found'); window.location.href = '../../index.php';</script>";
    exit();
}

// Debug: Check if party data is being retrieved
if (empty($player_data['id_party'])) {
    // Try alternative query to find party membership
    $alt_party_query = "SELECT pp.id_party, pt.nama_party, pt.win, pt.lose, (pt.win + pt.lose) as total_match
                        FROM party_player pp 
                        JOIN party pt ON pp.id_party = pt.id_party 
                        WHERE pp.id_player = ?";
    $stmt = $conn->prepare($alt_party_query);
    $stmt->bind_param("i", $player_data['id_player']);
    $stmt->execute();
    $alt_result = $stmt->get_result();
    
    if ($alt_result->num_rows > 0) {
        $party_info = $alt_result->fetch_assoc();
        $player_data['id_party'] = $party_info['id_party'];
        $player_data['nama_party'] = $party_info['nama_party'];
        $player_data['party_win'] = $party_info['win'];
        $player_data['party_lose'] = $party_info['lose'];
        $player_data['party_total_match'] = $party_info['total_match'];
    }
}

$success_message = '';
$error_message = '';

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'party_created':
            $success_message = "Party created successfully!";
            break;
        case 'profile_updated':
            $success_message = "Profile updated successfully!";
            break;
        case 'member_added':
            $success_message = "Player added to party successfully!";
            break;
        case 'invitation_sent':
            $success_message = "Invitation sent successfully! Waiting for player's response.";
            break;
        case 'invitation_accepted':
            $success_message = "Invitation accepted! Welcome to the party!";
            break;
        case 'invitation_declined':
            $success_message = "Invitation declined.";
            break;
        case 'party_left':
            $success_message = "Left party successfully!";
            break;
        case 'party_disbanded':
            $success_message = "Party disbanded successfully!";
            break;
        case 'member_kicked':
            $success_message = "Player kicked from party successfully!";
            break;
        case 'team_created':
            $success_message = "Team created successfully!";
            break;
        case 'team_updated':
            $success_message = "Team description updated successfully!";
            break;
        case 'member_updated':
            $success_message = "Team member information updated successfully!";
            break;
        case 'member_removed':
            $success_message = "Team member removed successfully!";
            break;
        case 'team_invitation_sent':
            $success_message = "Team invitation sent successfully! Waiting for player's response.";
            break;
        case 'team_invitation_accepted':
            $success_message = "Team invitation accepted! Welcome to the team!";
            break;
        case 'team_invitation_declined':
            $success_message = "Team invitation declined.";
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $new_nickname = trim($_POST['nickname']);
                $new_idGame = trim($_POST['idGame']);
                
                if (!empty($new_nickname) && !empty($new_idGame)) {
                    $update_query = "UPDATE player SET nickname = ?, idGame = ? WHERE username = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("sss", $new_nickname, $new_idGame, $username);
                    
                    if ($stmt->execute()) {
                        // Redirect to prevent form resubmission
                        header("Location: " . $_SERVER['PHP_SELF'] . "?success=profile_updated");
                        exit();
                    } else {
                        $error_message = "Failed to update profile.";
                    }
                } else {
                    $error_message = "Nickname and Game ID are required.";
                }
                break;
                
            case 'create_party':
                $party_name = trim($_POST['party_name']);
                
                if (!empty($party_name)) {
                    // Check if player is already in a party
                    $check_existing_party = "SELECT pp.id_party, pt.nama_party FROM party_player pp 
                                           JOIN party pt ON pp.id_party = pt.id_party 
                                           WHERE pp.id_player = ?";
                    $stmt = $conn->prepare($check_existing_party);
                    $stmt->bind_param("i", $player_data['id_player']);
                    $stmt->execute();
                    $existing_party_result = $stmt->get_result();
                    
                    if ($existing_party_result->num_rows > 0) {
                        $existing_party = $existing_party_result->fetch_assoc();
                        $error_message = "You are already in party: " . $existing_party['nama_party'];
                    } else {
                        // Create new party with player as leader
                        $create_party_query = "INSERT INTO party (nama_party, id_leader) VALUES (?, ?)";
                        $stmt = $conn->prepare($create_party_query);
                        $stmt->bind_param("si", $party_name, $player_data['id_player']);
                        
                        if ($stmt->execute()) {
                            $party_id = $conn->insert_id;
                            
                            // Add player to party_player table (no jml_menang column needed)
                            $add_to_party_query = "INSERT INTO party_player (id_party, id_player) VALUES (?, ?)";
                            $stmt = $conn->prepare($add_to_party_query);
                            $stmt->bind_param("ii", $party_id, $player_data['id_player']);
                            
                            if ($stmt->execute()) {
                                $success_message = "Party created successfully!";
                                // Redirect to prevent form resubmission
                                header("Location: " . $_SERVER['PHP_SELF'] . "?success=party_created");
                                exit();
                            } else {
                                $error_message = "Failed to join the created party.";
                            }
                        } else {
                            $error_message = "Failed to create party. Error: " . $conn->error;
                        }
                    }
                } else {
                    $error_message = "Party name is required.";
                }
                break;
                
            case 'invite_member':
                // Check if invitation system is available
                $check_table_query = "SHOW TABLES LIKE 'party_invitations'";
                $table_result = $conn->query($check_table_query);
                
                if (!$table_result || $table_result->num_rows == 0) {
                    $error_message = "Invitation system is not yet set up. Please run the database setup script.";
                    break;
                }
                
                $invite_username = trim($_POST['invite_username']);
                
                if (!empty($invite_username) && !empty($player_data['id_party'])) {
                    // Check if current player is party leader
                    $check_leader_query = "SELECT id_leader FROM party WHERE id_party = ?";
                    $stmt = $conn->prepare($check_leader_query);
                    $stmt->bind_param("i", $player_data['id_party']);
                    $stmt->execute();
                    $leader_result = $stmt->get_result();
                    $leader_data = $leader_result->fetch_assoc();
                    
                    if ($leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only party leader can invite members.";
                    } elseif ($player_data['party_members_count'] >= 5) {
                        $error_message = "Party is full (maximum 5 members).";
                    } else {
                        // Find player by username
                        $find_player_query = "SELECT id_player, nickname FROM player WHERE username = ?";
                        $stmt = $conn->prepare($find_player_query);
                        $stmt->bind_param("s", $invite_username);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $invited_player = $result->fetch_assoc();
                        
                        if ($invited_player) {
                            // Check if player is already in a party
                            $check_party_query = "SELECT id_party FROM party_player WHERE id_player = ?";
                            $stmt = $conn->prepare($check_party_query);
                            $stmt->bind_param("i", $invited_player['id_player']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $error_message = "Player is already in a party.";
                            } else {
                                // Check if invitation already exists
                                $check_invitation_query = "SELECT id_invitation, status FROM party_invitations 
                                                         WHERE id_party = ? AND id_invited = ? AND status = 'pending'";
                                $stmt = $conn->prepare($check_invitation_query);
                                $stmt->bind_param("ii", $player_data['id_party'], $invited_player['id_player']);
                                $stmt->execute();
                                $invitation_result = $stmt->get_result();
                                
                                if ($invitation_result->num_rows > 0) {
                                    $error_message = "Invitation already sent to this player.";
                                } else {
                                    // Create invitation
                                    $create_invitation_query = "INSERT INTO party_invitations (id_party, id_inviter, id_invited) VALUES (?, ?, ?)";
                                    $stmt = $conn->prepare($create_invitation_query);
                                    $stmt->bind_param("iii", $player_data['id_party'], $player_data['id_player'], $invited_player['id_player']);
                                    
                                    if ($stmt->execute()) {
                                        // Redirect to prevent form resubmission
                                        header("Location: " . $_SERVER['PHP_SELF'] . "?success=invitation_sent");
                                        exit();
                                    } else {
                                        $error_message = "Failed to send invitation.";
                                    }
                                }
                            }
                        } else {
                            $error_message = "Player not found.";
                        }
                    }
                } else {
                    $error_message = "Username is required and you must be in a party.";
                }
                break;
                
            case 'create_team':
                $team_name = trim($_POST['team_name']);
                
                if (!empty($team_name)) {
                    // Check if player is already in a team
                    if (!empty($player_data['id_team'])) {
                        $error_message = "You are already in a team.";
                    } elseif (empty($player_data['id_party'])) {
                        $error_message = "You must be in a party to create a team.";
                    } else {
                        // Check if player is party leader
                        $check_leader_query = "SELECT id_leader FROM party WHERE id_party = ?";
                        $stmt = $conn->prepare($check_leader_query);
                        $stmt->bind_param("i", $player_data['id_party']);
                        $stmt->execute();
                        $leader_result = $stmt->get_result();
                        $leader_data = $leader_result->fetch_assoc();
                        
                        if ($leader_data['id_leader'] != $player_data['id_player']) {
                            $error_message = "Only party leader can create a team.";
                        } elseif ($player_data['win'] < 3) {
                            $error_message = "As party leader, you need to win at least 3 times before creating a team.";
                        } else {
                            // Create team with current player as team leader
                            $create_team_query = "INSERT INTO team (nama_team, logo_team, win, point, deskripsi_team, id_leader) VALUES (?, 'default.png', 0, 0, 'Team created by party leader', ?)";
                            $stmt = $conn->prepare($create_team_query);
                            $stmt->bind_param("si", $team_name, $player_data['id_player']);
                            
                            if ($stmt->execute()) {
                                $team_id = $conn->insert_id;
                                
                                // Get all party members
                                $party_members_query = "SELECT id_player FROM party_player WHERE id_party = ?";
                                $stmt = $conn->prepare($party_members_query);
                                $stmt->bind_param("i", $player_data['id_party']);
                                $stmt->execute();
                                $members_result = $stmt->get_result();
                                
                                // Add all party members to team
                                $add_to_team_query = "INSERT INTO team_player (id_team, id_player) VALUES (?, ?)";
                                $stmt = $conn->prepare($add_to_team_query);
                                
                                $all_added = true;
                                while ($member = $members_result->fetch_assoc()) {
                                    $stmt->bind_param("ii", $team_id, $member['id_player']);
                                    if (!$stmt->execute()) {
                                        $all_added = false;
                                        break;
                                    }
                                }
                                
                                if ($all_added) {
                                    $success_message = "Team created successfully! All party members added to team.";
                                    // Refresh data
                                    $stmt = $conn->prepare($player_query);
                                    $stmt->bind_param("s", $username);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $player_data = $result->fetch_assoc();
                                } else {
                                    $error_message = "Team created but failed to add some members.";
                                }
                            } else {
                                $error_message = "Failed to create team.";
                            }
                        }
                    }
                } else {
                    $error_message = "Team name is required.";
                }
                break;
                
            case 'leave_party':
                if (!empty($player_data['id_party'])) {
                    // Check if player is party leader
                    $check_leader_query = "SELECT id_leader FROM party WHERE id_party = ?";
                    $stmt = $conn->prepare($check_leader_query);
                    $stmt->bind_param("i", $player_data['id_party']);
                    $stmt->execute();
                    $leader_result = $stmt->get_result();
                    $leader_data = $leader_result->fetch_assoc();
                    
                    if ($leader_data['id_leader'] == $player_data['id_player']) {
                        // If leader leaves, delete the entire party
                        $delete_party_members_query = "DELETE FROM party_player WHERE id_party = ?";
                        $stmt = $conn->prepare($delete_party_members_query);
                        $stmt->bind_param("i", $player_data['id_party']);
                        $stmt->execute();
                        
                        $delete_party_query = "DELETE FROM party WHERE id_party = ?";
                        $stmt = $conn->prepare($delete_party_query);
                        $stmt->bind_param("i", $player_data['id_party']);
                        
                        if ($stmt->execute()) {
                            // Redirect to prevent form resubmission
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=party_disbanded");
                            exit();
                        } else {
                            $error_message = "Failed to disband party.";
                        }
                    } else {
                        // If member leaves, just remove from party_player
                        $leave_party_query = "DELETE FROM party_player WHERE id_party = ? AND id_player = ?";
                        $stmt = $conn->prepare($leave_party_query);
                        $stmt->bind_param("ii", $player_data['id_party'], $player_data['id_player']);
                        
                        if ($stmt->execute()) {
                            // Redirect to prevent form resubmission
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=party_left");
                            exit();
                        } else {
                            $error_message = "Failed to leave party.";
                        }
                    }
                } else {
                    $error_message = "You are not in any party.";
                }
                break;
                
            case 'kick_member':
                $kick_username = trim($_POST['kick_username']);
                
                if (!empty($kick_username) && !empty($player_data['id_party'])) {
                    // Check if current player is party leader
                    $check_leader_query = "SELECT id_leader FROM party WHERE id_party = ?";
                    $stmt = $conn->prepare($check_leader_query);
                    $stmt->bind_param("i", $player_data['id_party']);
                    $stmt->execute();
                    $leader_result = $stmt->get_result();
                    $leader_data = $leader_result->fetch_assoc();
                    
                    if ($leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only party leader can kick members.";
                    } else {
                        // Find player to kick
                        $find_kick_player_query = "SELECT p.id_player, p.nickname FROM player p 
                                                 JOIN party_player pp ON p.id_player = pp.id_player 
                                                 WHERE p.username = ? AND pp.id_party = ?";
                        $stmt = $conn->prepare($find_kick_player_query);
                        $stmt->bind_param("si", $kick_username, $player_data['id_party']);
                        $stmt->execute();
                        $kick_result = $stmt->get_result();
                        $kick_player = $kick_result->fetch_assoc();
                        
                        if (!$kick_player) {
                            $error_message = "Player not found in your party.";
                        } elseif ($kick_player['id_player'] == $player_data['id_player']) {
                            $error_message = "You cannot kick yourself. Use leave party instead.";
                        } else {
                            // Remove player from party
                            $kick_query = "DELETE FROM party_player WHERE id_party = ? AND id_player = ?";
                            $stmt = $conn->prepare($kick_query);
                            $stmt->bind_param("ii", $player_data['id_party'], $kick_player['id_player']);
                            
                            if ($stmt->execute()) {
                                // Redirect to prevent form resubmission
                                header("Location: " . $_SERVER['PHP_SELF'] . "?success=member_kicked");
                                exit();
                            } else {
                                $error_message = "Failed to kick player.";
                            }
                        }
                    }
                } else {
                    $error_message = "Username is required and you must be in a party.";
                }
                break;
                
            case 'accept_invitation':
                // Check if invitation system is available
                $check_table_query = "SHOW TABLES LIKE 'party_invitations'";
                $table_result = $conn->query($check_table_query);
                
                if (!$table_result || $table_result->num_rows == 0) {
                    $error_message = "Invitation system is not yet set up. Please run the database setup script.";
                    break;
                }
                
                $invitation_id = (int)$_POST['invitation_id'];
                
                if ($invitation_id > 0) {
                    // Get invitation details
                    $get_invitation_query = "SELECT pi.*, pt.nama_party, p.nickname as inviter_nickname 
                                           FROM party_invitations pi 
                                           JOIN party pt ON pi.id_party = pt.id_party 
                                           JOIN player p ON pi.id_inviter = p.id_player 
                                           WHERE pi.id_invitation = ? AND pi.id_invited = ? AND pi.status = 'pending'";
                    $stmt = $conn->prepare($get_invitation_query);
                    $stmt->bind_param("ii", $invitation_id, $player_data['id_player']);
                    $stmt->execute();
                    $invitation_result = $stmt->get_result();
                    $invitation = $invitation_result->fetch_assoc();
                    
                    if (!$invitation) {
                        $error_message = "Invalid or expired invitation.";
                    } else {
                        // Check if player is already in a party
                        $check_party_query = "SELECT id_party FROM party_player WHERE id_player = ?";
                        $stmt = $conn->prepare($check_party_query);
                        $stmt->bind_param("i", $player_data['id_player']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error_message = "You are already in a party.";
                        } else {
                            // Check if party is still not full
                            $check_party_count_query = "SELECT COUNT(*) as member_count FROM party_player WHERE id_party = ?";
                            $stmt = $conn->prepare($check_party_count_query);
                            $stmt->bind_param("i", $invitation['id_party']);
                            $stmt->execute();
                            $count_result = $stmt->get_result();
                            $count_data = $count_result->fetch_assoc();
                            
                            if ($count_data['member_count'] >= 5) {
                                $error_message = "Party is now full.";
                                // Auto-decline this invitation
                                $decline_query = "UPDATE party_invitations SET status = 'declined', responded_at = NOW() WHERE id_invitation = ?";
                                $stmt = $conn->prepare($decline_query);
                                $stmt->bind_param("i", $invitation_id);
                                $stmt->execute();
                            } else {
                                // Add player to party
                                $add_member_query = "INSERT INTO party_player (id_party, id_player) VALUES (?, ?)";
                                $stmt = $conn->prepare($add_member_query);
                                $stmt->bind_param("ii", $invitation['id_party'], $player_data['id_player']);
                                
                                if ($stmt->execute()) {
                                    // Update invitation status
                                    $accept_query = "UPDATE party_invitations SET status = 'accepted', responded_at = NOW() WHERE id_invitation = ?";
                                    $stmt = $conn->prepare($accept_query);
                                    $stmt->bind_param("i", $invitation_id);
                                    $stmt->execute();
                                    
                                    // Redirect to prevent form resubmission
                                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=invitation_accepted");
                                    exit();
                                } else {
                                    $error_message = "Failed to join party.";
                                }
                            }
                        }
                    }
                } else {
                    $error_message = "Invalid invitation.";
                }
                break;
                
            case 'decline_invitation':
                // Check if invitation system is available
                $check_table_query = "SHOW TABLES LIKE 'party_invitations'";
                $table_result = $conn->query($check_table_query);
                
                if (!$table_result || $table_result->num_rows == 0) {
                    $error_message = "Invitation system is not yet set up. Please run the database setup script.";
                    break;
                }
                
                $invitation_id = (int)$_POST['invitation_id'];
                
                if ($invitation_id > 0) {
                    // Update invitation status
                    $decline_query = "UPDATE party_invitations SET status = 'declined', responded_at = NOW() 
                                    WHERE id_invitation = ? AND id_invited = ? AND status = 'pending'";
                    $stmt = $conn->prepare($decline_query);
                    $stmt->bind_param("ii", $invitation_id, $player_data['id_player']);
                    
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        // Redirect to prevent form resubmission
                        header("Location: " . $_SERVER['PHP_SELF'] . "?success=invitation_declined");
                        exit();
                    } else {
                        $error_message = "Failed to decline invitation or invitation not found.";
                    }
                } else {
                    $error_message = "Invalid invitation.";
                }
                break;
                
            case 'edit_team':
                $team_description = trim($_POST['team_description']);
                
                if (!empty($player_data['id_team'])) {
                    // Check if current player is team leader
                    $check_team_leader_query = "SELECT id_leader FROM team WHERE id_team = ?";
                    $stmt = $conn->prepare($check_team_leader_query);
                    $stmt->bind_param("i", $player_data['id_team']);
                    $stmt->execute();
                    $team_leader_result = $stmt->get_result();
                    $team_leader_data = $team_leader_result->fetch_assoc();
                    
                    if ($team_leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only team leader can edit team information.";
                    } else {
                        // Update team description
                        $update_team_query = "UPDATE team SET deskripsi_team = ? WHERE id_team = ?";
                        $stmt = $conn->prepare($update_team_query);
                        $stmt->bind_param("si", $team_description, $player_data['id_team']);
                        
                        if ($stmt->execute()) {
                            // Redirect to prevent form resubmission
                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=team_updated");
                            exit();
                        } else {
                            $error_message = "Failed to update team description.";
                        }
                    }
                } else {
                    $error_message = "You are not in any team.";
                }
                break;
                
            case 'edit_member':
                $member_username = trim($_POST['member_username']);
                $new_nickname = trim($_POST['new_nickname']);
                $new_game_id = trim($_POST['new_game_id']);
                
                if (!empty($player_data['id_team']) && !empty($member_username)) {
                    // Check if current player is team leader
                    $check_team_leader_query = "SELECT id_leader FROM team WHERE id_team = ?";
                    $stmt = $conn->prepare($check_team_leader_query);
                    $stmt->bind_param("i", $player_data['id_team']);
                    $stmt->execute();
                    $team_leader_result = $stmt->get_result();
                    $team_leader_data = $team_leader_result->fetch_assoc();
                    
                    if ($team_leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only team leader can edit member information.";
                    } else {
                        // Check if member is in the same team
                        $check_member_query = "SELECT p.id_player FROM player p 
                                             JOIN team_player tp ON p.id_player = tp.id_player 
                                             WHERE p.username = ? AND tp.id_team = ?";
                        $stmt = $conn->prepare($check_member_query);
                        $stmt->bind_param("si", $member_username, $player_data['id_team']);
                        $stmt->execute();
                        $member_result = $stmt->get_result();
                        $member_data = $member_result->fetch_assoc();
                        
                        if (!$member_data) {
                            $error_message = "Member not found in your team.";
                        } else {
                            // Update member information
                            $update_member_query = "UPDATE player SET nickname = ?, idGame = ? WHERE id_player = ?";
                            $stmt = $conn->prepare($update_member_query);
                            $stmt->bind_param("ssi", $new_nickname, $new_game_id, $member_data['id_player']);
                            
                            if ($stmt->execute()) {
                                // Redirect to prevent form resubmission
                                header("Location: " . $_SERVER['PHP_SELF'] . "?success=member_updated");
                                exit();
                            } else {
                                $error_message = "Failed to update member information.";
                            }
                        }
                    }
                } else {
                    $error_message = "Missing required information.";
                }
                break;
                
            case 'invite_team_member':
                $invite_username = trim($_POST['invite_username']);
                
                if (!empty($invite_username) && !empty($player_data['id_team'])) {
                    // Check if current player is team leader
                    $check_team_leader_query = "SELECT id_leader FROM team WHERE id_team = ?";
                    $stmt = $conn->prepare($check_team_leader_query);
                    $stmt->bind_param("i", $player_data['id_team']);
                    $stmt->execute();
                    $team_leader_result = $stmt->get_result();
                    $team_leader_data = $team_leader_result->fetch_assoc();
                    
                    if ($team_leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only team leader can invite members.";
                    } else {
                        // Check if team is full (max 5 members)
                        $current_members_count = count($team_members);
                        if ($current_members_count >= 5) {
                            $error_message = "Team is full (maximum 5 members).";
                        } else {
                            // Find player by username
                            $find_player_query = "SELECT id_player, nickname FROM player WHERE username = ?";
                            $stmt = $conn->prepare($find_player_query);
                            $stmt->bind_param("s", $invite_username);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $invited_player = $result->fetch_assoc();
                            
                            if ($invited_player) {
                                // Check if player is already in a team
                                $check_team_query = "SELECT id_team FROM team_player WHERE id_player = ?";
                                $stmt = $conn->prepare($check_team_query);
                                $stmt->bind_param("i", $invited_player['id_player']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $error_message = "Player is already in a team.";
                                } else {
                                    // Check if invitation already exists
                                    $check_invitation_query = "SELECT id_invitation, status FROM team_invitations 
                                                             WHERE id_team = ? AND id_invited = ? AND status = 'pending'";
                                    $stmt = $conn->prepare($check_invitation_query);
                                    $stmt->bind_param("ii", $player_data['id_team'], $invited_player['id_player']);
                                    $stmt->execute();
                                    $invitation_result = $stmt->get_result();
                                    
                                    if ($invitation_result->num_rows > 0) {
                                        $error_message = "Invitation already sent to this player.";
                                    } else {
                                        // Create team invitation
                                        $create_invitation_query = "INSERT INTO team_invitations (id_team, id_inviter, id_invited) VALUES (?, ?, ?)";
                                        $stmt = $conn->prepare($create_invitation_query);
                                        $stmt->bind_param("iii", $player_data['id_team'], $player_data['id_player'], $invited_player['id_player']);
                                        
                                        if ($stmt->execute()) {
                                            // Redirect to prevent form resubmission
                                            header("Location: " . $_SERVER['PHP_SELF'] . "?success=team_invitation_sent");
                                            exit();
                                        } else {
                                            $error_message = "Failed to send team invitation.";
                                        }
                                    }
                                }
                            } else {
                                $error_message = "Player not found.";
                            }
                        }
                    }
                } else {
                    $error_message = "Username is required and you must be in a team.";
                }
                break;
                
            case 'accept_team_invitation':
                $invitation_id = (int)$_POST['invitation_id'];
                
                if ($invitation_id > 0) {
                    // Get invitation details
                    $get_invitation_query = "SELECT ti.*, t.nama_team, p.nickname as inviter_nickname 
                                           FROM team_invitations ti 
                                           JOIN team t ON ti.id_team = t.id_team 
                                           JOIN player p ON ti.id_inviter = p.id_player 
                                           WHERE ti.id_invitation = ? AND ti.id_invited = ? AND ti.status = 'pending'";
                    $stmt = $conn->prepare($get_invitation_query);
                    $stmt->bind_param("ii", $invitation_id, $player_data['id_player']);
                    $stmt->execute();
                    $invitation_result = $stmt->get_result();
                    $invitation = $invitation_result->fetch_assoc();
                    
                    if (!$invitation) {
                        $error_message = "Invalid or expired team invitation.";
                    } else {
                        // Check if player is already in a team
                        $check_team_query = "SELECT id_team FROM team_player WHERE id_player = ?";
                        $stmt = $conn->prepare($check_team_query);
                        $stmt->bind_param("i", $player_data['id_player']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $error_message = "You are already in a team.";
                        } else {
                            // Check if team is still not full
                            $check_team_count_query = "SELECT COUNT(*) as member_count FROM team_player WHERE id_team = ?";
                            $stmt = $conn->prepare($check_team_count_query);
                            $stmt->bind_param("i", $invitation['id_team']);
                            $stmt->execute();
                            $count_result = $stmt->get_result();
                            $count_data = $count_result->fetch_assoc();
                            
                            if ($count_data['member_count'] >= 5) {
                                $error_message = "Team is now full.";
                                // Auto-decline this invitation
                                $decline_query = "UPDATE team_invitations SET status = 'declined', responded_at = NOW() WHERE id_invitation = ?";
                                $stmt = $conn->prepare($decline_query);
                                $stmt->bind_param("i", $invitation_id);
                                $stmt->execute();
                            } else {
                                // Add player to team
                                $add_member_query = "INSERT INTO team_player (id_team, id_player) VALUES (?, ?)";
                                $stmt = $conn->prepare($add_member_query);
                                $stmt->bind_param("ii", $invitation['id_team'], $player_data['id_player']);
                                
                                if ($stmt->execute()) {
                                    // Update invitation status
                                    $accept_query = "UPDATE team_invitations SET status = 'accepted', responded_at = NOW() WHERE id_invitation = ?";
                                    $stmt = $conn->prepare($accept_query);
                                    $stmt->bind_param("i", $invitation_id);
                                    $stmt->execute();
                                    
                                    // Redirect to prevent form resubmission
                                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=team_invitation_accepted");
                                    exit();
                                } else {
                                    $error_message = "Failed to join team.";
                                }
                            }
                        }
                    }
                } else {
                    $error_message = "Invalid team invitation.";
                }
                break;
                
            case 'decline_team_invitation':
                $invitation_id = (int)$_POST['invitation_id'];
                
                if ($invitation_id > 0) {
                    // Update invitation status
                    $decline_query = "UPDATE team_invitations SET status = 'declined', responded_at = NOW() 
                                    WHERE id_invitation = ? AND id_invited = ? AND status = 'pending'";
                    $stmt = $conn->prepare($decline_query);
                    $stmt->bind_param("ii", $invitation_id, $player_data['id_player']);
                    
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        // Redirect to prevent form resubmission
                        header("Location: " . $_SERVER['PHP_SELF'] . "?success=team_invitation_declined");
                        exit();
                    } else {
                        $error_message = "Failed to decline team invitation or invitation not found.";
                    }
                } else {
                    $error_message = "Invalid team invitation.";
                }
                break;
                
            case 'remove_member':
                $member_username = trim($_POST['member_username']);
                
                if (!empty($player_data['id_team']) && !empty($member_username)) {
                    // Check if current player is team leader
                    $check_team_leader_query = "SELECT id_leader FROM team WHERE id_team = ?";
                    $stmt = $conn->prepare($check_team_leader_query);
                    $stmt->bind_param("i", $player_data['id_team']);
                    $stmt->execute();
                    $team_leader_result = $stmt->get_result();
                    $team_leader_data = $team_leader_result->fetch_assoc();
                    
                    if ($team_leader_data['id_leader'] != $player_data['id_player']) {
                        $error_message = "Only team leader can remove members.";
                    } else {
                        // Check if member is in the same team and not the leader
                        $check_member_query = "SELECT p.id_player FROM player p 
                                             JOIN team_player tp ON p.id_player = tp.id_player 
                                             WHERE p.username = ? AND tp.id_team = ?";
                        $stmt = $conn->prepare($check_member_query);
                        $stmt->bind_param("si", $member_username, $player_data['id_team']);
                        $stmt->execute();
                        $member_result = $stmt->get_result();
                        $member_data = $member_result->fetch_assoc();
                        
                        if (!$member_data) {
                            $error_message = "Member not found in your team.";
                        } elseif ($member_data['id_player'] == $player_data['id_player']) {
                            $error_message = "You cannot remove yourself from the team.";
                        } else {
                            // Remove member from team
                            $remove_member_query = "DELETE FROM team_player WHERE id_team = ? AND id_player = ?";
                            $stmt = $conn->prepare($remove_member_query);
                            $stmt->bind_param("ii", $player_data['id_team'], $member_data['id_player']);
                            
                            if ($stmt->execute()) {
                                // Redirect to prevent form resubmission
                                header("Location: " . $_SERVER['PHP_SELF'] . "?success=member_removed");
                                exit();
                            } else {
                                $error_message = "Failed to remove member from team.";
                            }
                        }
                    }
                } else {
                    $error_message = "Missing required information.";
                }
                break;
        }
    }
}

// Get party members if player is in a party
$party_members = [];
if (!empty($player_data['id_party'])) {
    $members_query = "SELECT p.username, p.nickname, p.idGame, 
                            (pt.id_leader = p.id_player) as is_leader
                      FROM party_player pp 
                      JOIN player p ON pp.id_player = p.id_player 
                      JOIN party pt ON pp.id_party = pt.id_party
                      WHERE pp.id_party = ? 
                      ORDER BY is_leader DESC, p.username";
    $stmt = $conn->prepare($members_query);
    $stmt->bind_param("i", $player_data['id_party']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($member = $result->fetch_assoc()) {
        $party_members[] = $member;
    }
} else {
    // Double check by looking in party_player table directly
    $check_party_query = "SELECT pp.id_party, pt.nama_party 
                         FROM party_player pp 
                         JOIN party pt ON pp.id_party = pt.id_party 
                         WHERE pp.id_player = ?";
    $stmt = $conn->prepare($check_party_query);
    $stmt->bind_param("i", $player_data['id_player']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Player is in a party but our main query didn't catch it
        // Refresh the player data
        $stmt = $conn->prepare($player_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $player_data = $result->fetch_assoc();
        
        // Try to get party members again
        if (!empty($player_data['id_party'])) {
            $stmt = $conn->prepare($members_query);
            $stmt->bind_param("i", $player_data['id_party']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($member = $result->fetch_assoc()) {
                $party_members[] = $member;
            }
        }
    }
}

// Get pending invitations for current player
$pending_invitations = [];
// Check if party_invitations table exists
$check_table_query = "SHOW TABLES LIKE 'party_invitations'";
$table_result = $conn->query($check_table_query);

if ($table_result && $table_result->num_rows > 0) {
    $invitations_query = "SELECT pi.id_invitation, pi.invited_at, pt.nama_party, p.nickname as inviter_nickname, p.username as inviter_username
                         FROM party_invitations pi 
                         JOIN party pt ON pi.id_party = pt.id_party 
                         JOIN player p ON pi.id_inviter = p.id_player 
                         WHERE pi.id_invited = ? AND pi.status = 'pending' AND pi.expires_at > NOW()
                         ORDER BY pi.invited_at DESC";
    $stmt = $conn->prepare($invitations_query);
    $stmt->bind_param("i", $player_data['id_player']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($invitation = $result->fetch_assoc()) {
        $pending_invitations[] = $invitation;
    }
}

// Get sent invitations for current player (if they're in a party)
$sent_invitations = [];
if (!empty($player_data['id_party']) && $table_result && $table_result->num_rows > 0) {
    $sent_invitations_query = "SELECT pi.id_invitation, pi.invited_at, pi.status, p.nickname as invited_nickname, p.username as invited_username
                              FROM party_invitations pi 
                              JOIN player p ON pi.id_invited = p.id_player 
                              WHERE pi.id_party = ? AND pi.status = 'pending' AND pi.expires_at > NOW()
                              ORDER BY pi.invited_at DESC";
    $stmt = $conn->prepare($sent_invitations_query);
    $stmt->bind_param("i", $player_data['id_party']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($invitation = $result->fetch_assoc()) {
        $sent_invitations[] = $invitation;
    }
}

// Get team members and details if player is in a team
$team_members = [];
$team_details = [];
if (!empty($player_data['id_team'])) {
    // Get team details
    $team_query = "SELECT t.*, 
                          (SELECT COUNT(*) FROM team_player tp2 WHERE tp2.id_team = t.id_team) as team_members_count
                   FROM team t 
                   WHERE t.id_team = ?";
    $stmt = $conn->prepare($team_query);
    $stmt->bind_param("i", $player_data['id_team']);
    $stmt->execute();
    $result = $stmt->get_result();
    $team_details = $result->fetch_assoc();
    
    // Get team members
    $team_members_query = "SELECT p.username, p.nickname, p.idGame,
                                 (t.id_leader = p.id_player) as is_team_leader
                          FROM team_player tp 
                          JOIN player p ON tp.id_player = p.id_player 
                          JOIN team t ON tp.id_team = t.id_team
                          WHERE tp.id_team = ? 
                          ORDER BY is_team_leader DESC, p.username";
    $stmt = $conn->prepare($team_members_query);
    $stmt->bind_param("i", $player_data['id_team']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($member = $result->fetch_assoc()) {
        $team_members[] = $member;
    }
}

// Get pending team invitations for current player
$pending_team_invitations = [];
// Check if team_invitations table exists
$check_team_table_query = "SHOW TABLES LIKE 'team_invitations'";
$team_table_result = $conn->query($check_team_table_query);

if ($team_table_result && $team_table_result->num_rows > 0) {
    $team_invitations_query = "SELECT ti.id_invitation, ti.invited_at, t.nama_team, p.nickname as inviter_nickname, p.username as inviter_username
                              FROM team_invitations ti 
                              JOIN team t ON ti.id_team = t.id_team 
                              JOIN player p ON ti.id_inviter = p.id_player 
                              WHERE ti.id_invited = ? AND ti.status = 'pending' AND ti.expires_at > NOW()
                              ORDER BY ti.invited_at DESC";
    $stmt = $conn->prepare($team_invitations_query);
    $stmt->bind_param("i", $player_data['id_player']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($invitation = $result->fetch_assoc()) {
        $pending_team_invitations[] = $invitation;
    }
}

// Get sent team invitations for current player (if they're team leader)
$sent_team_invitations = [];
if (!empty($player_data['id_team']) && $team_table_result && $team_table_result->num_rows > 0) {
    // Check if current player is team leader
    $check_team_leader_query = "SELECT id_leader FROM team WHERE id_team = ?";
    $stmt = $conn->prepare($check_team_leader_query);
    $stmt->bind_param("i", $player_data['id_team']);
    $stmt->execute();
    $team_leader_result = $stmt->get_result();
    $team_leader_data = $team_leader_result->fetch_assoc();
    
    if ($team_leader_data && $team_leader_data['id_leader'] == $player_data['id_player']) {
        $sent_team_invitations_query = "SELECT ti.id_invitation, ti.invited_at, ti.status, p.nickname as invited_nickname, p.username as invited_username
                                       FROM team_invitations ti 
                                       JOIN player p ON ti.id_invited = p.id_player 
                                       WHERE ti.id_team = ? AND ti.status = 'pending' AND ti.expires_at > NOW()
                                       ORDER BY ti.invited_at DESC";
        $stmt = $conn->prepare($sent_team_invitations_query);
        $stmt->bind_param("i", $player_data['id_team']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($invitation = $result->fetch_assoc()) {
            $sent_team_invitations[] = $invitation;
        }
    }
}

// Get tournament registrations for current player
$tournament_registrations = [];
$tournament_query = "SELECT tr.*, t.nama_turnamen, t.format, t.registration_start, t.registration_end, t.max_participants, t.current_participants
                    FROM tournament_registrations tr
                    JOIN turnamen t ON tr.id_turnamen = t.id_turnamen
                    WHERE tr.registered_by = ?
                    ORDER BY tr.registration_date DESC";
$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $player_data['id_player']);
$stmt->execute();
$result = $stmt->get_result();

while ($registration = $result->fetch_assoc()) {
    $tournament_registrations[] = $registration;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/PLAYER/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <div class="nav-item" onclick="window.location.href='../../index.php'">
                <i class="fas fa-home"></i>
            </div>
            <div class="nav-item" onclick="showTournamentHistorySection()">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="nav-item" onclick="window.location.href='menuTeams.php'">
                <i class="fas fa-users"></i>
            </div>
            <div class="nav-item active" onclick="showProfileSection()">
                <i class="fas fa-user"></i>
            </div>
            <div class="nav-item" onclick="showTeamSection()">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="nav-item">
                <i class="fas fa-cog"></i>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Welcome, <?php echo htmlspecialchars($player_data['username'] ?? $player_data['username']); ?></h1>
                <p class="date-text"><?php echo date('D, d M Y'); ?></p>
            </div>
            <div class="header-right">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search">
                </div>
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="profile-avatar">
                    <img src="../../ASSETS/user.png" alt="Profile" class="avatar-img">
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Gradient Banner -->
            <div class="gradient-banner"></div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="profile-section">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar-large">
                            <img src="../../ASSETS/user.png" alt="<?php echo htmlspecialchars($player_data['nickname'] ?? $player_data['username']); ?>" class="avatar-large">
                        </div>
                        <div class="profile-details">
                            <h2 class="profile-name"><?php echo htmlspecialchars($player_data['nickname'] ?? $player_data['username']); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($player_data['email']); ?></p>
                            <p class="profile-status">
                                Status: <span class="status-<?php echo $player_data['status']; ?>"><?php echo ucfirst($player_data['status']); ?></span>
                            </p>
                        </div>
                    </div>
                    <button class="edit-btn" id="editBtn">Edit Profile</button>
                </div>

                <!-- Profile Form Section -->
                <form method="POST" id="profileForm" class="form-section">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($player_data['username']); ?>" class="form-input" disabled readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($player_data['email']); ?>" class="form-input" disabled readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nickname">Nickname</label>
                            <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($player_data['nickname'] ?? ''); ?>" placeholder="Your In-Game Nickname" class="form-input" disabled>
                        </div>
                        <div class="form-group">
                            <label for="idGame">Game ID</label>
                            <input type="text" id="idGame" name="idGame" value="<?php echo htmlspecialchars($player_data['idGame'] ?? ''); ?>" placeholder="Your Game ID" class="form-input" disabled>
                        </div>
                    </div>
                    <button type="submit" id="saveBtn" class="save-btn" style="display: none;">Save Changes</button>
                </form>

                <!-- Player Statistics Section -->
                <div class="stats-section">
                    <h3 class="section-title">Player Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $player_data['win'] ?? 0; ?></div>
                                <div class="stat-label">Wins</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $player_data['lose'] ?? 0; ?></div>
                                <div class="stat-label">Losses</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo ($player_data['win'] + $player_data['lose']) ?? 0; ?></div>
                                <div class="stat-label">Total Matches</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">
                                    <?php 
                                    $total_matches = ($player_data['win'] + $player_data['lose']) ?? 0;
                                    $wins = $player_data['win'] ?? 0;
                                    $win_rate = $total_matches > 0 ? round(($wins / $total_matches) * 100, 1) : 0;
                                    echo $win_rate . '%';
                                    ?>
                                </div>
                                <div class="stat-label">Win Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Management Section Container -->
                <div class="management-container">
                    <!-- Party Management Section -->
                    <div id="party-management" class="management-section active">
                        <div class="party-section">
                            <h3 class="section-title">
                                Party Information
                                <?php if (!empty($player_data['nama_party'])): ?>
                                    <span class="party-stats">W: <?php echo $player_data['party_win'] ?? 0; ?> | L: <?php echo $player_data['party_lose'] ?? 0; ?> | Total: <?php echo ($player_data['party_win'] + $player_data['party_lose']) ?? 0; ?></span>
                                <?php endif; ?>
                            </h3>
                    
                    <?php if (empty($player_data['nama_party'])): ?>
                    <!-- Create Party Form -->
                    <div class="party-create">
                        <p class="info-text">You are not in any party. Create one to start your journey!</p>
                        <form method="POST" class="create-party-form">
                            <input type="hidden" name="action" value="create_party">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="party_name" placeholder="Enter party name" class="form-input" required>
                                </div>
                                <button type="submit" class="create-btn">Create Party</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Party Info and Members -->
                    <div class="party-info">
                        <div class="party-header">
                            <h4 class="party-name"><?php echo htmlspecialchars($player_data['nama_party']); ?></h4>
                            <span class="party-member-count"><?php echo count($party_members); ?>/5 Members</span>
                        </div>
                        
                        <!-- Party Members List -->
                        <div class="party-members">
                            <?php foreach ($party_members as $member): ?>
                            <div class="member-item">
                                <div class="member-info">
                                    <span class="member-name"><?php echo htmlspecialchars($member['nickname']); ?></span>
                                    <?php if ($member['is_leader']): ?>
                                        <span class="leader-badge">Leader</span>
                                    <?php endif; ?>
                                    <span class="member-username">@<?php echo htmlspecialchars($member['username']); ?></span>
                                    <span class="member-id">ID: <?php echo htmlspecialchars($member['idGame']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Invite Member Form (only if not full and user is party leader) -->
                        <?php 
                        // Check if current player is party leader for invite form
                        $is_party_leader = false;
                        if (!empty($player_data['id_party'])) {
                            $check_leader_query = "SELECT id_leader FROM party WHERE id_party = ?";
                            $stmt = $conn->prepare($check_leader_query);
                            $stmt->bind_param("i", $player_data['id_party']);
                            $stmt->execute();
                            $leader_result = $stmt->get_result();
                            $leader_data = $leader_result->fetch_assoc();
                            $is_party_leader = ($leader_data['id_leader'] == $player_data['id_player']);
                        }
                        ?>
                        <?php if (count($party_members) < 5 && $is_party_leader): ?>
                        <div class="invite-section">
                            <form method="POST" class="invite-form">
                                <input type="hidden" name="action" value="invite_member">
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="text" name="invite_username" placeholder="Enter username to send invitation" class="form-input" required>
                                    </div>
                                    <button type="submit" class="invite-btn">Send Invitation</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Pending Sent Invitations (only for party leader) -->
                        <?php if (!empty($sent_invitations) && $is_party_leader): ?>
                        <div class="sent-invitations">
                            <h5 class="subsection-title">Pending Invitations Sent</h5>
                            <div class="invitations-list">
                                <?php foreach ($sent_invitations as $invitation): ?>
                                <div class="invitation-item">
                                    <div class="invitation-info">
                                        <span class="invited-player"><?php echo htmlspecialchars($invitation['invited_nickname']); ?></span>
                                        <span class="invitation-username">@<?php echo htmlspecialchars($invitation['invited_username']); ?></span>
                                        <span class="invitation-date">Sent: <?php echo date('M j, Y H:i', strtotime($invitation['invited_at'])); ?></span>
                                    </div>
                                    <span class="invitation-status pending">Waiting for response</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Party Management -->
                        <div class="party-management">
                            <div class="management-buttons">
                                <!-- Leave/Disband Party Button -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to leave this party?')">
                                    <input type="hidden" name="action" value="leave_party">
                                    <button type="submit" class="danger-btn">
                                        <?php echo $is_party_leader ? 'Disband Party' : 'Leave Party'; ?>
                                    </button>
                                </form>
                                
                                <!-- Kick Member Form (only for leaders) -->
                                <?php if ($is_party_leader && count($party_members) > 1): ?>
                                <button type="button" class="warning-btn" onclick="toggleKickForm()">Kick Member</button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Kick Member Form (hidden by default) -->
                            <?php if ($is_party_leader && count($party_members) > 1): ?>
                            <div id="kickMemberForm" class="kick-form" style="display: none;">
                                <form method="POST" class="kick-member-form">
                                    <input type="hidden" name="action" value="kick_member">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <select name="kick_username" class="form-select" required>
                                                <option value="">Select member to kick</option>
                                                <?php foreach ($party_members as $member): ?>
                                                    <?php if ($member['username'] !== $player_data['username']): ?>
                                                    <option value="<?php echo htmlspecialchars($member['username']); ?>">
                                                        <?php echo htmlspecialchars($member['nickname']); ?> (@<?php echo htmlspecialchars($member['username']); ?>)
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="danger-btn" onclick="return confirm('Are you sure you want to kick this member?')">Kick</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Party Invitations Section -->
                <?php if (!empty($pending_invitations)): ?>
                <div class="party-invitations-section">
                    <h3 class="section-title">Party Invitations</h3>
                    <div class="invitations-list">
                        <?php foreach ($pending_invitations as $invitation): ?>
                        <div class="invitation-card">
                            <div class="invitation-header">
                                <h4 class="party-name"><?php echo htmlspecialchars($invitation['nama_party']); ?></h4>
                                <span class="invitation-date"><?php echo date('M j, Y H:i', strtotime($invitation['invited_at'])); ?></span>
                            </div>
                            <div class="invitation-details">
                                <p class="invitation-text">
                                    <strong><?php echo htmlspecialchars($invitation['inviter_nickname']); ?></strong> 
                                    (@<?php echo htmlspecialchars($invitation['inviter_username']); ?>) invited you to join their party.
                                </p>
                            </div>
                            <div class="invitation-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="accept_invitation">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id_invitation']; ?>">
                                    <button type="submit" class="accept-btn">Accept</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="decline_invitation">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id_invitation']; ?>">
                                    <button type="submit" class="decline-btn">Decline</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Team Invitations Section -->
                <?php if (!empty($pending_team_invitations)): ?>
                <div class="team-invitations-section">
                    <h3 class="section-title">Team Invitations</h3>
                    <div class="team-invitations-list">
                        <?php foreach ($pending_team_invitations as $invitation): ?>
                        <div class="team-invitation-card">
                            <div class="invitation-header">
                                <h4 class="team-name"><?php echo htmlspecialchars($invitation['nama_team']); ?></h4>
                                <span class="invitation-date"><?php echo date('M j, Y H:i', strtotime($invitation['invited_at'])); ?></span>
                            </div>
                            <div class="invitation-details">
                                <p class="invitation-text">
                                    <strong><?php echo htmlspecialchars($invitation['inviter_nickname']); ?></strong> 
                                    (@<?php echo htmlspecialchars($invitation['inviter_username']); ?>) invited you to join their team.
                                </p>
                            </div>
                            <div class="invitation-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="accept_team_invitation">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id_invitation']; ?>">
                                    <button type="submit" class="accept-btn">Accept</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="decline_team_invitation">
                                    <input type="hidden" name="invitation_id" value="<?php echo $invitation['id_invitation']; ?>">
                                    <button type="submit" class="decline-btn">Decline</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tournament History Section -->
                    <div id="tournament-history" class="management-section" style="display: none;">
                        <div class="tournament-section">
                            <h3 class="section-title">Tournament History</h3>
                            
                            <?php if (empty($tournament_registrations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-trophy fa-3x"></i>
                                <p class="info-text">You haven't registered for any tournaments yet.</p>
                                <a href="../../index.php" class="action-btn">Browse Tournaments</a>
                            </div>
                            <?php else: ?>
                            <div class="tournament-list">
                                <?php foreach ($tournament_registrations as $registration): ?>
                                <div class="tournament-card">
                                    <div class="tournament-info">
                                        <h4 class="tournament-name"><?php echo htmlspecialchars($registration['nama_turnamen']); ?></h4>
                                        <div class="tournament-details">
                                            <span class="tournament-format">Format: <?php echo htmlspecialchars($registration['format']); ?></span>
                                            <span class="registration-date">Registered: <?php echo date('M j, Y', strtotime($registration['registration_date'])); ?></span>
                                            <span class="registration-type">Type: <?php echo ucfirst($registration['registration_type']); ?></span>
                                        </div>
                                    </div>
                                    <div class="tournament-status">
                                        <span class="status-badge status-<?php echo $registration['status']; ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Team Management Section -->
                    <div id="team-management" class="management-section" style="display: none;">
                        <div class="team-section">
                            <h3 class="section-title">Team Management</h3>
                            
                            <?php if (empty($player_data['nama_team'])): ?>
                            <!-- Create Team Section -->
                            <div class="team-create">
                                <?php if (empty($player_data['nama_party'])): ?>
                                <p class="info-text">You need to be in a party to create a team.</p>
                                <?php elseif ($player_data['win'] < 3): ?>
                                <p class="info-text">Your party needs to win <?php echo (3 - $player_data['win']); ?> more times to create a team.</p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($player_data['win'] / 3) * 100; ?>%"></div>
                                </div>
                                <?php else: ?>
                                <p class="info-text">Congratulations! Your party can now create a team.</p>
                                <form method="POST" class="create-team-form">
                                    <input type="hidden" name="action" value="create_team">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <input type="text" name="team_name" placeholder="Enter team name (cannot be changed later)" class="form-input" required>
                                        </div>
                                        <button type="submit" class="create-btn">Create Team</button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <!-- Team Info and Management -->
                            <div class="team-info">
                                <div class="team-header">
                                    <h4 class="team-name"><?php echo htmlspecialchars($player_data['nama_team']); ?></h4>
                                    <div class="team-stats">
                                        <span class="team-stat">Members: <?php echo count($team_members); ?></span>
                                        <span class="team-stat">Wins: <?php echo $team_details['win'] ?? 0; ?></span>
                                        <span class="team-stat">Points: <?php echo $team_details['point'] ?? 0; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Team Description -->
                                <div class="team-description">
                                    <div class="description-header">
                                        <h5 class="subsection-title">Team Description</h5>
                                        <?php 
                                        // Check if current user is team leader
                                        $is_team_leader = ($team_details['id_leader'] == $player_data['id_player']);
                                        if ($is_team_leader): 
                                        ?>
                                        <button type="button" class="edit-description-btn" id="editDescriptionBtn">Edit</button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Display Mode -->
                                    <div id="description-display" class="description-display">
                                        <p class="description-text">
                                            <?php echo htmlspecialchars($team_details['deskripsi_team'] ?? 'No description available'); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Edit Mode (hidden by default) -->
                                    <?php if ($is_team_leader): ?>
                                    <div id="description-edit" class="description-edit" style="display: none;">
                                        <form method="POST" class="edit-description-form">
                                            <input type="hidden" name="action" value="edit_team">
                                            <div class="form-group">
                                                <textarea name="team_description" class="form-textarea" rows="4" placeholder="Enter team description..." maxlength="500"><?php echo htmlspecialchars($team_details['deskripsi_team'] ?? ''); ?></textarea>
                                                <div class="char-counter">
                                                    <span id="char-count"><?php echo strlen($team_details['deskripsi_team'] ?? ''); ?></span>/500
                                                </div>
                                            </div>
                                            <div class="edit-buttons">
                                                <button type="submit" class="save-description-btn">Save</button>
                                                <button type="button" class="cancel-description-btn" id="cancelDescriptionBtn">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Team Members List -->
                                <div class="team-members">
                                    <div class="members-header">
                                        <h5 class="subsection-title">Team Members</h5>
                                        <?php if ($is_team_leader): ?>
                                        <button type="button" class="manage-members-btn" id="manageMembersBtn">Manage Members</button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Current Team Members -->
                                    <div class="members-list">
                                        <?php foreach ($team_members as $index => $member): ?>
                                        <div class="member-item" id="member-<?php echo $index; ?>">
                                            <div class="member-display">
                                                <div class="member-info">
                                                    <span class="member-name"><?php echo htmlspecialchars($member['nickname']); ?></span>
                                                    <span class="member-username">@<?php echo htmlspecialchars($member['username']); ?></span>
                                                    <span class="member-id">ID: <?php echo htmlspecialchars($member['idGame']); ?></span>
                                                    <?php if ($member['is_team_leader']): ?>
                                                        <span class="leader-badge">Team Leader</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($is_team_leader && !$member['is_team_leader']): ?>
                                                <div class="member-actions" style="display: none;">
                                                    <button type="button" class="remove-member-btn" onclick="removeMember('<?php echo htmlspecialchars($member['username']); ?>', '<?php echo htmlspecialchars($member['nickname']); ?>')">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Invite Member Form (only if team not full and user is leader) -->
                                    <?php if ($is_team_leader && count($team_members) < 5): ?>
                                    <div class="invite-member-section" style="margin-top: 20px;">
                                        <h6 class="invite-title">Invite New Member</h6>
                                        <form method="POST" class="invite-member-form">
                                            <input type="hidden" name="action" value="invite_team_member">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <input type="text" name="invite_username" placeholder="Enter username to invite to team" class="form-input" required>
                                                </div>
                                                <button type="submit" class="invite-btn">Send Invitation</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Pending Sent Team Invitations (only for team leader) -->
                                    <?php if (!empty($sent_team_invitations) && isset($is_team_leader) && $is_team_leader): ?>
                                    <div class="sent-team-invitations" style="margin-top: 20px;">
                                        <h6 class="invite-title">Pending Team Invitations Sent</h6>
                                        <div class="team-invitations-list">
                                            <?php foreach ($sent_team_invitations as $invitation): ?>
                                            <div class="team-invitation-item">
                                                <div class="invitation-info">
                                                    <span class="invited-player"><?php echo htmlspecialchars($invitation['invited_nickname']); ?></span>
                                                    <span class="invitation-username">@<?php echo htmlspecialchars($invitation['invited_username']); ?></span>
                                                    <span class="invitation-date">Sent: <?php echo date('M j, Y H:i', strtotime($invitation['invited_at'])); ?></span>
                                                </div>
                                                <span class="invitation-status pending">Waiting for response</span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Team Statistics -->
                                <div class="team-statistics">
                                    <h5 class="subsection-title">Team Statistics</h5>
                                    <div class="stats-grid">
                                        <div class="stat-card">
                                            <div class="stat-number"><?php echo $team_details['win'] ?? 0; ?></div>
                                            <div class="stat-label">Wins</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-number"><?php echo $team_details['lose'] ?? 0; ?></div>
                                            <div class="stat-label">Losses</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-number"><?php echo ($team_details['win'] + $team_details['lose']) ?? 0; ?></div>
                                            <div class="stat-label">Total Matches</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-number"><?php echo $team_details['point'] ?? 0; ?></div>
                                            <div class="stat-label">Points</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-number">
                                                <?php 
                                                $team_total_matches = ($team_details['win'] + $team_details['lose']) ?? 0;
                                                $team_wins = $team_details['win'] ?? 0;
                                                $team_win_rate = $team_total_matches > 0 ? round(($team_wins / $team_total_matches) * 100, 1) : 0;
                                                echo $team_win_rate . '%';
                                                ?>
                                            </div>
                                            <div class="stat-label">Win Rate</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-number"><?php echo count($team_members); ?></div>
                                            <div class="stat-label">Active Members</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tournament History Section -->
            <div id="tournament-history" class="management-section" style="display: none;">
                <div class="tournament-history-section">
                    <h3 class="section-title">Riwayat Turnamen</h3>
                    
                    <?php if (!empty($tournament_registrations)): ?>
                        <div class="tournament-cards">
                            <?php foreach ($tournament_registrations as $registration): ?>
                                <div class="tournament-history-card">
                                    <div class="tournament-card-header">
                                        <h4 class="tournament-name"><?php echo htmlspecialchars($registration['nama_turnamen']); ?></h4>
                                        <span class="tournament-status status-<?php echo $registration['status']; ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </div>
                                    <div class="tournament-card-body">
                                        <div class="tournament-detail">
                                            <span class="detail-label">Format:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($registration['format']); ?></span>
                                        </div>
                                        <div class="tournament-detail">
                                            <span class="detail-label">Tipe Pendaftaran:</span>
                                            <span class="detail-value"><?php echo ucfirst($registration['registration_type']); ?></span>
                                        </div>
                                        <div class="tournament-detail">
                                            <span class="detail-label">Tanggal Daftar:</span>
                                            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($registration['registration_date'])); ?></span>
                                        </div>
                                        <div class="tournament-detail">
                                            <span class="detail-label">Peserta:</span>
                                            <span class="detail-value"><?php echo $registration['current_participants']; ?>/<?php echo $registration['max_participants']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-tournaments-message">
                            <div class="message-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h4>Belum Ada Turnamen</h4>
                            <p>Anda belum mendaftar turnamen apapun. <a href="menuTournament.php">Lihat turnamen yang tersedia</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../../SCRIPT/PLAYER/profile.js"></script>
    <script>
        // Functions for switching between different sections
        function showProfileSection() {
            // Show profile sections and hide others
            document.getElementById('party-management').style.display = 'block';
            document.getElementById('team-management').style.display = 'block';
            document.getElementById('tournament-history').style.display = 'none';
            document.querySelector('.profile-section').style.display = 'block';
            
            // Update active classes
            document.getElementById('party-management').classList.add('active');
            document.getElementById('team-management').classList.remove('active');
            
            // Update sidebar active state
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }
        
        function showTeamSection() {
            // Show profile sections
            document.getElementById('party-management').style.display = 'block';
            document.getElementById('team-management').style.display = 'block';
            document.getElementById('tournament-history').style.display = 'none';
            document.querySelector('.profile-section').style.display = 'block';
            
            // Hide party section and show team section
            document.getElementById('party-management').classList.remove('active');
            document.getElementById('team-management').classList.add('active');
            
            // Update sidebar active state
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }
        
        function showTournamentHistorySection() {
            // Hide profile sections and show tournament history
            document.getElementById('party-management').style.display = 'none';
            document.getElementById('team-management').style.display = 'none';
            document.getElementById('tournament-history').style.display = 'block';
            document.querySelector('.profile-section').style.display = 'none';
            
            // Update sidebar active state
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }
        
        // Set default active section (party management) and profile nav active
        document.addEventListener('DOMContentLoaded', function() {
            // Show party management by default
            document.getElementById('party-management').classList.add('active');
            document.getElementById('team-management').classList.remove('active');
            
            // Set profile nav item as active by default
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            document.querySelector('.nav-item:nth-child(4)').classList.add('active'); // Profile icon is 4th nav-item
            
            // Team description edit functionality
            const editDescriptionBtn = document.getElementById('editDescriptionBtn');
            const cancelDescriptionBtn = document.getElementById('cancelDescriptionBtn');
            const descriptionDisplay = document.getElementById('description-display');
            const descriptionEdit = document.getElementById('description-edit');
            const textarea = document.querySelector('textarea[name="team_description"]');
            const charCount = document.getElementById('char-count');
            
            if (editDescriptionBtn) {
                editDescriptionBtn.addEventListener('click', function() {
                    descriptionDisplay.style.display = 'none';
                    descriptionEdit.style.display = 'block';
                    editDescriptionBtn.style.display = 'none';
                    textarea.focus();
                });
            }
            
            if (cancelDescriptionBtn) {
                cancelDescriptionBtn.addEventListener('click', function() {
                    descriptionDisplay.style.display = 'block';
                    descriptionEdit.style.display = 'none';
                    editDescriptionBtn.style.display = 'inline-block';
                    
                    // Reset textarea to original value
                    textarea.value = textarea.defaultValue;
                    updateCharCount();
                });
            }
            
            if (textarea && charCount) {
                textarea.addEventListener('input', updateCharCount);
                
                function updateCharCount() {
                    const currentLength = textarea.value.length;
                    charCount.textContent = currentLength;
                    
                    // Change color based on character count
                    if (currentLength > 450) {
                        charCount.style.color = '#ef4444';
                    } else if (currentLength > 400) {
                        charCount.style.color = '#f59e0b';
                    } else {
                        charCount.style.color = '#9ca3af';
                    }
                }
            }
        });
        
        // Team member management functions
        function toggleMemberManagement() {
            const memberActions = document.querySelectorAll('.member-actions');
            const manageMembersBtn = document.getElementById('manageMembersBtn');
            const isManaging = manageMembersBtn.textContent === 'Done Managing';
            
            memberActions.forEach(action => {
                action.style.display = isManaging ? 'none' : 'flex';
            });
            
            manageMembersBtn.textContent = isManaging ? 'Manage Members' : 'Done Managing';
            manageMembersBtn.classList.toggle('active', !isManaging);
        }
        
        function removeMember(username, nickname) {
            if (confirm(`Are you sure you want to remove ${nickname} from the team?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'remove_member';
                
                const usernameInput = document.createElement('input');
                usernameInput.type = 'hidden';
                usernameInput.name = 'member_username';
                usernameInput.value = username;
                
                form.appendChild(actionInput);
                form.appendChild(usernameInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Add event listener for manage members button
        document.addEventListener('DOMContentLoaded', function() {
            const manageMembersBtn = document.getElementById('manageMembersBtn');
            if (manageMembersBtn) {
                manageMembersBtn.addEventListener('click', toggleMemberManagement);
            }
        });

        // Navigation functions for sidebar
        function showProfileSection() {
            hideAllSections();
            const profileSection = document.querySelector('.profile-section');
            const partyManagement = document.getElementById('party-management');
            
            if (profileSection) profileSection.style.display = 'block';
            if (partyManagement) partyManagement.style.display = 'block';
            
            // Update active nav item
            updateActiveNavItem(3); // Profile is the 4th item (index 3)
        }

        function showTeamSection() {
            hideAllSections();
            const profileSection = document.querySelector('.profile-section');
            const teamManagement = document.getElementById('team-management');
            
            if (profileSection) profileSection.style.display = 'block';
            if (teamManagement) teamManagement.style.display = 'block';
            
            // Update active nav item
            updateActiveNavItem(4); // Team is the 5th item (index 4)
        }

        function showTournamentHistorySection() {
            hideAllSections();
            const profileSection = document.querySelector('.profile-section');
            const tournamentHistory = document.getElementById('tournament-history');
            
            if (profileSection) profileSection.style.display = 'block';
            if (tournamentHistory) tournamentHistory.style.display = 'block';
            
            // Update active nav item
            updateActiveNavItem(1); // Tournament is the 2nd item (index 1)
        }

        function hideAllSections() {
            const sections = document.querySelectorAll('.management-section');
            sections.forEach(section => {
                section.style.display = 'none';
            });
        }

        function updateActiveNavItem(index) {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach((item, i) => {
                if (i === index) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');

            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
