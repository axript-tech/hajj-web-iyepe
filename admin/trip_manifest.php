<?php
// admin/trip_manifest.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

// --- HANDLE ROOM ASSIGNMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_room'])) {
    require_once '../includes/mailer.php';
    
    $bk_id = intval($_POST['booking_id']);
    $mecca_rm = $conn->real_escape_string($_POST['mecca_room']);
    $medina_rm = $conn->real_escape_string($_POST['medina_room']);
    
    // Check old values to see if an email needs to be sent
    $old_data = $conn->query("SELECT mecca_room_no, medina_room_no, m.email, m.full_name FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.id = '$bk_id'")->fetch_assoc();
    
    $conn->query("UPDATE bookings SET mecca_room_no = '$mecca_rm', medina_room_no = '$medina_rm' WHERE id = '$bk_id'");
    
    // Send Notification if changed
    if (($mecca_rm !== $old_data['mecca_room_no'] && !empty($mecca_rm)) || ($medina_rm !== $old_data['medina_room_no'] && !empty($medina_rm))) {
        $msg_body = "Your accommodation allocations have been updated by the administration.<br><br>";
        if (!empty($mecca_rm)) $msg_body .= "Makkah Room: <strong>$mecca_rm</strong><br>";
        if (!empty($medina_rm)) $msg_body .= "Madinah Room: <strong>$medina_rm</strong><br>";
        $msg_body .= "<br>Please log in to your dashboard to view your roommates.";
        
        send_hajj_mail($old_data['email'], $old_data['full_name'], "Hotel Room Assignment Updated", $msg_body);
    }

    // Redirect to self to prevent resubmission
    $qs = $_SERVER['QUERY_STRING'];
    header("Location: trip_manifest.php?$qs");
    exit();
}

// 1. Fetch Packages
$packages_result = $conn->query("SELECT id, name FROM packages");

// 2. Filters
$selected_pkg_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
$selected_month = isset($_GET['trip_month']) ? intval($_GET['trip_month']) : 0;
$selected_year = isset($_GET['trip_year']) ? intval($_GET['trip_year']) : 0;

$package_name = "Select a Trip";
$batch_label = "All Time";

// 3. Fetch Data & Group Rooms
$pilgrims = [];
$mecca_map = [];
$medina_map = [];

if ($selected_pkg_id > 0) {
    // Get Package Info
    $pkg_query = $conn->query("SELECT name, total_cost, mecca_hotel, medina_hotel FROM packages WHERE id = $selected_pkg_id");
    $pkg_data = $pkg_query->fetch_assoc();
    $package_name = $pkg_data['name'];

    // Construct Label
    if ($selected_month > 0 && $selected_year > 0) {
        $dateObj = DateTime::createFromFormat('!m', $selected_month);
        $batch_label = $dateObj->format('F') . " " . $selected_year . " Cohort";
    }

    // Query
    $sql = "SELECT m.id as member_id, m.full_name, m.phone, m.passport_photo, m.passport_number,
                   b.id as booking_id, b.amount_paid, b.total_due, b.travel_date, 
                   b.mecca_room_no, b.medina_room_no,
                   mp.blood_group, mp.mobility_needs, mp.emergency_contact_name, mp.emergency_contact_phone
            FROM bookings b
            JOIN members m ON b.member_id = m.id
            LEFT JOIN medical_profiles mp ON m.id = mp.member_id
            WHERE b.package_id = '$selected_pkg_id' AND b.booking_status = 'confirmed'";
    
    if ($selected_month > 0) $sql .= " AND MONTH(b.travel_date) = '$selected_month'";
    if ($selected_year > 0) $sql .= " AND YEAR(b.travel_date) = '$selected_year'";

    $sql .= " ORDER BY m.full_name ASC";
    $result = $conn->query($sql);

    // Build Maps for Roommates (Bed Logic)
    while($row = $result->fetch_assoc()) {
        $pilgrims[] = $row;
        // Group by Mecca Room
        if(!empty($row['mecca_room_no'])) {
            $mecca_map[$row['mecca_room_no']][] = [
                'name' => $row['full_name'], 
                'id' => $row['member_id'],
                'photo' => $row['passport_photo']
            ];
        }
        // Group by Madinah Room
        if(!empty($row['medina_room_no'])) {
            $medina_map[$row['medina_room_no']][] = [
                'name' => $row['full_name'], 
                'id' => $row['member_id'],
                'photo' => $row['passport_photo']
            ];
        }
    }
}

