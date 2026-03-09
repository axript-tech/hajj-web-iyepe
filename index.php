<?php
// index.php - Landing Page
session_start();
require_once 'config/constants.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abdullateef Hajj & Umrah | Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandGreen: '#135c55',
                        brandGold: '#d4af37',
                        lightBg: '#f8fafc',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .pattern-overlay {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23135c55' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-clip {
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        @media (max-width: 768px) {
            .hero-clip { clip-path: none; }
        }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen flex flex-col relative pattern-overlay">

    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" alt="Logo" class="h-10 w-auto">
                <div class="hidden sm:block">
                    <h1 class="font-bold text-brandGreen tracking-wide leading-tight">Abdullateef</h1>
                    <p class="text-[10px] text-brandGold uppercase font-bold tracking-widest">Hajj & Umrah</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="login.php" class="text-gray-600 hover:text-brandGreen font-semibold px-4 py-2 transition hidden sm:inline-block">Login</a>
                <a href="register.php" class="bg-brandGreen text-white px-5 py-2 rounded-lg font-semibold hover:bg-teal-800 transition shadow-md">Register Now</a>
            </div>
        </div>
    </nav>

    <!-- Main Hero Section -->
    <div class="relative bg-white hero-clip pb-20 md:pb-32 overflow-hidden border-b border-gray-100 shadow-sm">
        
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-brandGold/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-brandGreen/5 rounded-full blur-3xl translate-y-1/4 -translate-x-1/4"></div>

        <div class="max-w-7xl mx-auto px-6 pt-12 md:pt-20 grid lg:grid-cols-12 gap-12 items-center relative z-10">
            
            <!-- Text Content -->
            <div class="lg:col-span-7 text-center lg:text-left space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-50 border border-green-100 text-brandGreen text-xs font-bold uppercase tracking-wider mb-2 shadow-sm mx-auto lg:mx-0">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    2026 Registration Open
                </div>
                
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-[1.15] tracking-tight">
                    Your Sacred Journey, <br>
                    <span class="text-brandGreen">Managed Perfectly.</span>
                </h2>
                
                <p class="text-lg text-gray-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Experience peace of mind from the moment you register. Our dedicated portal handles your profile, installment payments, and travel itineraries with absolute transparency.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-2">
                    <a href="register.php" class="bg-brandGold text-white font-bold px-8 py-4 rounded-xl hover:bg-yellow-600 transition shadow-lg flex items-center justify-center gap-2 group">
                        Start Application <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="login.php" class="bg-white text-gray-700 border border-gray-300 font-bold px-8 py-4 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition flex items-center justify-center gap-2">
                        <i class="fas fa-lock text-brandGreen"></i> Access Portal
                    </a>
                </div>
            </div>

            <!-- Image/Visual Content -->
            <div class="lg:col-span-5 relative hidden md:block">
                <!-- Welcome Card -->
                <div class="bg-white p-6 rounded-3xl shadow-2xl relative z-20 border border-gray-100 max-w-sm mx-auto transform hover:-translate-y-2 transition duration-500">
                    <div class="absolute -top-6 -right-6 w-20 h-20 bg-brandGreen rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                        <i class="fas fa-kaaba text-white text-2xl"></i>
                    </div>
                    
                    <div class="w-24 h-24 rounded-full overflow-hidden mb-4 mx-auto border-2 border-gray-100">
                        <img src="https://i0.wp.com/abdullateefhajjumrah.com/wp-content/uploads/2025/06/fdd568e5-297d-48f4-b530-c355cbf5593d.png?w=2101&ssl=1" alt="Hon. Iyepe" class="w-full h-full object-cover object-top">
                    </div>
                    <div class="text-center">
                        <h3 class="font-bold text-xl text-gray-800">Welcome to the Family</h3>
                        <p class="text-sm text-gray-500 mb-6">Hon. Iyepe</p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center"><i class="fas fa-check"></i></div>
                            <p class="text-sm font-semibold text-gray-700">Verified Operator</p>
                        </div>
                        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-shield-alt"></i></div>
                            <p class="text-sm font-semibold text-gray-700">Secure Payments</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Features Section -->
    <div class="max-w-7xl mx-auto px-6 py-16 md:py-24 relative z-10 -mt-10 md:mt-0">
        <div class="text-center mb-16">
            <h3 class="text-sm font-bold text-brandGold uppercase tracking-widest mb-2">Why Use Our Portal?</h3>
            <h2 class="text-3xl font-bold text-gray-900">Everything you need in one place.</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition border border-gray-100 group">
                <div class="w-14 h-14 bg-green-50 text-brandGreen rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform"><i class="fas fa-file-medical"></i></div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Health & Safety First</h4>
                <p class="text-gray-600 leading-relaxed text-sm">Upload your medical profile securely. Our system ensures you are medically cleared before travel, prioritizing your well-being.</p>
            </div>
            
            <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition border border-gray-100 group">
                <div class="w-14 h-14 bg-yellow-50 text-brandGold rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform"><i class="fas fa-wallet"></i></div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Flexible Installments</h4>
                <p class="text-gray-600 leading-relaxed text-sm">Pay your commitment fee and subsequent installments at your own pace. Track your balance and download receipts instantly.</p>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition border border-gray-100 group">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform"><i class="fas fa-map-marked-alt"></i></div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Live Itinerary</h4>
                <p class="text-gray-600 leading-relaxed text-sm">View your flight schedules, assigned Makkah and Madinah hotel rooms, and receive important cohort announcements directly.</p>
            </div>
        </div>
    </div>

    <!-- Footer Area -->
    <footer class="mt-auto border-t border-gray-200 bg-white py-8">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500 gap-4">
            <p>&copy; <?php echo date('Y'); ?> Abdullateef Integrated Hajj & Umrah Ltd.</p>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><i class="fas fa-lock text-gray-400"></i> Secure Portal</span>
                <span class="flex items-center gap-1"><i class="fas fa-headset text-gray-400"></i> Support Available</span>
            </div>
        </div>
    </footer>

</body>
</html>