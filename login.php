<?php
// login.php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, password_hash, role, status FROM members WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($user['status'] == 'banned') {
            $error = "This account has been banned. Please contact support.";
        } elseif (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . ($user['role'] == 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Abdullateef Hajj & Umrah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>body { font-family: 'Quicksand', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

    <div class="max-w-md w-full mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-block bg-white p-3 rounded-2xl shadow-sm">
                <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" 
                     alt="Logo" class="h-16 w-auto object-contain mx-auto">
            </a>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
            <h2 class="text-2xl font-bold text-[#1B7D75] mb-2 text-center">Welcome Back</h2>
            <p class="text-gray-500 text-sm text-center mb-8">Log in to your pilgrim dashboard.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm font-bold mb-6 text-center border border-red-100">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm font-bold mb-6 text-center border border-red-100">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Google Sign-In Setup -->
            <div id="g_id_onload"
                 data-client_id="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleResponse"
                 data-auto_prompt="false">
            </div>

            <div class="flex justify-center mb-6">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="100%">
                </div>
            </div>

            <div class="relative flex py-2 items-center mb-6">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-xs font-bold uppercase">Or continue with email</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <form action="login.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#1B7D75] focus:border-[#1B7D75] outline-none transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2 flex justify-between">
                        Password
                        <a href="forgot_password.php" class="text-xs text-[#1B7D75] hover:underline">Forgot?</a>
                    </label>
                    <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#1B7D75] focus:border-[#1B7D75] outline-none transition">
                </div>
                <button type="submit" name="login_submit" class="w-full bg-[#1B7D75] text-white font-bold py-3.5 rounded-xl hover:bg-teal-800 transition shadow-lg mt-4">
                    Sign In
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-8">
                Don't have an account? <a href="register.php" class="text-[#C8AA00] font-bold hover:underline">Register here</a>
            </p>
        </div>
    </div>

    <!-- Hidden form to process Google response -->
    <script>
        function handleGoogleResponse(response) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'google_handler.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'credential';
            input.value = response.credential;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>