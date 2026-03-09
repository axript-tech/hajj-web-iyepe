<?php
// admin/manage_trips.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php"); 
    exit();
}

// --- HANDLE ACTIONS ---

// 1. Create New Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_batch'])) {
    $pkg_id = intval($_POST['package_id']);
    $name = $conn->real_escape_string($_POST['batch_name']);
    $start = $_POST['start_date'];
    $end = $_POST['return_date'];
    
    $flight_name = $conn->real_escape_string($_POST['flight_name'] ?? '');
    $flight_number = $conn->real_escape_string($_POST['flight_number'] ?? '');
    $dep_airport = $conn->real_escape_string($_POST['departure_airport'] ?? '');
    $dep_terminal = $conn->real_escape_string($_POST['departure_terminal'] ?? '');
    
    $ret_flight_name = $conn->real_escape_string($_POST['return_flight_name'] ?? '');
    $ret_flight_number = $conn->real_escape_string($_POST['return_flight_number'] ?? '');
    $ret_airport = $conn->real_escape_string($_POST['return_airport'] ?? '');
    $ret_terminal = $conn->real_escape_string($_POST['return_terminal'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO trip_batches (package_id, batch_name, start_date, return_date, flight_name, flight_number, departure_airport, departure_terminal, return_flight_name, return_flight_number, return_airport, return_terminal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssssss", $pkg_id, $name, $start, $end, $flight_name, $flight_number, $dep_airport, $dep_terminal, $ret_flight_name, $ret_flight_number, $ret_airport, $ret_terminal);
    
    if ($stmt->execute()) {
        $msg = "New Trip Batch created successfully.";
        $msg_type = "success";
    } else {
        $msg = "Error creating batch: " . $conn->error;
        $msg_type = "error";
    }
}

// 2. Update Existing Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_batch'])) {
    $batch_id = intval($_POST['batch_id']);
    $pkg_id = intval($_POST['package_id']);
    $name = $conn->real_escape_string($_POST['batch_name']);
    $start = $_POST['start_date'];
    $end = $_POST['return_date'];
    
    $flight_name = $conn->real_escape_string($_POST['flight_name'] ?? '');
    $flight_number = $conn->real_escape_string($_POST['flight_number'] ?? '');
    $dep_airport = $conn->real_escape_string($_POST['departure_airport'] ?? '');
    $dep_terminal = $conn->real_escape_string($_POST['departure_terminal'] ?? '');
    
    $ret_flight_name = $conn->real_escape_string($_POST['return_flight_name'] ?? '');
    $ret_flight_number = $conn->real_escape_string($_POST['return_flight_number'] ?? '');
    $ret_airport = $conn->real_escape_string($_POST['return_airport'] ?? '');
    $ret_terminal = $conn->real_escape_string($_POST['return_terminal'] ?? '');
    
    $stmt = $conn->prepare("UPDATE trip_batches SET package_id=?, batch_name=?, start_date=?, return_date=?, flight_name=?, flight_number=?, departure_airport=?, departure_terminal=?, return_flight_name=?, return_flight_number=?, return_airport=?, return_terminal=? WHERE id=?");
    $stmt->bind_param("isssssssssssi", $pkg_id, $name, $start, $end, $flight_name, $flight_number, $dep_airport, $dep_terminal, $ret_flight_name, $ret_flight_number, $ret_airport, $ret_terminal, $batch_id);
    
    if ($stmt->execute()) {
        header("Location: manage_trips.php?msg=updated");
        exit();
    } else {
        $msg = "Error updating batch: " . $conn->error;
        $msg_type = "error";
    }
}

// 3. Update Trip Status (Logic Updated for Reversibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $batch_id = intval($_POST['batch_id']);
    $status = $_POST['update_status']; 
    
    // Update Batch Status
    $conn->query("UPDATE trip_batches SET status = '$status' WHERE id = '$batch_id'");
    
    // Handle Linked Bookings based on status change
    if ($status === 'completed') {
        // Close all bookings
        $conn->query("UPDATE bookings SET booking_status = 'completed' WHERE trip_batch_id = '$batch_id'");
        $msg = "Trip completed. All bookings archived.";
    } elseif ($status === 'active') {
        // If reopening a completed trip, revert bookings to confirmed
        $conn->query("UPDATE bookings SET booking_status = 'confirmed' WHERE trip_batch_id = '$batch_id' AND booking_status = 'completed'");
        $msg = "Trip is now Active.";
    } else {
        $msg = "Trip status reverted to " . ucfirst($status);
    }
    $msg_type = "success";
}

// Check for Edit Mode
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_res = $conn->query("SELECT * FROM trip_batches WHERE id = '$edit_id'");
    if ($edit_res->num_rows > 0) {
        $edit_data = $edit_res->fetch_assoc();
    }
}

// Handle Redirect Message
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $msg = "Trip Batch updated successfully.";
    $msg_type = "success";
}

