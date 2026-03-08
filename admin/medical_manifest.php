<?php
// admin/medical_manifest.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../index.php"); 
}

// Current Filter
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Fetch Batches for Filter Dropdown
$batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed'");

// Fetch Data with Passport Photo based on Filter
$sql = "SELECT m.id, m.full_name, m.phone, m.has_paid_commitment, m.passport_photo,
               med.blood_group, med.genotype, med.chronic_conditions, med.mobility_needs, 
               med.emergency_contact_name, med.emergency_contact_phone, med.last_updated
        FROM members m
        LEFT JOIN medical_profiles med ON m.id = med.member_id ";

if ($batch_id > 0) {
    // Filter by specific batch
    $sql .= "JOIN bookings b ON m.id = b.member_id WHERE b.trip_batch_id = '$batch_id' AND b.booking_status = 'confirmed' AND m.role = 'user' ";
} else {
    // Show all pilgrims
    $sql .= "WHERE m.role = 'user' ";
}

$sql .= "ORDER BY med.mobility_needs DESC, m.has_paid_commitment DESC";

$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Medical Manifest</h1>
        <p class="text-gray-600 mt-1">Live operational view of pilgrim health & safety status.</p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3">
        <!-- Dynamic Trip Filter -->
        <form method="GET" class="flex items-center">
            <select name="batch_id" onchange="this.form.submit()" class="p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-deepGreen outline-none bg-white font-bold text-gray-700 shadow-sm cursor-pointer">
                <option value="0">Global (All Pilgrims)</option>
                <?php 
                if ($batches) {
                    $batches->data_seek(0); 
                    while($b = $batches->fetch_assoc()): 
                ?>
                    <option value="<?php echo $b['id']; ?>" <?php echo ($batch_id == $b['id']) ? 'selected' : ''; ?>>
                        Cohort: <?php echo htmlspecialchars($b['batch_name']); ?>
                    </option>
                <?php 
                    endwhile; 
                } 
                ?>
            </select>
        </form>

        <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm font-bold shadow-sm">Back</a>
        
        <!-- Dynamic Print Link -->
        <a href="print_manifest.php?type=medical<?php echo $batch_id > 0 ? '&batch_id='.$batch_id : ''; ?>" target="_blank" class="bg-white border border-deepGreen text-deepGreen px-4 py-2 rounded-lg shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm font-bold transition">
            <i class="fas fa-print"></i> Print Manifest
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-deepGreen">
        <p class="text-gray-500 text-sm">Total Pilgrims <?php echo $batch_id > 0 ? 'in Cohort' : ''; ?></p>
        <h3 class="text-2xl font-bold text-black"><?php echo $result->num_rows; ?></h3>
    </div>
    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
        <p class="text-gray-500 text-sm">Critical Health Conditions</p>
        <h3 class="text-2xl font-bold text-red-600">
            <?php 
            $risk_count = 0;
            $data = []; 
            while($row = $result->fetch_assoc()) {
                if ((!empty($row['chronic_conditions']) && $row['chronic_conditions'] !== 'None') || $row['genotype'] == 'SS') {
                    $risk_count++;
                }
                $data[] = $row;
            }
            echo $risk_count;
            ?>
        </h3>
    </div>
    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-hajjGold">
        <p class="text-gray-500 text-sm">Mobility Assistance Needed</p>
        <h3 class="text-2xl font-bold text-hajjGold">
            <?php 
            $mobility_count = 0;
            foreach($data as $row) {
                if ($row['mobility_needs'] !== 'None' && !empty($row['mobility_needs'])) $mobility_count++;
            }
            echo $mobility_count;
            ?>
        </h3>
    </div>
</div>

