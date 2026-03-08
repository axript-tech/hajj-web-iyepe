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
    <title>Abdullateef Hajj & Umrah | Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepGreen: '#1B7D75',
                        hajjGold: '#C8AA00',
                        lightGold: '#FFF9C4',
                        offWhite: '#F9FAFB',
                    },
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>body { font-family: 'Quicksand', sans-serif; }</style>
</head>
<body class="bg-offWhite text-gray-800">

    <!-- Navigation -->
    <nav class="bg-deepGreen text-white py-4 shadow-lg relative z-50">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white p-1 rounded-md shadow-sm">
                    <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" 
                         alt="Logo" class="w-full h-full object-contain">
                </div>
                <div class="hidden md:block">
                    <span class="font-bold text-lg block leading-tight tracking-wide">Abdullateef</span>
                    <span class="text-[10px] text-hajjGold font-bold uppercase tracking-wider">Integrated Hajj & Umrah</span>
                </div>
            </div>
            <div class="flex gap-4">
                <a href="login.php" class="text-white hover:text-hajjGold font-bold transition self-center">Login</a>
                <a href="register.php" class="bg-hajjGold text-white px-6 py-2 rounded-full font-bold hover:bg-yellow-600 transition shadow-md transform hover:scale-105">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative bg-deepGreen text-white min-h-[600px] flex items-center overflow-hidden">
        <!-- Background Image/Pattern -->
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/arabesque.png')]"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-deepGreen via-deepGreen to-transparent"></div>
        
        <div class="container mx-auto px-6 relative z-10 grid md:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div class="inline-block bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-hajjGold animate-pulse">
                    2026 Registration Open
                </div>
                <h1 class="text-5xl md:text-6xl font-bold leading-tight">
                    Embark on your <br>
                    <span class="text-hajjGold">Spiritual Journey</span> <br>
                    with Confidence.
                </h1>
                <p class="text-green-100 text-lg max-w-lg leading-relaxed">
                    Join the "Hon. Iyepe" family. We provide a fully managed, medically secure, and spiritually guided Hajj & Umrah experience.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="register.php" class="bg-hajjGold text-white px-8 py-4 rounded-lg font-bold text-center hover:bg-yellow-600 transition shadow-xl transform hover:-translate-y-1">
                        Start Registration
                    </a>
                    <a href="login.php" class="bg-white/10 border border-white/30 backdrop-blur text-white px-8 py-4 rounded-lg font-bold text-center hover:bg-white hover:text-deepGreen transition">
                        Member Portal
                    </a>
                </div>
            </div>
            
            <!-- Hero Visual (Hidden on mobile) -->
            <div class="hidden md:block relative">
                <div class="absolute -inset-4 bg-hajjGold/20 rounded-full blur-3xl"></div>
                <div class="relative bg-white/5 border border-white/10 p-6 rounded-2xl backdrop-blur-sm shadow-2xl transform rotate-3 hover:rotate-0 transition duration-500">
                    <div class="flex items-center gap-4 mb-4 border-b border-white/10 pb-4">
                        <div class="w-12 h-12 bg-hajjGold rounded-full flex items-center justify-center text-2xl font-bold">A</div>
                        <div>
                            <h3 class="font-bold text-lg">Pilgrim Dashboard</h3>
                            <p class="text-xs text-green-200">Live Status Overview</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="h-2 bg-white/20 rounded w-3/4"></div>
                        <div class="h-2 bg-white/10 rounded w-full"></div>
                        <div class="h-2 bg-white/10 rounded w-5/6"></div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <div class="flex-1 bg-green-500/20 p-3 rounded text-center">
                            <i class="fas fa-check-circle text-green-400 mb-1"></i>
                            <p class="text-[10px] uppercase">Medical</p>
                        </div>
                        <div class="flex-1 bg-yellow-500/20 p-3 rounded text-center">
                            <i class="fas fa-wallet text-yellow-400 mb-1"></i>
                            <p class="text-[10px] uppercase">Wallet</p>
                        </div>
                        <div class="flex-1 bg-blue-500/20 p-3 rounded text-center">
                            <i class="fas fa-plane text-blue-400 mb-1"></i>
                            <p class="text-[10px] uppercase">Flight</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Grid -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-deepGreen mb-4">Why Choose Abdullateef?</h2>
                <div class="w-20 h-1 bg-hajjGold mx-auto"></div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-xl bg-offWhite border border-gray-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-green-100 text-deepGreen rounded-lg flex items-center justify-center text-2xl mb-6 group-hover:bg-deepGreen group-hover:text-white transition">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Health First</h3>
                    <p class="text-gray-600 leading-relaxed">
                        We prioritize your well-being with mandatory medical profiling and dedicated medical staff on every trip.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-xl bg-offWhite border border-gray-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-yellow-100 text-hajjGold rounded-lg flex items-center justify-center text-2xl mb-6 group-hover:bg-hajjGold group-hover:text-white transition">
                        <i class="fas fa-kaaba"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Prime Accommodation</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Stay steps away from the Haram. We secure premium hotels in Makkah and Madinah for your comfort.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-xl bg-offWhite border border-gray-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-6 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Flexible Payments</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Secure your slot with a commitment fee and pay the rest in convenient installments via our portal.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-10 border-t-4 border-hajjGold">
        <div class="container mx-auto px-6 text-center md:text-left flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <h4 class="text-white font-bold text-lg mb-1">Abdullateef Integrated Hajj & Umrah Ltd.</h4>
                <p class="text-xs opacity-60">Lagos, Nigeria</p>
            </div>
            <div class="text-sm">
                &copy; <?php echo date('Y'); ?> All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>