<?php
// forgot_password.php
session_start();
require_once 'config/db.php';
require_once 'includes/mailer.php';

$msg = "";
$msg_type = "";
$step = 1; // 1: Request Email, 2: Reset Form

// STEP 0: Check if accessing via an emailed token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash("sha256", $token);
    
    $check = $conn->query("SELECT email FROM members WHERE reset_token_hash = '$token_hash' AND reset_token_expires_at > NOW()");
    if ($check->num_rows > 0) {
        $step = 2;
        $reset_email = $check->fetch_assoc()['email'];
    } else {
        $msg = "This recovery link is invalid or has expired.";
        $msg_type = "error";
    }
}

// STEP 1: Handle Email Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    $check = $conn->query("SELECT id, full_name FROM members WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $user = $check->fetch_assoc();
        
        // Generate Token
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 mins
        
        $conn->query("UPDATE members SET reset_token_hash = '$token_hash', reset_token_expires_at = '$expiry' WHERE email = '$email'");
        
        // Send Recovery Email
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/forgot_password.php?token=" . $token;
        $email_body = "You recently requested to reset your password for your Abdullateef portal account.<br><br>
                       <a href='$reset_link' style='display:inline-block; padding:10px 20px; background-color:#C8AA00; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;'>Reset Password</a>
                       <br><br>If you did not request this, please ignore this email. This link will expire in 30 minutes.";
        
        send_hajj_mail($email, $user['full_name'], "Password Recovery", $email_body);
        
        $msg = "A password recovery link has been sent to your email address.";
        $msg_type = "success";
    } else {
        $msg = "Email not found in our records.";
        $msg_type = "error";
    }
}

// STEP 2: Handle New Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_new_password'])) {
    $email = $conn->real_escape_string($_POST['reset_email']);
    $pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if ($pass === $confirm) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE members SET password_hash = '$hash', reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = '$email'");
        $msg = "Password updated! You can now login.";
        $msg_type = "success";
        $step = 3; // Show Login Button
    } else {
        $msg = "Passwords do not match.";
        $msg_type = "error";
        $step = 2;
        $reset_email = $email;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Recovery | Abdullateef Hajj</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00' }, fontFamily: { sans: ['Quicksand'] } } } }</script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-deepGreen p-6 text-center">
            <h2 class="text-2xl font-bold text-white">Account Recovery</h2>
            <p class="text-green-100 text-sm">Secure Portal Access</p>
        </div>

        <div class="p-8">
            <?php if($msg): ?>
                <div class="mb-6 p-3 rounded text-sm text-center <?php echo ($msg_type=='success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Enter Email -->
            <?php if($step == 1): ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                            <input type="email" name="email" class="w-full pl-10 p-2 border rounded focus:border-deepGreen outline-none" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    <button type="submit" name="request_reset" class="w-full bg-deepGreen text-white font-bold py-2 rounded hover:bg-teal-800 transition">
                        Send Recovery Link
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <a href="index.php" class="text-sm text-gray-500 hover:text-deepGreen">Back to Login</a>
                </div>

            <!-- STEP 2: New Password -->
            <?php elseif($step == 2): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="reset_email" value="<?php echo htmlspecialchars($reset_email); ?>">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                    </div>
                    <button type="submit" name="set_new_password" class="w-full bg-hajjGold text-white font-bold py-2 rounded hover:bg-yellow-600 transition">
                        Reset Password
                    </button>
                </form>

            <!-- STEP 3: Success -->
            <?php elseif($step == 3): ?>
                <div class="text-center">
                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                    <p class="text-gray-600 mb-6">Your password has been securely updated.</p>
                    <a href="index.php" class="inline-block bg-deepGreen text-white font-bold px-6 py-2 rounded hover:bg-teal-800 transition">
                        Login Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>