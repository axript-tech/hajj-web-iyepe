<?php
// admin/trip_checkin.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = '';
$msg_type = 'success';

// 1. Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action']; 
    $booking_ids = $_POST['selected_bookings'] ?? [];

    if (!empty($booking_ids) && in_array($action, ['bulk_checkin', 'bulk_noshow'])) {
        $status = ($action === 'bulk_checkin') ? 'checked_in' : 'no_show';
        
        // Sanitize all IDs
        $clean_ids = array_map('intval', $booking_ids);
        $id_list = implode(',', $clean_ids);
        
        $sql = "UPDATE bookings SET checkin_status = '$status' WHERE id IN ($id_list)";
        
        if ($conn->query($sql)) {
            $status_text = ($status === 'checked_in') ? 'Checked In' : 'marked as No Show';
            $msg = count($clean_ids) . " pilgrim(s) successfully $status_text.";
        } else {
            $msg = "Error during bulk update: " . $conn->error;
            $msg_type = "error";
        }
    } else {
        $msg = "No pilgrims selected for bulk action.";
        $msg_type = "error";
    }
}

// 2. Handle Single Check-in Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($_POST['bulk_action'])) {
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action']; // 'checkin', 'noshow', or 'pending'
    
    if (in_array($action, ['checkin', 'noshow', 'pending'])) {
        if ($action === 'checkin') {
            $status = 'checked_in';
        } elseif ($action === 'noshow') {
            $status = 'no_show';
        } else {
            $status = 'pending';
        }
        
        $stmt = $conn->prepare("UPDATE bookings SET checkin_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            $msg = "Pilgrim status updated.";
        } else {
            $msg = "Error updating status.";
            $msg_type = "error";
        }
    }
}

// Active Filter
$batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
$batch_name_display = "Unknown Cohort";

// Fetch active batches for the dropdown
$batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed' ORDER BY start_date ASC");

// Fetch Pilgrims for selected batch
$pilgrims = null;
if ($batch_id > 0) {
    $sql = "SELECT b.id as booking_id, b.checkin_status, m.full_name, m.passport_photo, m.id as member_id, 
                   b.amount_paid, b.total_due, p.name as pkg_name, tb.flight_name, tb.flight_number, tb.batch_name
            FROM bookings b
            JOIN members m ON b.member_id = m.id
            JOIN packages p ON b.package_id = p.id
            JOIN trip_batches tb ON b.trip_batch_id = tb.id
            WHERE b.trip_batch_id = '$batch_id' AND b.booking_status = 'confirmed'
            ORDER BY m.full_name ASC";
    $pilgrims = $conn->query($sql);
}

// Calculate Stats if a batch is selected
$total_pilgrims = 0;
$checked_in_count = 0;
$no_show_count = 0;
$pending_count = 0;

if ($pilgrims && $pilgrims->num_rows > 0) {
    $total_pilgrims = $pilgrims->num_rows;
    // We need to iterate once to get counts, so we'll store data in an array
    $pilgrim_data = [];
    while ($row = $pilgrims->fetch_assoc()) {
        $pilgrim_data[] = $row;
        if ($row['checkin_status'] === 'checked_in') $checked_in_count++;
        elseif ($row['checkin_status'] === 'no_show') $no_show_count++;
        else $pending_count++;
        $batch_name_display = $row['batch_name'];
    }
}
?>

<?php include '../includes/header.php'; ?>

<!-- Print Styles specific to Check-in Manifest -->
<style>
    @media print {
        body { background-color: white !important; font-size: 10pt; }
        .no-print, nav, footer { display: none !important; }
        .print-only { display: block !important; }
        .shadow-sm, .shadow-lg, .shadow { box-shadow: none !important; }
        .rounded-xl, .rounded-lg, .rounded-full { border-radius: 0 !important; }
        table { border-collapse: collapse !important; width: 100% !important; margin-top: 20px; }
        th, td { border: 1px solid #000 !important; padding: 8px !important; text-align: left; }
        th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; font-weight: bold; }
        .bg-green-50, .bg-red-50, .bg-yellow-50 { background-color: transparent !important; }
        .text-white { color: black !important; }
        .bg-green-500 { background-color: transparent !important; color: black !important; border: none !important; }
        
        .print-status-checked { content: 'CHECKED IN'; font-weight: bold; }
        .print-status-noshow { content: 'NO SHOW'; text-decoration: line-through; }
        .print-status-pending { content: 'PENDING'; }
    }
    .print-only { display: none; }