<!-- Manifest Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead class="bg-deepGreen text-white uppercase text-xs tracking-wider">
            <tr>
                <th class="p-4 w-16 text-center">Photo</th>
                <th class="p-4">Pilgrim Name</th>
                <th class="p-4">Vitals (Blood/Geno)</th>
                <th class="p-4">Chronic Conditions</th>
                <th class="p-4">Mobility Needs</th>
                <th class="p-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            <?php if (count($data) > 0): ?>
                <?php foreach($data as $row): ?>
                    <!-- Main Row -->
                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="toggleRow(<?php echo $row['id']; ?>)">
                        <td class="p-3 text-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden mx-auto border border-gray-300">
                                <?php if($row['passport_photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($row['passport_photo'] ?? ''); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-4">
                            <p class="font-bold text-deepGreen text-base"><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></p>
                            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($row['phone'] ?? ''); ?></p>
                        </td>
                        <td class="p-4">
                            <?php if($row['blood_group']): ?>
                                <span class="inline-block px-2 py-1 bg-gray-100 rounded text-gray-700 font-bold text-xs mr-1">
                                    <?php echo $row['blood_group']; ?>
                                </span>
                                <span class="inline-block px-2 py-1 rounded font-bold text-xs <?php echo ($row['genotype'] == 'SS') ? 'bg-red-100 text-red-700' : 'bg-blue-50 text-blue-700'; ?>">
                                    <?php echo $row['genotype']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <?php if(empty($row['chronic_conditions']) || $row['chronic_conditions'] == 'None'): ?>
                                <span class="text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> Clear</span>
                            <?php else: ?>
                                <span class="bg-red-50 text-red-600 px-2 py-1 rounded border border-red-100 font-semibold">
                                    <?php echo htmlspecialchars($row['chronic_conditions'] ?? ''); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <?php if($row['mobility_needs'] == 'Wheelchair' || $row['mobility_needs'] == 'Stretcher'): ?>
                                <span class="text-hajjGold font-bold flex items-center gap-1">
                                    <i class="fas fa-wheelchair"></i> <?php echo $row['mobility_needs']; ?>
                                </span>
                            <?php elseif($row['mobility_needs'] == 'Walking Stick'): ?>
                                <span class="text-orange-500 font-bold flex items-center gap-1">
                                    <i class="fas fa-hiking"></i> <?php echo $row['mobility_needs']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <i class="fas fa-chevron-down text-gray-400" id="icon-<?php echo $row['id']; ?>"></i>
                        </td>
                    </tr>

                    <!-- Expandable Detail Row -->
                    <tr id="details-<?php echo $row['id']; ?>" class="hidden bg-gray-50 border-b border-gray-200 shadow-inner">
                        <td colspan="6" class="p-0">
                            <div class="p-6 grid md:grid-cols-4 gap-6 bg-gray-50">
                                
                                <!-- 1. Passport Photo -->
                                <div class="col-span-1">
                                    <div class="bg-white p-2 rounded shadow border border-gray-200 w-full max-w-[150px]">
                                        <div class="aspect-[3/4] bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                                            <?php if($row['passport_photo']): ?>
                                                <img src="../<?php echo htmlspecialchars($row['passport_photo'] ?? ''); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="text-center text-gray-400">
                                                    <i class="fas fa-user-circle fa-4x mb-2"></i>
                                                    <p class="text-xs">No Photo</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Emergency Contact -->
                                <div class="col-span-1 space-y-3">
                                    <h4 class="font-bold text-deepGreen border-b border-gray-200 pb-1 uppercase text-xs">Emergency Contact</h4>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Name</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($row['emergency_contact_name'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Phone</p>
                                        <p class="font-bold"><?php echo htmlspecialchars($row['emergency_contact_phone'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>

                                <!-- 3. Detailed Medical Info -->
                                <div class="col-span-2 space-y-3">
                                    <h4 class="font-bold text-deepGreen border-b border-gray-200 pb-1 uppercase text-xs">Detailed Medical Notes</h4>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Full Conditions List</p>
                                        <p class="font-bold text-gray-800 bg-white p-2 border rounded">
                                            <?php echo !empty($row['chronic_conditions']) ? htmlspecialchars($row['chronic_conditions'] ?? '') : 'No chronic conditions reported.'; ?>
                                        </p>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-xs text-gray-400">Last Updated: <?php echo date('d M Y', strtotime($row['last_updated'])); ?></span>
                                        <a href="member_profile.php?id=<?php echo $row['id']; ?>" class="text-xs font-bold text-deepGreen hover:underline">Edit Medical Profile</a>
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="p-12 text-center text-gray-500"><i class="fas fa-notes-medical fa-2x mb-3 opacity-30"></i><br>No medical records found for this selection.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleRow(id) {
    const row = document.getElementById('details-' + id);
    const icon = document.getElementById('icon-' + id);
    if(row) {
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
            if(icon) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
        } else {
            row.classList.add('hidden');
            if(icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
        }
    }
}
</script>

<style>
@media print {
    .no-print, button, a, form { display: none !important; }
    tr.hidden { display: table-row !important; }
    body { background: white; font-size: 10pt; }
    table, th, td { border: 1px solid #000 !important; }
    .shadow, .shadow-lg { box-shadow: none !important; }
}
</style>

<?php include '../includes/footer.php'; ?>