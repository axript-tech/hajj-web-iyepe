<?php
// id_card.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];

// Fetch Member Details
// FIX: Changed m.blood_group to mp.blood_group as it resides in medical_profiles table
$sql = "SELECT m.full_name, m.id, m.passport_photo, mp.blood_group, 
               mp.emergency_contact_name, mp.emergency_contact_phone,
               b.booking_status, p.name as package_name
        FROM members m
        LEFT JOIN medical_profiles mp ON m.id = mp.member_id
        LEFT JOIN bookings b ON m.id = b.member_id
        LEFT JOIN packages p ON b.package_id = p.id
        WHERE m.id = '$user_id'";
$data = $conn->query($sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pilgrim ID | <?php echo $data['full_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Quicksand', sans-serif; background: #e5e7eb; }
        .id-card { 
            width: 350px; 
            height: 550px; 
            background-image: url('https://www.transparenttextures.com/patterns/arabesque.png'), linear-gradient(135deg, #1B7D75 0%, #115e59 100%);
            background-blend-mode: overlay;
        }
        @media print {
            body { background: white; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
            .no-print { display: none; }
            .id-card { border: 1px solid #ddd; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4">

    <div class="no-print mb-6 flex gap-4">
        <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded shadow hover:bg-gray-700">Back</a>
        <button onclick="window.print()" class="px-4 py-2 bg-yellow-600 text-white rounded shadow hover:bg-yellow-700">Print Card</button>
    </div>

    <!-- ID CARD FRONT -->
    <div class="id-card rounded-2xl shadow-2xl relative overflow-hidden flex flex-col text-white">
        
        <!-- Header -->
        <div class="text-center pt-6 pb-2 relative z-10">
            <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-2 border-2 border-white text-deepGreen font-bold text-xl">A</div>
            <h1 class="font-bold text-lg leading-none uppercase tracking-wide">Abdullateef</h1>
            <p class="text-[10px] text-yellow-300 uppercase tracking-widest">Hajj & Umrah Pilgrim</p>
        </div>

        <!-- Photo Area -->
        <div class="flex-grow flex flex-col items-center justify-center relative z-10">
            <div class="w-32 h-32 bg-white p-1 rounded-full shadow-lg mb-4">
                <div class="w-full h-full rounded-full overflow-hidden bg-gray-200">
                    <?php if($data['passport_photo']): ?>
                        <img src="<?php echo $data['passport_photo']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400 font-bold">NO IMG</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2 class="text-xl font-bold text-center px-4 leading-tight mb-1"><?php echo $data['full_name']; ?></h2>
            <p class="text-xs bg-yellow-500 text-deepGreen px-3 py-0.5 rounded-full font-bold shadow">
                ID: <?php echo str_pad($data['id'], 6, '0', STR_PAD_LEFT); ?>
            </p>
        </div>

        <!-- Details Footer -->
        <div class="bg-white text-gray-800 p-5 rounded-t-3xl relative z-10 h-40">
            <div class="grid grid-cols-2 gap-4 text-xs mb-3">
                <div>
                    <span class="block text-gray-400 uppercase text-[9px]">Package</span>
                    <span class="font-bold text-deepGreen truncate block"><?php echo $data['package_name'] ?? 'Not Selected'; ?></span>
                </div>
                <div class="text-right">
                    <span class="block text-gray-400 uppercase text-[9px]">Blood Group</span>
                    <span class="font-bold text-deepGreen"><?php echo $data['blood_group'] ?? 'N/A'; ?></span>
                </div>
            </div>
            
            <div class="border-t pt-2">
                <span class="block text-gray-400 uppercase text-[9px] mb-1">In Case of Emergency</span>
                <p class="font-bold text-sm leading-tight"><?php echo $data['emergency_contact_name']; ?></p>
                <p class="text-deepGreen font-mono font-bold"><?php echo $data['emergency_contact_phone']; ?></p>
            </div>
        </div>

        <!-- Decorative Circles -->
        <div class="absolute top-20 -left-10 w-40 h-40 bg-white opacity-5 rounded-full blur-xl"></div>
        <div class="absolute bottom-40 -right-10 w-40 h-40 bg-yellow-500 opacity-10 rounded-full blur-xl"></div>
    </div>

</body>
</html>