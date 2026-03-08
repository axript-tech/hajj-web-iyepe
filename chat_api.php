<?php
// chat_api.php
ob_start(); // Prevent PHP warnings from breaking JSON output
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$action = $_POST['action'] ?? '';

// Authorization Check
function checkAuth($conn, $user_id, $batch_id, $role) {
    if ($role !== 'admin') {
        $check = $conn->query("SELECT id FROM bookings WHERE member_id='$user_id' AND trip_batch_id='$batch_id' AND booking_status='confirmed'");
        if ($check && $check->num_rows === 0) {
            ob_clean();
            echo json_encode(['error' => 'Access Denied: You do not belong to this trip batch.']);
            exit;
        }
    }
}

try {
    // 1. SEND TEXT / LOCATION MESSAGE
    if ($action === 'send') {
        $batch_id = intval($_POST['batch_id']);
        $message = trim($_POST['message']);
        $is_admin = ($role === 'admin') ? 1 : 0;

        if (!empty($message) && $batch_id > 0) {
            checkAuth($conn, $user_id, $batch_id, $role);
            $stmt = $conn->prepare("INSERT INTO trip_messages (trip_batch_id, member_id, message, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $batch_id, $user_id, $message, $is_admin);
            $stmt->execute();
            ob_clean();
            echo json_encode(['status' => 'success']);
        }
        exit;
    }

    // 2. SEND FILE / IMAGE ATTACHMENT
    if ($action === 'send_file') {
        $batch_id = intval($_POST['batch_id']);
        $att_type = $_POST['att_type']; 
        $is_admin = ($role === 'admin') ? 1 : 0;

        checkAuth($conn, $user_id, $batch_id, $role);

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['attachment']['name']));
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            $allowed_images = ['jpg', 'jpeg', 'png', 'gif'];
            $allowed_docs = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
            
            $is_valid = false;
            if ($att_type === 'image' && in_array($ext, $allowed_images)) $is_valid = true;
            if ($att_type === 'document' && in_array($ext, $allowed_docs)) $is_valid = true;

            if ($is_valid) {
                $new_name = "chat_" . time() . "_" . rand(1000,9999) . "." . $ext;
                $upload_dir = 'assets/uploads/chat/';
                
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                
                $filepath = $upload_dir . $new_name;
                
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
                    
                    // FIX: Using explicit concatenation to avoid array index parsing errors
                    $msg_content = ($att_type === 'image') 
                        ? "[IMG]" . $filepath . "[/IMG]" 
                        : "[FILE]" . $filepath . "|" . $filename . "[/FILE]";
                    
                    $stmt = $conn->prepare("INSERT INTO trip_messages (trip_batch_id, member_id, message, is_admin) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iisi", $batch_id, $user_id, $msg_content, $is_admin);
                    $stmt->execute();
                    
                    ob_clean();
                    echo json_encode(['status' => 'success']);
                    exit;
                }
            }
        }
        ob_clean();
        echo json_encode(['error' => 'Upload failed or invalid file type.']);
        exit;
    }

    // 3. FETCH MESSAGES
    if ($action === 'fetch') {
        $batch_id = intval($_POST['batch_id']);
        $last_id = intval($_POST['last_id'] ?? 0);

        checkAuth($conn, $user_id, $batch_id, $role);

        $sql = "SELECT tm.*, m.full_name, m.passport_photo, m.role 
                FROM trip_messages tm 
                JOIN members m ON tm.member_id = m.id 
                WHERE tm.trip_batch_id = '$batch_id' AND tm.id > '$last_id' 
                ORDER BY tm.created_at ASC";
        
        $result = $conn->query($sql);
        $messages = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'id' => $row['id'],
                    'user_id' => $row['member_id'],
                    'name' => ($row['is_admin']) ? 'Admin Support' : explode(' ', $row['full_name'])[0],
                    'photo' => $row['passport_photo'],
                    'message' => nl2br(htmlspecialchars($row['message'])), 
                    'time' => date('h:i A', strtotime($row['created_at'])),
                    'is_me' => ($row['member_id'] == $user_id),
                    'is_admin' => $row['is_admin']
                ];
            }
        }
        
        ob_clean();
        echo json_encode(['messages' => $messages]);
        exit;
    }

    // 4. DELETE MESSAGE (Admin Only)
    if ($action === 'delete') {
        if ($role !== 'admin') {
            ob_clean();
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $msg_id = intval($_POST['msg_id']);
        $conn->query("DELETE FROM trip_messages WHERE id = '$msg_id'");
        ob_clean();
        echo json_encode(['status' => 'deleted']);
        exit;
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
?>