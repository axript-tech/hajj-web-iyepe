<?php
// includes/header.php

// 1. Path & Session Helper
$is_admin_dir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base_url = $is_admin_dir ? '../' : './';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? 'guest';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abdullateef Hajj & Umrah Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00', lightGold: '#FFF9C4', black: '#000000', white: '#FFFFFF' },
                    fontFamily: { sans: ['Quicksand', 'sans-serif'], }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Quicksand', sans-serif; }
        @media print { nav, .no-print { display: none !important; } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 text-black flex flex-col min-h-screen">
    
    <nav class="bg-deepGreen text-white shadow-md relative z-50">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="<?php echo $base_url . ($user_role === 'admin' ? 'admin/dashboard.php' : 'index.php'); ?>" class="flex items-center gap-3 group">
                <div class="h-10 bg-white p-1 rounded-md shadow-sm group-hover:shadow-md transition">
                    <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" alt="Logo" class="h-full w-auto object-contain">
                </div>
                <div class="hidden md:block">
                    <span class="font-bold text-lg block leading-tight tracking-wide">Abdullateef</span>
                    <span class="text-[10px] text-hajjGold font-bold uppercase tracking-wider">Integrated Hajj & Umrah</span>
                </div>
            </a>
            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="flex items-center gap-4">
                        <span class="text-sm hidden md:inline opacity-90">
                            <?php 
                                if($user_role === 'admin') echo '<i class="fas fa-shield-alt text-hajjGold mr-1"></i> Admin';
                                elseif($user_role === 'manager') echo '<i class="fas fa-user-tie text-hajjGold mr-1"></i> Manager';
                                else echo 'Welcome, Pilgrim'; 
                            ?>
                        </span>
                        <a href="<?php echo $base_url; ?>logout.php" class="bg-white/10 border border-white/20 text-white px-4 py-1.5 rounded text-sm font-bold hover:bg-white hover:text-deepGreen transition">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>index.php" class="text-white hover:text-hajjGold mr-4 text-sm font-bold">Login</a>
                    <a href="<?php echo $base_url; ?>register.php" class="bg-hajjGold text-white px-4 py-2 rounded text-sm font-bold hover:bg-yellow-600 shadow-md">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if(in_array($user_role, ['admin', 'manager'])): ?>
        <div class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40 print:hidden">
            <div class="container mx-auto px-6">
                <ul class="flex flex-nowrap gap-6 text-sm font-bold text-gray-600 overflow-x-auto no-scrollbar whitespace-nowrap">
                    <li><a href="<?php echo $base_url; ?>admin/dashboard.php" class="py-4 block border-b-4 <?php echo ($current_page == 'dashboard.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-tachometer-alt mr-1"></i> Dashboard</a></li>
                    
                    <li><a href="<?php echo $base_url; ?>admin/manage_trips.php" class="py-4 block border-b-4 <?php echo ($current_page == 'manage_trips.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-calendar-alt mr-1"></i> Trips</a></li>
                    
                    <!-- NEW BATCH MANAGER LINK -->
                    <li><a href="<?php echo $base_url; ?>admin/batch_manager.php" class="py-4 block border-b-4 <?php echo ($current_page == 'batch_manager.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-layer-group mr-1"></i> Batching</a></li>

                    <li><a href="<?php echo $base_url; ?>admin/members_list.php" class="py-4 block border-b-4 <?php echo ($current_page == 'members_list.php' || $current_page == 'create_member.php' || $current_page == 'member_profile.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-users mr-1"></i> Pilgrims</a></li>
                    
                    <li><a href="<?php echo $base_url; ?>admin/trip_checkin.php" class="py-4 block border-b-4 <?php echo ($current_page == 'trip_checkin.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-clipboard-check mr-1"></i> Check-in</a></li>
                    
                    <li><a href="<?php echo $base_url; ?>admin/room_manager.php" class="py-4 block border-b-4 <?php echo ($current_page == 'room_manager.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-th-large mr-1"></i> Rooming</a></li>
                    <li><a href="<?php echo $base_url; ?>admin/trip_manifest.php" class="py-4 block border-b-4 <?php echo ($current_page == 'trip_manifest.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-plane-departure mr-1"></i> Manifest</a></li>
                    <li><a href="<?php echo $base_url; ?>admin/manage_packages.php" class="py-4 block border-b-4 <?php echo ($current_page == 'manage_packages.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-kaaba mr-1"></i> Packages</a></li>
                    <li><a href="<?php echo $base_url; ?>admin/manage_addons.php" class="py-4 block border-b-4 <?php echo ($current_page == 'manage_addons.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-plus-square mr-1"></i> Add-ons</a></li>
                    
                    <?php if($user_role === 'admin'): ?>
                        <li><a href="<?php echo $base_url; ?>admin/payments.php" class="py-4 block border-b-4 <?php echo ($current_page == 'payments.php' || $current_page == 'resolve_disputes.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-money-bill-wave mr-1"></i> Ledger</a></li>
                        <li><a href="<?php echo $base_url; ?>admin/manage_roles.php" class="py-4 block border-b-4 <?php echo ($current_page == 'manage_roles.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-user-shield mr-1"></i> Roles</a></li>
                    <?php endif; ?>

                    <li><a href="<?php echo $base_url; ?>admin/announcements.php" class="py-4 block border-b-4 <?php echo ($current_page == 'announcements.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-bullhorn mr-1"></i> Comms</a></li>
                    
                    <!-- Admin Chat Link -->
                    <li><a href="<?php echo $base_url; ?>admin/trip_chat.php" class="py-4 block border-b-4 <?php echo ($current_page == 'trip_chat.php') ? 'border-hajjGold text-deepGreen' : 'border-transparent hover:text-deepGreen hover:border-gray-300'; ?> transition"><i class="fas fa-comments mr-1"></i> Chat</a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <main class="flex-grow container mx-auto p-4 md:p-8">