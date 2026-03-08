<?php
// admin/api_room_handler.php
session_start();
require_once '../config/db.php';
require_once '../includes/mailer.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// 2. Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action']);
    exit();
}

// 3. Handle Actions
if ($input['action'] === 'move_pilgrim') {
    $booking_id = intval($input['booking_id']);
    $room_no = $conn->real_escape_string($input['room_number']); // Can be empty string if moving to unassigned
    $city = $input['city']; // 'mecca' or 'medina'
    
    // Validate city
    if (!in_array($city, ['mecca', 'medina'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid city']);
        exit();
    }

    $col = ($city === 'mecca') ? 'mecca_room_no' : 'medina_room_no';
    
    // Update DB
    $sql = "UPDATE bookings SET $col = " . ($room_no ? "'$room_no'" : "NULL") . " WHERE id = '$booking_id'";
    
    if ($conn->query($sql)) {
        
        // If assigned to a room, notify the user
        if (!empty($room_no)) {
            $user_sql = "SELECT m.email, m.full_name FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.id = '$booking_id'";
            $user_res = $conn->query($user_sql)->fetch_assoc();
            
            $city_display = ucfirst($city);
            $msg_body = "Your accommodation in <strong>$city_display</strong> has been updated.<br><br>You have been assigned to <strong>Room $room_no</strong>. You can view your roommates and hotel details on your portal dashboard.";
            send_hajj_mail($user_res['email'], $user_res['full_name'], "$city_display Room Assignment", $msg_body);
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
?>