$months = [1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'];
?>

<?php include '../includes/header.php'; ?>

<!-- No-Print Navigation -->
<div class="no-print mb-8">
    <div class="flex justify-between items-end border-b pb-4 mb-4">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Trip Manifest & Rooming</h1>
            <p class="text-gray-600 mt-1">Assign hotel rooms and view occupants.</p>
        </div>
        <div class="flex gap-2">
            <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">Back</a>
        </div>
    </div>

    <form method="GET" class="bg-gray-100 p-4 rounded-lg flex flex-col md:flex-row gap-4 items-end shadow-inner">
        <div class="flex-grow w-full md:w-auto">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Select Package</label>
            <select name="package_id" class="w-full p-2 border rounded font-bold text-gray-700">
                <option value="0">-- Choose a Package --</option>
                <?php 
                if ($packages_result->num_rows > 0) {
                    $packages_result->data_seek(0);
                    while($p = $packages_result->fetch_assoc()) {
                        $selected = ($p['id'] == $selected_pkg_id) ? 'selected' : '';
                        echo "<option value='{$p['id']}' $selected>{$p['name']}</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Month</label>
            <select name="trip_month" class="w-full p-2 border rounded font-bold text-gray-700">
                <option value="0">All</option>
                <?php foreach($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php echo ($selected_month == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-32">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Year</label>
            <select name="trip_year" class="w-full p-2 border rounded font-bold text-gray-700">
                <option value="0">All</option>
                <?php for($y = date('Y')-1; $y <= date('Y')+3; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="bg-deepGreen text-white px-6 py-2 rounded font-bold hover:bg-teal-800 transition">Load</button>
        <?php if($selected_pkg_id > 0): ?>
            <button type="button" onclick="window.print()" class="bg-hajjGold text-white px-6 py-2 rounded font-bold hover:bg-yellow-600 transition"><i class="fas fa-print"></i></button>
        <?php endif; ?>
    </form>
</div>

<!-- Print Header -->
<div class="hidden print-block text-center mb-8 border-b-2 border-black pb-4">
    <h1 class="text-2xl font-bold uppercase tracking-widest">Passenger & Rooming List</h1>
    <h2 class="text-xl font-bold mt-2"><?php echo htmlspecialchars($package_name); ?></h2>
    <p class="text-lg font-semibold mt-1"><?php echo htmlspecialchars($batch_label); ?></p>
</div>

<?php if ($selected_pkg_id > 0 && !empty($pilgrims)): ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 print-shadow-none print-border-black">
        <table class="w-full text-left border-collapse">
            <thead class="bg-deepGreen text-white uppercase text-xs tracking-wider print-bg-black print-text-black">
                <tr>
                    <th class="p-3 w-10">#</th>
                    <th class="p-3 w-16 text-center">Img</th>
                    <th class="p-3">Passenger</th>
                    <th class="p-3">Passport</th>
                    <th class="p-3">Room Assignment</th>
                    <th class="p-3 text-center no-print">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm print-divide-black">
                <?php 
                $counter = 1;
                foreach($pilgrims as $row): 
                    $balance = $row['total_due'] - $row['amount_paid'];
                ?>
                    <tr class="hover:bg-gray-50 print-no-bg cursor-pointer transition" onclick="toggleRow(<?php echo $row['member_id']; ?>)">
                        <td class="p-3 text-gray-500 text-center font-mono"><?php echo $counter++; ?></td>
                        <td class="p-3 text-center">
                            <div class="w-10 h-10 bg-gray-200 mx-auto overflow-hidden rounded-full border border-gray-300 print-border-black">
                                <?php if($row['passport_photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($row['passport_photo']); ?>" class="w-full h-full object-cover grayscale-print">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-[10px]"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-3">
                            <p class="font-bold text-deepGreen print-text-black uppercase"><?php echo htmlspecialchars($row['full_name']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars($row['phone']); ?></p>
                        </td>
                        <td class="p-3 font-mono"><?php echo $row['passport_number'] ?? '-'; ?></td>
                        <td class="p-3">
                            <div class="text-xs">
                                <span class="block">Mak: <strong><?php echo $row['mecca_room_no'] ?? '-'; ?></strong></span>
                                <span class="block">Mad: <strong><?php echo $row['medina_room_no'] ?? '-'; ?></strong></span>
                            </div>
                        </td>
                        <td class="p-3 text-center no-print">
                            <i class="fas fa-chevron-down text-gray-400" id="icon-<?php echo $row['member_id']; ?>"></i>
                        </td>
                    </tr>

                    <!-- Expandable Detail Row (Room & Bed UI) -->
                    <tr id="details-<?php echo $row['member_id']; ?>" class="hidden bg-gray-50 border-b-2 border-gray-200 shadow-inner no-print">
                        <td colspan="6" class="p-0">
                            <div class="p-6 grid md:grid-cols-3 gap-6 bg-gray-100">
                                
                                <!-- Room Assignment Form -->
                                <div class="md:col-span-2 bg-white p-4 rounded shadow border border-deepGreen/20">
                                    <h4 class="font-bold text-deepGreen border-b pb-2 mb-3 text-xs uppercase"><i class="fas fa-bed mr-1"></i> Room Allocation</h4>
                                    <form method="POST" class="grid grid-cols-2 gap-4">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                        
                                        <!-- Mecca -->
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Makkah Room No.</label>
                                            <input type="text" name="mecca_room" value="<?php echo $row['mecca_room_no']; ?>" placeholder="e.g. 305" class="w-full p-2 border rounded text-sm focus:border-deepGreen mb-2">
                                            
                                            <!-- Mecca Roommates (Bed UI) -->
                                            <?php if(!empty($row['mecca_room_no']) && isset($mecca_map[$row['mecca_room_no']])): ?>
                                                <div class="bg-gray-50 p-2 rounded border border-gray-200">
                                                    <p class="text-[9px] text-gray-400 uppercase font-bold mb-1">Roommates (Makkah)</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach($mecca_map[$row['mecca_room_no']] as $roommate): 
                                                            $is_me = ($roommate['id'] == $row['member_id']);
                                                        ?>
                                                            <div class="flex items-center gap-1 px-2 py-1 rounded text-xs border <?php echo $is_me ? 'bg-deepGreen text-white border-deepGreen' : 'bg-white text-gray-600 border-gray-200'; ?>">
                                                                <i class="fas fa-user-circle"></i>
                                                                <span class="truncate max-w-[80px]"><?php echo explode(' ', $roommate['name'])[0]; ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Madinah -->
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Madinah Room No.</label>
                                            <input type="text" name="medina_room" value="<?php echo $row['medina_room_no']; ?>" placeholder="e.g. 512" class="w-full p-2 border rounded text-sm focus:border-deepGreen mb-2">
                                            
                                            <!-- Madinah Roommates (Bed UI) -->
                                            <?php if(!empty($row['medina_room_no']) && isset($medina_map[$row['medina_room_no']])): ?>
                                                <div class="bg-gray-50 p-2 rounded border border-gray-200">
                                                    <p class="text-[9px] text-gray-400 uppercase font-bold mb-1">Roommates (Madinah)</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach($medina_map[$row['medina_room_no']] as $roommate): 
                                                            $is_me = ($roommate['id'] == $row['member_id']);
                                                        ?>
                                                            <div class="flex items-center gap-1 px-2 py-1 rounded text-xs border <?php echo $is_me ? 'bg-deepGreen text-white border-deepGreen' : 'bg-white text-gray-600 border-gray-200'; ?>">
                                                                <i class="fas fa-user-circle"></i>
                                                                <span class="truncate max-w-[80px]"><?php echo explode(' ', $roommate['name'])[0]; ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-span-2 text-right">
                                            <button type="submit" name="assign_room" class="bg-deepGreen text-white text-xs font-bold px-4 py-2 rounded hover:bg-teal-800 transition">
                                                Update Assignments
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Quick Info -->
                                <div class="bg-white p-4 rounded shadow border border-gray-200">
                                    <h4 class="font-bold text-gray-600 border-b pb-2 mb-3 text-xs uppercase">Pilgrim Status</h4>
                                    <div class="space-y-3 text-sm">
                                        <div>
                                            <span class="block text-xs text-gray-400">Balance Due</span>
                                            <span class="font-bold font-mono <?php echo ($balance > 0)?'text-red-600':'text-green-600'; ?>"><?php echo CURRENCY.number_format($balance); ?></span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-gray-400">Emergency Contact</span>
                                            <span class="font-bold"><?php echo $row['emergency_contact_phone'] ?? 'N/A'; ?></span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-gray-400">Mobility</span>
                                            <span class="font-bold text-hajjGold"><?php echo $row['mobility_needs']; ?></span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif($selected_pkg_id == 0): ?>
    <div class="text-center p-12 bg-white rounded shadow text-gray-500">
        <i class="fas fa-hotel fa-3x mb-4 text-gray-300"></i>
        <p>Select a package to manage room assignments.</p>
    </div>
<?php else: ?>
    <div class="text-center p-12 bg-white rounded shadow text-gray-500">
        <p>No confirmed passengers found for this trip.</p>
    </div>
<?php endif; ?>

<script>
function toggleRow(id) {
    const row = document.getElementById('details-' + id);
    const icon = document.getElementById('icon-' + id);
    if(row) {
        if(row.classList.contains('hidden')) { 
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
    .no-print { display: none !important; }
    .print-block { display: block !important; }
    .print-shadow-none { box-shadow: none !important; }
    .print-border-black { border-color: #000 !important; }
    .print-bg-black { background-color: #ddd !important; color: #000 !important; }
    .grayscale-print { filter: grayscale(100%); }
    .print-text-black { color: #000 !important; }
    body { background: white; font-size: 10pt; }
    table, th, td { border: 1px solid #000 !important; }
}
</style>

<?php include '../includes/footer.php'; ?>