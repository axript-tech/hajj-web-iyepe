<?php
// admin/dashboard.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
   // header("Location: ../index.php"); 
}

// 1. Fetch Key Metrics
$stats_query = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM members WHERE role='user') as total_users,
        (SELECT COUNT(*) FROM members WHERE has_paid_commitment=1) as total_active,
        (SELECT COUNT(*) FROM medical_profiles WHERE (chronic_conditions != 'None' AND chronic_conditions != '') OR genotype = 'SS') as high_risk,
        (SELECT SUM(amount) FROM payments WHERE status='success') as total_revenue
");
$stats = $stats_query->fetch_assoc();

// 2. Fetch Upcoming Trip Batches (Cohorts)
$cohorts_sql = "
    SELECT 
        p.name as package_name, 
        b.travel_date, 
        COUNT(b.id) as pilgrim_count 
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    WHERE b.booking_status = 'confirmed' AND b.travel_date >= CURRENT_DATE
    GROUP BY p.name, b.travel_date 
    ORDER BY b.travel_date ASC 
    LIMIT 5
";
$cohorts = $conn->query($cohorts_sql);
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-deepGreen">Admin Command Center</h1>
    <p class="text-gray-600">Operational Overview & Live Metrics</p>
</div>

<!-- Key Metrics Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <!-- Total Registered -->
    <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-gray-300">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase">Registered Users</p>
            <i class="fas fa-users text-gray-300"></i>
        </div>
        <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_users']); ?></h3>
    </div>

    <!-- Active Pilgrims -->
    <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-deepGreen">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-bold text-deepGreen uppercase">Active Pilgrims</p>
            <i class="fas fa-user-check text-deepGreen/50"></i>
        </div>
        <h3 class="text-3xl font-bold text-deepGreen"><?php echo number_format($stats['total_active']); ?></h3>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-hajjGold">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-bold text-hajjGold uppercase">Total Revenue</p>
            <i class="fas fa-coins text-hajjGold/50"></i>
        </div>
        <!-- FIX: Added '?? 0' to handle null values from SUM() -->
        <h3 class="text-2xl font-bold text-gray-800"><?php echo CURRENCY . number_format($stats['total_revenue'] ?? 0); ?></h3>
    </div>

    <!-- Medical Alerts -->
    <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-red-500">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-bold text-red-500 uppercase">Medical Alerts</p>
            <i class="fas fa-heartbeat text-red-300"></i>
        </div>
        <h3 class="text-3xl font-bold text-red-600"><?php echo number_format($stats['high_risk']); ?></h3>
        <p class="text-xs text-gray-400 mt-1">Requiring special attention</p>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-8">
    
    <!-- Upcoming Batches -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-deepGreen"><i class="fas fa-plane-departure mr-2"></i> Upcoming Trip Batches</h3>
            <a href="trip_manifest.php" class="text-xs font-bold text-gray-500 hover:text-deepGreen">View All</a>
        </div>
        <div class="p-0">
            <?php if($cohorts->num_rows > 0): ?>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-5 py-3">Travel Date</th>
                            <th class="px-5 py-3">Package</th>
                            <th class="px-5 py-3 text-right">Pilgrims</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while($c = $cohorts->fetch_assoc()): 
                            $dateObj = new DateTime($c['travel_date']);
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3 font-bold text-gray-700">
                                    <?php echo $dateObj->format('M Y'); ?>
                                </td>
                                <td class="px-5 py-3 text-gray-600">
                                    <?php echo $c['package_name']; ?>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span class="bg-deepGreen text-white px-2 py-1 rounded text-xs font-bold">
                                        <?php echo $c['pilgrim_count']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                    <p>No upcoming trips scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <h3 class="font-bold text-gray-700 mb-6">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-4">
            <a href="create_member.php" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-xl hover:bg-deepGreen hover:text-white transition group border border-transparent hover:border-deepGreen cursor-pointer">
                <i class="fas fa-user-plus text-2xl text-deepGreen mb-2 group-hover:text-white"></i>
                <span class="text-sm font-bold">Add Pilgrim</span>
            </a>
            <!-- UPDATED PRINT LINK -->
            <a href="print_manifest.php?type=members" target="_blank" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-xl hover:bg-hajjGold hover:text-white transition group border border-transparent hover:border-hajjGold cursor-pointer">
                <i class="fas fa-print text-2xl text-hajjGold mb-2 group-hover:text-white"></i>
                <span class="text-sm font-bold">Print Master List</span>
            </a>
            <a href="manage_packages.php" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-xl hover:bg-gray-200 transition group cursor-pointer">
                <i class="fas fa-tags text-2xl text-gray-500 mb-2"></i>
                <span class="text-sm font-bold text-gray-600">Edit Prices</span>
            </a>
            <a href="medical_manifest.php" class="flex flex-col items-center justify-center p-4 bg-gray-50 rounded-xl hover:bg-red-50 hover:border-red-200 transition group border border-transparent cursor-pointer">
                <i class="fas fa-notes-medical text-2xl text-red-400 mb-2"></i>
                <span class="text-sm font-bold text-gray-600">Medical Check</span>
            </a>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>