// Fetch Batches with Counts
$sql = "SELECT tb.*, p.name as package_name, 
               (SELECT COUNT(*) FROM bookings WHERE trip_batch_id = tb.id AND booking_status != 'cancelled') as pilgrim_count
        FROM trip_batches tb
        JOIN packages p ON tb.package_id = p.id
        ORDER BY tb.start_date DESC";
$batches = $conn->query($sql);

// Fetch Packages for Dropdown
$packages = $conn->query("SELECT id, name FROM packages");
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8 flex justify-between items-end">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Trip Lifecycle Manager</h1>
        <p class="text-gray-600 mt-1">Schedule cohorts, manage dates, flight info, and close completed trips.</p>
    </div>
    <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">Back</a>
</div>

<?php if(isset($msg)): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo ($msg_type == 'success') ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500'; ?>">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">
    
    <!-- Left: Create/Edit Form -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-hajjGold">
            <h3 class="font-bold text-lg text-deepGreen mb-4">
                <?php echo $edit_data ? 'Edit Trip Batch' : 'Schedule New Trip'; ?>
            </h3>
            
            <form method="POST" class="space-y-6">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="batch_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Package Type</label>
                        <select name="package_id" class="w-full p-2 border rounded focus:border-deepGreen" required>
                            <?php 
                            if ($packages->num_rows > 0) {
                                $packages->data_seek(0); // Reset pointer
                                while($p = $packages->fetch_assoc()): 
                                    $selected = ($edit_data && $edit_data['package_id'] == $p['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>><?php echo $p['name']; ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Batch Name</label>
                        <input type="text" name="batch_name" 
                               value="<?php echo $edit_data ? htmlspecialchars($edit_data['batch_name']) : ''; ?>" 
                               placeholder="e.g. Hajj 2026 Group A" class="w-full p-2 border rounded focus:border-deepGreen" required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Start Date</label>
                            <input type="date" name="start_date" 
                                   value="<?php echo $edit_data ? $edit_data['start_date'] : ''; ?>" 
                                   class="w-full p-2 border rounded focus:border-deepGreen" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Return Date</label>
                            <input type="date" name="return_date" 
                                   value="<?php echo $edit_data ? $edit_data['return_date'] : ''; ?>" 
                                   class="w-full p-2 border rounded focus:border-deepGreen" required>
                        </div>
                    </div>
                </div>

                <!-- Departure Flight -->
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <span class="text-xs font-bold text-deepGreen uppercase flex items-center gap-2"><i class="fas fa-plane-departure text-gray-400"></i> Departure Flight</span>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Airline</label>
                            <input type="text" name="flight_name" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['flight_name'] ?? '') : ''; ?>" 
                                   placeholder="e.g. Air Peace" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Flight No.</label>
                            <input type="text" name="flight_number" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['flight_number'] ?? '') : ''; ?>" 
                                   placeholder="e.g. P4-7221" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                        </div>
                        <div class="col-span-2 flex gap-2">
                            <div class="flex-grow">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Airport</label>
                                <input type="text" name="departure_airport" 
                                       value="<?php echo $edit_data ? htmlspecialchars($edit_data['departure_airport'] ?? '') : ''; ?>" 
                                       placeholder="e.g. MMIA Lagos" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                            </div>
                            <div class="w-24">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Terminal</label>
                                <input type="text" name="departure_terminal" 
                                       value="<?php echo $edit_data ? htmlspecialchars($edit_data['departure_terminal'] ?? '') : ''; ?>" 
                                       placeholder="e.g. 2" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Flight -->
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <span class="text-xs font-bold text-deepGreen uppercase flex items-center gap-2"><i class="fas fa-plane-arrival text-gray-400"></i> Return Flight</span>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Airline</label>
                            <input type="text" name="return_flight_name" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['return_flight_name'] ?? '') : ''; ?>" 
                                   placeholder="e.g. Saudi Airlines" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Flight No.</label>
                            <input type="text" name="return_flight_number" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['return_flight_number'] ?? '') : ''; ?>" 
                                   placeholder="e.g. SV-421" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                        </div>
                        <div class="col-span-2 flex gap-2">
                            <div class="flex-grow">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Airport</label>
                                <input type="text" name="return_airport" 
                                       value="<?php echo $edit_data ? htmlspecialchars($edit_data['return_airport'] ?? '') : ''; ?>" 
                                       placeholder="e.g. Jeddah (JED)" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                            </div>
                            <div class="w-24">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Terminal</label>
                                <input type="text" name="return_terminal" 
                                       value="<?php echo $edit_data ? htmlspecialchars($edit_data['return_terminal'] ?? '') : ''; ?>" 
                                       placeholder="e.g. Hajj" class="w-full p-2 border rounded text-sm focus:border-deepGreen">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 pt-4 border-t">
                    <button type="submit" name="<?php echo $edit_data ? 'update_batch' : 'create_batch'; ?>" class="flex-grow bg-deepGreen text-white font-bold py-3 rounded hover:bg-teal-800 transition shadow">
                        <?php echo $edit_data ? 'Save Changes' : 'Create Trip Batch'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="manage_trips.php" class="px-4 py-3 bg-gray-200 text-gray-600 rounded hover:bg-gray-300 font-bold text-center">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Trip List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <table class="w-full text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="p-4">Batch Details</th>
                        <th class="p-4">Logistics</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if($batches->num_rows > 0): while($row = $batches->fetch_assoc()): 
                        $status_color = 'bg-blue-100 text-blue-700'; // upcoming
                        if($row['status'] == 'active') $status_color = 'bg-green-100 text-green-700 animate-pulse';
                        if($row['status'] == 'completed') $status_color = 'bg-gray-100 text-gray-500';
                    ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 align-top w-1/3">
                                <p class="font-bold text-deepGreen"><?php echo htmlspecialchars($row['batch_name']); ?></p>
                                <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($row['package_name']); ?></p>
                                <span class="bg-gray-200 px-2 py-1 rounded text-xs font-bold"><i class="fas fa-users text-gray-500 mr-1"></i> <?php echo $row['pilgrim_count']; ?> Pilgrims</span>
                            </td>
                            <td class="p-4 text-gray-600 text-xs align-top space-y-2">
                                <!-- Departure Info -->
                                <div>
                                    <p class="font-bold text-gray-700"><i class="fas fa-plane-departure text-green-600 mr-1"></i> <?php echo date('d M Y', strtotime($row['start_date'])); ?></p>
                                    <?php if(!empty($row['flight_name'])): ?>
                                        <p class="text-[10px] text-gray-500 mt-0.5">
                                            <?php echo htmlspecialchars($row['flight_name'] . ' (' . $row['flight_number'] . ')'); ?>
                                            <?php if(!empty($row['departure_airport'])) echo " | " . htmlspecialchars($row['departure_airport']); ?>
                                            <?php if(!empty($row['departure_terminal'])) echo " T" . htmlspecialchars($row['departure_terminal']); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-[10px] text-gray-400 italic mt-0.5">TBD</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Return Info -->
                                <div>
                                    <p class="font-bold text-gray-700"><i class="fas fa-plane-arrival text-red-400 mr-1"></i> <?php echo date('d M Y', strtotime($row['return_date'])); ?></p>
                                    <?php if(!empty($row['return_flight_name'])): ?>
                                        <p class="text-[10px] text-gray-500 mt-0.5">
                                            <?php echo htmlspecialchars($row['return_flight_name'] . ' (' . $row['return_flight_number'] . ')'); ?>
                                            <?php if(!empty($row['return_airport'])) echo " | " . htmlspecialchars($row['return_airport']); ?>
                                            <?php if(!empty($row['return_terminal'])) echo " T" . htmlspecialchars($row['return_terminal']); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-[10px] text-gray-400 italic mt-0.5">TBD</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4 text-center align-top">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $status_color; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="p-4 text-right align-top">
                                <div class="flex flex-col items-end gap-2">
                                    <!-- Edit Button -->
                                    <a href="manage_trips.php?edit_id=<?php echo $row['id']; ?>" class="text-xs bg-white border border-gray-300 text-gray-600 px-3 py-1 rounded hover:bg-gray-50 transition shadow-sm w-full text-center">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    <!-- Status Controls -->
                                    <form method="POST" class="w-full">
                                        <input type="hidden" name="batch_id" value="<?php echo $row['id']; ?>">
                                        
                                        <?php if($row['status'] == 'upcoming'): ?>
                                            <button type="submit" name="update_status" value="active" class="w-full text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 shadow-sm" title="Start Trip">
                                                Depart
                                            </button>
                                        
                                        <?php elseif($row['status'] == 'active'): ?>
                                            <button type="button" class="w-full text-xs bg-gray-800 text-white px-3 py-1 rounded hover:bg-black shadow-sm mb-1" title="End Trip" onclick="confirmTripCompletion(this, 'completed')">
                                                Complete
                                            </button>
                                            <button type="submit" name="update_status" value="upcoming" class="w-full text-[10px] text-gray-400 hover:text-red-500 underline text-right" title="Undo Departure">
                                                Undo
                                            </button>
                                        
                                        <?php elseif($row['status'] == 'completed'): ?>
                                            <button type="submit" name="update_status" value="active" class="w-full text-xs border border-orange-500 text-orange-500 px-3 py-1 rounded hover:bg-orange-50 shadow-sm" title="Reopen Trip">
                                                Reopen
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">No trips scheduled yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Intercept and use AppUI for form submission confirmation
    function confirmTripCompletion(btn, statusValue) {
        if(typeof AppUI !== 'undefined') {
            AppUI.confirm('Confirm Trip Completion?<br><br><span class="text-sm font-normal text-gray-500">This will officially conclude the journey and archive all associated pilgrim bookings.</span>', () => {
                const form = btn.closest('form');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'update_status';
                input.value = statusValue;
                form.appendChild(input);
                form.submit();
            });
        } else {
            // Fallback just in case
            if(confirm("Confirm Trip Completion?")) {
                const form = btn.closest('form');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'update_status';
                input.value = statusValue;
                form.appendChild(input);
                form.submit();
            }
        }
    }
</script>

<?php include '../includes/footer.php'; ?>