</style>

<div class="max-w-6xl mx-auto space-y-6 relative">

    <!-- Print Header (Only visible when printing) -->
    <div class="print-only text-center border-b-2 border-black pb-4 mb-6">
        <h1 class="text-2xl font-bold uppercase tracking-widest">Flight Boarding Manifest</h1>
        <h2 class="text-xl font-bold mt-1"><?php echo htmlspecialchars($batch_name_display); ?></h2>
        <?php if(!empty($pilgrim_data[0]['flight_name'])): ?>
            <p class="text-lg font-semibold mt-1">Flight: <?php echo htmlspecialchars($pilgrim_data[0]['flight_name'] . ' ' . $pilgrim_data[0]['flight_number']); ?></p>
        <?php endif; ?>
        <p class="text-sm mt-2">Printed on: <?php echo date('d M Y, h:i A'); ?></p>
        <div class="mt-4 flex justify-between text-sm font-bold border border-black p-2">
            <span>Total Manifest: <?php echo $total_pilgrims; ?></span>
            <span>Checked In: <?php echo $checked_in_count; ?></span>
            <span>No Show: <?php echo $no_show_count; ?></span>
        </div>
    </div>

    <!-- Header & Selection (Screen Only) -->
    <div class="no-print bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-clipboard-check text-hajjGold"></i> Departure Check-in</h1>
            <p class="text-gray-500 text-sm mt-1">Verify physical attendance for active trips.</p>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <form method="GET" class="flex-grow">
                <select name="batch_id" onchange="this.form.submit()" class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 font-bold text-gray-700 min-w-[200px]">
                    <option value="0">-- Select Trip Cohort --</option>
                    <?php if ($batches->num_rows > 0): ?>
                        <?php $batches->data_seek(0); while($b = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo ($batch_id == $b['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['batch_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </form>
            
            <?php if ($batch_id > 0 && $total_pilgrims > 0): ?>
                <a href="print_manifest.php?type=checkin&batch_id=<?php echo $batch_id; ?>" target="_blank" class="bg-white border border-deepGreen text-deepGreen hover:bg-gray-50 px-4 py-2.5 rounded-lg shadow-sm font-bold flex items-center gap-2 transition" title="Print Check-in Manifest">
                    <i class="fas fa-print"></i> <span class="hidden md:inline">Print</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="no-print p-4 rounded-xl border-l-4 shadow-sm <?php echo ($msg_type === 'success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
            <span class="font-bold flex items-center gap-2">
                <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $msg; ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ($batch_id == 0): ?>
        <div class="no-print bg-white p-12 rounded-xl shadow-sm border-2 border-dashed border-gray-200 text-center text-gray-400">
            <i class="fas fa-plane-departure fa-3x mb-4 opacity-20"></i>
            <p class="text-lg font-bold text-gray-500">Select a Trip Cohort</p>
            <p class="text-sm mt-1">Choose a batch from the dropdown above to view the manifest and begin check-in.</p>
        </div>
    <?php else: ?>
        
        <!-- Stats Dashboard (Screen Only) -->
        <div class="no-print grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-gray-400 text-center">
                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Total Manifest</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_pilgrims; ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-green-500 text-center">
                <p class="text-xs text-green-600 uppercase font-bold mb-1">Checked In</p>
                <p class="text-2xl font-bold text-green-700" id="stat-checked"><?php echo $checked_in_count; ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-yellow-500 text-center">
                <p class="text-xs text-yellow-600 uppercase font-bold mb-1">Pending</p>
                <p class="text-2xl font-bold text-yellow-700" id="stat-pending"><?php echo $pending_count; ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-red-500 text-center">
                <p class="text-xs text-red-600 uppercase font-bold mb-1">No Show</p>
                <p class="text-2xl font-bold text-red-700" id="stat-noshow"><?php echo $no_show_count; ?></p>
            </div>
        </div>

        <!-- DYNAMIC BULK ACTIONS BAR (Hidden until checkboxes are clicked) -->
        <div id="bulk-actions-bar" class="hidden no-print sticky top-20 z-40 bg-deepGreen text-white p-4 rounded-xl shadow-2xl mb-4 flex justify-between items-center transition-all border border-teal-800 transform translate-y-2 opacity-0">
            <div class="flex items-center gap-3">
                <span class="bg-white text-deepGreen w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-sm" id="selected-count">0</span>
                <span class="text-sm font-bold uppercase tracking-wider">Pilgrim(s) Selected</span>
            </div>
            <div class="flex gap-3">
                <button type="submit" form="bulk-form" name="bulk_action" value="bulk_checkin" class="bg-white text-deepGreen px-5 py-2 rounded-lg font-bold text-sm hover:bg-gray-100 transition shadow flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Check In Selected
                </button>
                <button type="submit" form="bulk-form" name="bulk_action" value="bulk_noshow" class="bg-red-500 text-white border border-red-400 px-5 py-2 rounded-lg font-bold text-sm hover:bg-red-600 transition shadow flex items-center gap-2">
                    <i class="fas fa-times-circle"></i> Mark No Show
                </button>
            </div>
        </div>

        <!-- Pilgrim List inside a Form for Bulk Submissions -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden relative">
            <div class="no-print p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Passenger Manifest</h3>
                <?php if(!empty($pilgrim_data[0]['flight_name'])): ?>
                    <span class="text-xs font-bold bg-blue-100 text-blue-700 px-3 py-1 rounded-full"><i class="fas fa-plane"></i> <?php echo htmlspecialchars($pilgrim_data[0]['flight_name'] . ' ' . $pilgrim_data[0]['flight_number']); ?></span>
                <?php endif; ?>
            </div>
            
            <form method="POST" id="bulk-form" class="overflow-x-auto">
                <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                        <tr>
                            <!-- Select All Checkbox -->
                            <th class="no-print p-4 w-12 text-center">
                                <input type="checkbox" id="selectAll" class="w-4 h-4 text-deepGreen bg-white border-gray-300 rounded focus:ring-deepGreen cursor-pointer">
                            </th>
                            <th class="print-only p-4 w-12">#</th>
                            <th class="p-4">Pilgrim</th>
                            <th class="p-4">Financial Status</th>
                            <th class="p-4 text-center">Boarding Status</th>
                            <th class="no-print p-4 text-right">Check-in Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($total_pilgrims > 0): ?>
                            <?php $row_num = 1; foreach($pilgrim_data as $p): 
                                $balance = $p['total_due'] - $p['amount_paid'];
                            ?>
                                <tr class="hover:bg-gray-50 transition <?php echo ($p['checkin_status'] === 'checked_in') ? 'bg-green-50/30' : (($p['checkin_status'] === 'no_show') ? 'bg-red-50/30 opacity-70' : ''); ?>">
                                    <!-- Row Checkbox -->
                                    <td class="no-print p-4 text-center">
                                        <input type="checkbox" name="selected_bookings[]" value="<?php echo $p['booking_id']; ?>" class="row-checkbox w-4 h-4 text-deepGreen bg-white border-gray-300 rounded focus:ring-deepGreen cursor-pointer">
                                    </td>
                                    
                                    <td class="print-only p-4 text-gray-500 font-mono"><?php echo $row_num++; ?></td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="no-print w-10 h-10 rounded-full bg-gray-200 overflow-hidden flex-shrink-0 border border-gray-300">
                                                <?php if($p['passport_photo']): ?>
                                                    <img src="../<?php echo htmlspecialchars($p['passport_photo']); ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs"><i class="fas fa-user"></i></div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-deepGreen"><?php echo htmlspecialchars($p['full_name']); ?></p>
                                                <p class="text-[10px] text-gray-400 font-mono">ID: #<?php echo str_pad($p['member_id'], 4, '0', STR_PAD_LEFT); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($balance <= 0): ?>
                                            <span class="no-print text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-bold border border-green-200"><i class="fas fa-check"></i> Fully Paid</span>
                                            <span class="print-only">Cleared</span>
                                        <?php else: ?>
                                            <span class="no-print text-xs bg-red-100 text-red-700 px-2 py-1 rounded font-bold border border-red-200">Owing: <?php echo CURRENCY . number_format($balance); ?></span>
                                            <span class="print-only font-bold">Owing Balance</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center font-bold">
                                        <!-- Screen rendering -->
                                        <div class="no-print">
                                            <?php if ($p['checkin_status'] === 'pending'): ?>
                                                <span class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-bold uppercase border border-yellow-200 animate-pulse">Pending</span>
                                            <?php elseif ($p['checkin_status'] === 'checked_in'): ?>
                                                <span class="text-xs bg-green-500 text-white px-3 py-1 rounded-full font-bold uppercase shadow-sm"><i class="fas fa-check-circle"></i> Checked In</span>
                                            <?php else: ?>
                                                <span class="text-xs bg-red-500 text-white px-3 py-1 rounded-full font-bold uppercase shadow-sm"><i class="fas fa-times-circle"></i> No Show</span>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Print rendering -->
                                        <div class="print-only">
                                            <?php 
                                                if ($p['checkin_status'] === 'checked_in') echo 'CHECKED IN';
                                                elseif ($p['checkin_status'] === 'no_show') echo 'NO SHOW';
                                                else echo 'PENDING';
                                            ?>
                                        </div>
                                    </td>
                                    <td class="no-print p-4 text-right">
                                        <!-- We keep individual forms isolated from the bulk form using the formaction trick or separate logic. 
                                             Actually, to nest them cleanly, we use buttons with form overrides. -->
                                        <div class="inline-flex gap-2 justify-end">
                                            <?php if ($p['checkin_status'] !== 'checked_in'): ?>
                                                <button type="submit" name="action" value="checkin" formaction="trip_checkin.php?batch_id=<?php echo $batch_id; ?>" onclick="document.getElementById('hidden-booking-id').value = <?php echo $p['booking_id']; ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs font-bold shadow transition">
                                                    Check In
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($p['checkin_status'] !== 'no_show'): ?>
                                                <button type="submit" name="action" value="noshow" formaction="trip_checkin.php?batch_id=<?php echo $batch_id; ?>" onclick="document.getElementById('hidden-booking-id').value = <?php echo $p['booking_id']; ?>" class="bg-white border border-red-300 text-red-500 hover:bg-red-50 hover:border-red-500 px-3 py-1.5 rounded text-xs font-bold shadow-sm transition">
                                                    No Show
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($p['checkin_status'] !== 'pending'): ?>
                                                <button type="submit" name="action" value="pending" formaction="trip_checkin.php?batch_id=<?php echo $batch_id; ?>" onclick="document.getElementById('hidden-booking-id').value = <?php echo $p['booking_id']; ?>" class="text-gray-400 hover:text-gray-600 text-xs px-2 underline">
                                                    Undo
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="p-8 text-center text-gray-500">No confirmed bookings found for this trip.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Hidden input to catch the specific booking ID when an individual button is clicked -->
                <input type="hidden" id="hidden-booking-id" name="booking_id" value="">
            </form>
        </div>

    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('selectAll');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const bulkBar = document.getElementById('bulk-actions-bar');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkBar() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (count > 0) {
                bulkBar.classList.remove('hidden');
                // Small delay to allow display:block to apply before animating opacity
                setTimeout(() => {
                    bulkBar.classList.remove('opacity-0', 'translate-y-2');
                    bulkBar.classList.add('opacity-100', 'translate-y-0');
                }, 10);
                selectedCount.innerText = count;
            } else {
                bulkBar.classList.remove('opacity-100', 'translate-y-0');
                bulkBar.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => {
                    bulkBar.classList.add('hidden');
                }, 300); // Wait for transition to finish
            }
            
            if (selectAll) {
                selectAll.checked = (count === rowCheckboxes.length && count > 0);
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                rowCheckboxes.forEach(cb => cb.checked = e.target.checked);
                updateBulkBar();
            });
        }

        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>