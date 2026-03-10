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
    <title>Login | Abdullateef Hajj App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00' }, fontFamily: { sans: ['Quicksand', 'sans-serif'] } } }
        }
    </script>
    <style>body { font-family: 'Quicksand', sans-serif; }</style>
</head>
<body class="bg-gray-100 font-sans flex flex-col md:flex-row min-h-screen">
          
    <!-- Mobile Header / Desktop Sidebar -->
    <div class="bg-[#1B7D75] pt-10 pb-16 sm:pb-24 px-4 sm:px-6 md:pb-12 md:w-1/3 lg:w-1/4 md:min-h-screen rounded-b-3xl md:rounded-none md:rounded-r-[3rem] shadow-2xl relative flex flex-col justify-center">
        <div class="absolute top-4 left-4 sm:top-6 sm:left-6 md:top-10 md:left-10">
            <a href="index.php" class="text-white flex items-center gap-2 hover:text-[#C8AA00] transition text-sm sm:text-base">
                <i class="fas fa-arrow-left"></i> <span class="hidden md:inline font-bold">Back</span>
            </a>
        </div>
        
        <div class="mt-6 sm:mt-8 md:mt-0 text-center md:text-left md:px-8">
            <div class="bg-white p-2 rounded-xl shadow-sm w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center mb-4 sm:mb-6 mx-auto md:mx-0">
                <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" alt="Logo" class="w-full object-contain">
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-2 sm:mb-3">Welcome Back.</h2>
            <p class="text-green-100 text-xs sm:text-sm md:text-base leading-relaxed">
                Sign in to access your dashboard, manage payments, and track your itinerary.
            </p>
        </div>
    </div>

    <!-- Form Area -->
    <div class="px-4 sm:px-6 -mt-8 sm:-mt-16 md:mt-0 flex-grow flex items-center justify-center md:p-12 py-6 sm:py-10">
        <div class="bg-white p-5 sm:p-8 md:p-10 rounded-2xl sm:rounded-3xl shadow-xl w-full max-w-md border border-gray-100">
            
            <div class="text-center mb-6 sm:mb-8 hidden sm:block">
                <h3 class="text-xl sm:text-2xl font-bold text-gray-800">Sign In to Portal</h3>
                <p class="text-gray-500 mt-1 text-xs sm:text-sm">Enter your details below.</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-xl text-xs sm:text-sm font-bold mb-4 sm:mb-6 text-center border border-red-100 shadow-sm">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-xl text-xs sm:text-sm font-bold mb-4 sm:mb-6 text-center border border-red-100 shadow-sm">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4 sm:space-y-5">
                <div>
                    <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                        <input type="email" name="email" required class="w-full bg-gray-50 pl-9 sm:pl-12 pr-4 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="you@email.com">
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase">Password</label>
                        <a href="forgot_password.php" class="text-[10px] sm:text-xs text-[#1B7D75] font-bold hover:underline">Forgot?</a>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                        <input type="password" id="password" name="password" required class="w-full bg-gray-50 pl-9 sm:pl-12 pr-10 sm:pr-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="••••••••">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 sm:right-4 top-2.5 sm:top-3.5 text-gray-400 hover:text-[#1B7D75] transition focus:outline-none" title="Toggle Password Visibility">
                            <i id="eye-icon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login_submit" class="w-full bg-[#1B7D75] text-white font-bold py-3 sm:py-4 rounded-lg sm:rounded-xl mt-4 sm:mt-6 shadow-md hover:bg-teal-800 active:scale-95 transition text-sm sm:text-lg">
                    Secure Login
                </button>
            </form>

            <div class="mt-6 sm:mt-8 text-center pt-4 sm:pt-6 border-t border-gray-100">
                <p class="text-gray-600 text-xs sm:text-sm">
                    Don't have an account? 
                    <a href="register.php" class="text-[#C8AA00] font-bold hover:underline">Register here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const pwdInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>