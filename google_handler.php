<?php
// google_handler.php
session_start();
require_once 'config/db.php';
require_once 'config/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $jwt = $_POST['credential'];
    
    // Decode the JWT payload (Part 2 of the JWT string)
    $token_parts = explode('.', $jwt);
    if (count($token_parts) === 3) {
        $payload = json_decode(base64_decode(strtr($token_parts[1], '-_', '+/')), true);
        
        if (isset($payload['email'])) {
            $email = $conn->real_escape_string($payload['email']);
            $full_name = $conn->real_escape_string($payload['name'] ?? 'Google User');
            $photo = $conn->real_escape_string($payload['picture'] ?? '');
            
            // Check if user already exists
            $check = $conn->query("SELECT * FROM members WHERE email = '$email'");
            
            if ($check->num_rows > 0) {
                // USER EXISTS: Log them in
                $user = $check->fetch_assoc();
                
                if ($user['status'] === 'banned') {
                    header("Location: login.php?error=Account is banned.");
                    exit();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
                exit();
                
            } else {
                // NEW USER: Register them automatically
                // Generate a random secure password since they use Google to log in
                $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $default_phone = "Update Required"; // Phone is NOT NULL in DB schema
                
                $sql = "INSERT INTO members (full_name, email, phone, password_hash, passport_photo, status) 
                        VALUES ('$full_name', '$email', '$default_phone', '$random_password', '$photo', 'pending')";
                
                if ($conn->query($sql) === TRUE) {
                    $new_user_id = $conn->insert_id;
                    
                    // Initialize empty medical profile
                    $conn->query("INSERT INTO medical_profiles (member_id) VALUES ('$new_user_id')");
                    
                    // Log them in immediately
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['role'] = 'user';
                    
                    // Redirect to dashboard (will show 'Account Under Review' status)
                    header("Location: dashboard.php?msg=google_registered");
                    exit();
                } else {
                    header("Location: register.php?error=Failed to create account via Google.");
                    exit();
                }
            }
        }
    }
}

// Fallback if something fails
header("Location: login.php?error=Google authentication failed.");
exit();
?>