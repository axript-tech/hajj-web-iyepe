<?php
// admin/batch_manager.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/mailer.php';

// Access Control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php"); 
    exit();
}

// --- AJAX HANDLER FOR DRAG AND DROP ---
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['action']) && $input['action'] === 'move_pilgrim_batch') {
    ob_clean(); // Ensure pure JSON response
    header('Content-Type: application/json');
    
    $booking_id = intval($input['booking_id']);
    $batch_id_raw = $input['target_batch_id'];
    
    if ($batch_id_raw === 'unassigned' || empty($batch_id_raw)) {
        $target_batch_id = 'NULL';
        $travel_date = 'NULL';
    } else {
        $target_batch_id = intval($batch_id_raw);
        $b_res = $conn->query("SELECT start_date, batch_name FROM trip_batches WHERE id = $target_batch_id");
        if($b_res->num_rows > 0) {
            $batch_info = $b_res->fetch_assoc();
            $travel_date = "'" . $batch_info['start_date'] . "'";
            $batch_name = $batch_info['batch_name'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid batch']);
            exit;
        }
    }
    
    $sql = "UPDATE bookings SET trip_batch_id = $target_batch_id, travel_date = $travel_date WHERE id = '$booking_id'";
    
    if ($conn->query($sql)) {
        // Send email only if moving TO a specific batch (not unassigning)
        if ($target_batch_id !== 'NULL') {
            $user_sql = "SELECT m.email, m.full_name FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.id = '$booking_id'";
            $u = $conn->query($user_sql)->fetch_assoc();
            if ($u) {
                $msg_body = "You have been assigned to your travel cohort: <strong>$batch_name</strong>. You can now view your flight itinerary and access the group chat from your dashboard.";
                send_hajj_mail($u['email'], $u['full_name'], "Trip Cohort Assigned", $msg_body);
            }
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

$msg = '';
$msg_type = '';
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;

// --- HANDLE AUTO ASSIGNMENT (Bulk) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign']) && $package_id > 0) {
    // 1. Fetch unassigned confirmed pilgrims
    $unassigned_res = $conn->query("SELECT b.id as booking_id, m.email, m.full_name 
                                    FROM bookings b JOIN members m ON b.member_id = m.id 
                                    WHERE b.package_id = '$package_id' AND b.trip_batch_id IS NULL AND b.booking_status = 'confirmed'");
    
    // 2. Fetch upcoming cohorts and their specific capacity
    $batches_res = $conn->query("SELECT b.id as batch_id, b.batch_name, b.start_date, b.capacity, 
                                        (SELECT COUNT(id) FROM bookings WHERE trip_batch_id = b.id AND booking_status='confirmed') as current_count 
                                 FROM trip_batches b 
                                 WHERE b.package_id = '$package_id' AND b.status = 'upcoming' ORDER BY b.start_date ASC");
    
    $batches = [];
    while($b = $batches_res->fetch_assoc()) {
        $total_capacity = intval($b['capacity'] ?? 50); 
        $b['remaining_capacity'] = max(0, $total_capacity - $b['current_count']);
        $batches[] = $b;
    }
    
    $assigned_count = 0;
    $batch_idx = 0;
    
    if (count($batches) > 0 && $unassigned_res->num_rows > 0) {
        while ($pilgrim = $unassigned_res->fetch_assoc()) {
            while ($batch_idx < count($batches) && $batches[$batch_idx]['remaining_capacity'] <= 0) {
                $batch_idx++;
            }
            if ($batch_idx >= count($batches)) break; 
            
            $target_batch = $batches[$batch_idx];
            $b_id = $pilgrim['booking_id'];
            $batch_id_val = $target_batch['batch_id'];
            $travel_date = $target_batch['start_date'];
            
            $conn->query("UPDATE bookings SET trip_batch_id = '$batch_id_val', travel_date = '$travel_date' WHERE id = '$b_id'");
            
            $msg_body = "You have been automatically assigned to your travel cohort: <strong>{$target_batch['batch_name']}</strong>. You can now view your flight itinerary and access the group chat from your dashboard.";
            send_hajj_mail($pilgrim['email'], $pilgrim['full_name'], "Trip Cohort Assigned", $msg_body);
            
            $batches[$batch_idx]['remaining_capacity']--;
            $assigned_count++;
        }
        $msg = "Auto-assigned $assigned_count pilgrims successfully based on available cohort capacities.";
        $msg_type = 'success';
    } elseif ($unassigned_res->num_rows == 0) {
        $msg = "No unassigned pilgrims available for this package.";
        $msg_type = 'info';
    } else {
        $msg = "No upcoming batches with available capacity found for this package.";
        $msg_type = 'error';
    }
}

// Fetch Active Packages for Dropdown
$packages = $conn->query("SELECT id, name FROM packages ORDER BY name ASC");

// Initialize state
$unassigned = [];
$cohorts = [];

if ($package_id > 0) {
    // 1. Fetch unassigned pilgrims for the selected package INCLUDING FINANCIALS
    $unassigned_sql = "SELECT b.id as booking_id, m.id as member_id, m.full_name, m.passport_photo, m.phone, b.created_at,
                              b.amount_paid, b.total_due 
                       FROM bookings b 
                       JOIN members m ON b.member_id = m.id 
                       WHERE b.package_id = '$package_id' AND b.trip_batch_id IS NULL AND b.booking_status = 'confirmed'
                       ORDER BY b.created_at ASC";
    $u_res = $conn->query($unassigned_sql);
    if($u_res) while($row = $u_res->fetch_assoc()) $unassigned[] = $row;

    // 2. Fetch upcoming cohorts and their currently assigned pilgrims
    $cohorts_sql = "SELECT b.id, b.batch_name, b.start_date, b.capacity 
                    FROM trip_batches b 
                    WHERE b.package_id = '$package_id' AND b.status = 'upcoming'
                    ORDER BY b.start_date ASC";
    $c_res = $conn->query($cohorts_sql);
    
    if($c_res) {
        while($batch = $c_res->fetch_assoc()) {
            $b_id = $batch['id'];
            $batch['pilgrims'] = [];
            
            // FETCH ASSIGNED PILGRIMS INCLUDING FINANCIALS
            $p_sql = "SELECT b.id as booking_id, m.id as member_id, m.full_name, m.passport_photo, m.phone,
                             b.amount_paid, b.total_due 
                      FROM bookings b JOIN members m ON b.member_id = m.id 
                      WHERE b.trip_batch_id = '$b_id' AND b.booking_status = 'confirmed'
                      ORDER BY m.full_name ASC";
            $p_res = $conn->query($p_sql);
            if($p_res) while($p = $p_res->fetch_assoc()) $batch['pilgrims'][] = $p;
            
            $batch['current_count'] = count($batch['pilgrims']);
            $cohorts[] = $batch;
        }
    }
}

// Helper Function for Cards (Now includes payment info)
function renderPilgrimCard($p) {
    $photo = $p['passport_photo'] ? '../'.$p['passport_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($p['full_name']).'&background=f3f4f6&color=1B7D75';
    
    // Financial calculations
    $total_due = floatval($p['total_due']);
    $amount_paid = floatval($p['amount_paid']);
    $balance = max(0, $total_due - $amount_paid);
    $percent_paid = ($total_due > 0) ? min(100, round(($amount_paid / $total_due) * 100)) : 100;
    
    // Status styling based on financial readiness
    // Let's say 50% is a reasonable threshold to assign, but 100% is ideal
    $status_color = 'text-red-500';
    $status_bg = 'bg-red-100';
    $status_icon = 'fa-times-circle';
    
    if ($percent_paid >= 100) {
        $status_color = 'text-green-600';
        $status_bg = 'bg-green-100';
        $status_icon = 'fa-check-circle';
    } elseif ($percent_paid >= 50) {
        $status_color = 'text-yellow-600';
        $status_bg = 'bg-yellow-100';
        $status_icon = 'fa-exclamation-circle';
    }

    // Format currency nicely
    $formatted_paid = CURRENCY . number_format($amount_paid);
    
    return "
    <div class='bg-white p-3 rounded-lg border border-gray-200 shadow-sm cursor-grab active:cursor-grabbing hover:border-deepGreen transition flex flex-col gap-2 draggable-item' draggable='true' data-id='{$p['booking_id']}'>
        <div class='flex items-center gap-3'>
            <div class='w-10 h-10 rounded-full bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-200'>
                <img src='$photo' class='w-full h-full object-cover'>
            </div>
            <div class='overflow-hidden flex-grow'>
                <p class='text-sm font-bold text-gray-800 truncate leading-tight'>{$p['full_name']}</p>
                <p class='text-[10px] text-gray-500 font-mono'>ID: #".str_pad($p['member_id'], 4, '0', STR_PAD_LEFT)."</p>
            </div>
        </div>
        
        <div class='bg-gray-50 rounded p-2 border border-gray-100 mt-1 flex justify-between items-center'>
            <div>
                <p class='text-[9px] text-gray-500 uppercase font-bold'>Paid</p>
                <p class='text-xs font-bold text-gray-800'>$formatted_paid <span class='text-[10px] font-normal text-gray-400'>({$percent_paid}%)</span></p>
            </div>
            <div class='text-right'>
               <span class='text-[10px] px-1.5 py-0.5 rounded font-bold flex items-center gap-1 $status_color $status_bg' title='Balance: ".CURRENCY.number_format($balance)."'>
                   <i class='fas $status_icon'></i> ".($percent_paid >= 100 ? 'Cleared' : 'Owing')."
               </span>
            </div>
        </div>
    </div>
    ";
}
?>

<?php include '../includes/header.php'; ?>

<div class="h-[calc(100vh-140px)] flex flex-col max-w-7xl mx-auto">
    
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-200 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-layer-group text-hajjGold"></i> Batch Allocation</h1>
            <p class="text-xs text-gray-500 mt-1">Drag and drop pilgrims into their travel cohorts.</p>
        </div>
        
        <!-- Package Selector -->
        <form method="GET" class="flex gap-2 items-center">
            <span class="text-xs font-bold text-gray-500 uppercase">Target Package:</span>
            <select name="package_id" onchange="this.form.submit()" class="p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 font-bold text-gray-700 min-w-[250px] shadow-sm">
                <option value="0">-- Select a Package --</option>
                <?php if ($packages && $packages->num_rows > 0): ?>
                    <?php while($p = $packages->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($package_id == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </form>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl border-l-4 shadow-sm mb-4 shrink-0 <?php echo ($msg_type === 'success') ? 'bg-green-50 border-green-500 text-green-700' : (($msg_type === 'info') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-red-50 border-red-500 text-red-700'); ?>">
            <span class="font-bold flex items-center gap-2">
                <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-info-circle'; ?>"></i> <?php echo $msg; ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ($package_id == 0): ?>
        <div class="flex-grow bg-white p-16 rounded-2xl shadow-sm border border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400">
            <i class="fas fa-hand-pointer fa-4x mb-4 opacity-30 text-deepGreen"></i>
            <h2 class="text-xl font-bold text-gray-600">Select a Package to Begin</h2>
            <p class="mt-2 text-sm max-w-md mx-auto text-center">Choose a package from the top right dropdown to view unassigned pilgrims and distribute them into active cohorts via drag-and-drop.</p>
        </div>
    <?php else: ?>
        
        <!-- Main Drag & Drop Workspace -->
        <div class="flex-grow flex gap-6 overflow-hidden">
            
            <!-- Left Column: Unassigned Pool -->
            <div class="w-1/3 bg-gray-50 border border-gray-200 rounded-xl flex flex-col overflow-hidden shadow-sm">
                
                <div class="p-4 bg-white border-b border-gray-200 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-800">Unassigned Pool</h3>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mt-0.5"><span id="unassigned-count"><?php echo count($unassigned); ?></span> Pending</p>
                    </div>
                    <?php if (count($unassigned) > 0 && count($cohorts) > 0): ?>
                        <form method="POST">
                            <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                            <button type="submit" name="auto_assign" value="1" class="bg-hajjGold text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow hover:bg-yellow-600 transition flex items-center gap-1" title="Sequentially fill batches">
                                <i class="fas fa-magic"></i> Auto-Fill
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Unassigned Drop Zone -->
                <div class="p-4 overflow-y-auto flex-grow space-y-3 drop-zone" data-batch="unassigned">
                    <?php foreach($unassigned as $p): ?>
                        <?php echo renderPilgrimCard($p); ?>
                    <?php endforeach; ?>
                    <div class="text-center text-gray-400 text-xs py-8 italic empty-msg <?php echo count($unassigned)>0 ? 'hidden' : ''; ?>">
                        All pilgrims assigned!
                    </div>
                </div>
            </div>

            <!-- Right Column: Target Cohorts -->
            <div class="w-2/3 flex flex-col overflow-hidden">
                <div class="overflow-y-auto flex-grow pr-2 pb-10">
                    <?php if (count($cohorts) > 0): ?>
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <?php foreach($cohorts as $batch): 
                                $total_slots = intval($batch['capacity'] ?? 50);
                                $current = $batch['current_count'];
                                $is_full = ($current >= $total_slots && $total_slots > 0);
                            ?>
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col batch-card overflow-hidden h-[450px]">
                                    <!-- Header -->
                                    <div class="p-3 bg-deepGreen text-white flex justify-between items-center shrink-0">
                                        <div>
                                            <h4 class="font-bold text-sm leading-tight flex items-center gap-2">
                                                <i class="fas fa-plane text-hajjGold"></i> <?php echo htmlspecialchars($batch['batch_name']); ?>
                                            </h4>
                                            <p class="text-[10px] text-green-200 mt-0.5">Departs: <?php echo date('d M Y', strtotime($batch['start_date'])); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs bg-white/20 px-2 py-1 rounded font-bold count-badge" data-max="<?php echo $total_slots; ?>">
                                                <?php echo $current; ?> / <?php echo $total_slots; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Drop Zone -->
                                    <div class="p-3 bg-gray-50 flex-grow overflow-y-auto space-y-2 drop-zone" data-batch="<?php echo $batch['id']; ?>">
                                        <?php foreach($batch['pilgrims'] as $p): ?>
                                            <?php echo renderPilgrimCard($p); ?>
                                        <?php endforeach; ?>
                                        <div class="text-center text-gray-400 text-xs py-4 italic empty-msg <?php echo count($batch['pilgrims'])>0 ? 'hidden' : ''; ?>">
                                            Drop pilgrims here to assign.
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 rounded-xl shadow-sm border border-yellow-200 p-8 text-center text-yellow-800">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3 text-yellow-500"></i>
                            <h3 class="font-bold text-lg mb-2">No active cohorts found.</h3>
                            <p class="text-sm opacity-80 mb-4">You need to create 'Upcoming' trip batches for this package before you can assign pilgrims.</p>
                            <a href="manage_trips.php" class="inline-block font-bold bg-yellow-200 px-6 py-3 rounded-lg hover:bg-yellow-300 transition text-yellow-800 shadow-sm">Manage Trip Cohorts</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    <?php endif; ?>
</div>

<!-- Drag & Drop Logic -->
<script>
    let draggedItem = null;

    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop();
    });

    function initDragAndDrop() {
        const draggables = document.querySelectorAll('.draggable-item');
        const zones = document.querySelectorAll('.drop-zone');

        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', () => {
                draggedItem = draggable;
                setTimeout(() => draggable.classList.add('opacity-40', 'scale-95'), 0);
            });

            draggable.addEventListener('dragend', () => {
                draggedItem.classList.remove('opacity-40', 'scale-95');
                draggedItem = null;
            });
        });

        zones.forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault(); // Allow dropping
                zone.classList.add('bg-green-100/50', 'ring-2', 'ring-deepGreen', 'ring-inset');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('bg-green-100/50', 'ring-2', 'ring-deepGreen', 'ring-inset');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('bg-green-100/50', 'ring-2', 'ring-deepGreen', 'ring-inset');
                
                if (draggedItem) {
                    const bookingId = draggedItem.getAttribute('data-id');
                    const targetBatch = zone.getAttribute('data-batch');
                    
                    // Capacity Enforcement (Soft limit visually, but enforced strictly in JS)
                    if (targetBatch !== 'unassigned') {
                        const badge = zone.parentElement.querySelector('.count-badge');
                        const current = zone.querySelectorAll('.draggable-item').length;
                        const max = parseInt(badge.getAttribute('data-max') || 999);
                        
                        if (current >= max && zone !== draggedItem.parentElement) {
                            if (window.AppUI) window.AppUI.toast("Cannot assign: This cohort is already at full capacity.", "error");
                            else alert("Cohort is full!");
                            return; // Stop the drop
                        }
                    }

                    // Optimistic UI Update
                    zone.appendChild(draggedItem);
                    updateCounts();

                    // API Call
                    saveBatchAssignment(bookingId, targetBatch);
                }
            });
        });
    }

    function updateCounts() {
        // Update Batches
        document.querySelectorAll('.batch-card').forEach(card => {
            const zone = card.querySelector('.drop-zone');
            const badge = card.querySelector('.count-badge');
            const max = badge.getAttribute('data-max');
            const count = zone.querySelectorAll('.draggable-item').length;
            
            badge.innerText = `${count} / ${max}`;
            
            if(count >= max) {
                badge.classList.remove('bg-white/20');
                badge.classList.add('bg-red-500', 'text-white');
            } else {
                badge.classList.add('bg-white/20');
                badge.classList.remove('bg-red-500', 'text-white');
            }

            const emptyMsg = zone.querySelector('.empty-msg');
            if(count === 0) emptyMsg.classList.remove('hidden');
            else emptyMsg.classList.add('hidden');
        });

        // Update Unassigned
        const unassignedZone = document.querySelector('.drop-zone[data-batch="unassigned"]');
        if (unassignedZone) {
            const unCount = unassignedZone.querySelectorAll('.draggable-item').length;
            document.getElementById('unassigned-count').innerText = unCount;
            
            const unEmptyMsg = unassignedZone.querySelector('.empty-msg');
            if(unCount === 0) unEmptyMsg.classList.remove('hidden');
            else unEmptyMsg.classList.add('hidden');
        }
    }

    function saveBatchAssignment(bookingId, targetBatchId) {
        fetch('batch_manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'move_pilgrim_batch',
                booking_id: bookingId,
                target_batch_id: targetBatchId
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success') {
                if (window.AppUI) window.AppUI.toast('Error saving assignment: ' + data.message, 'error');
                else alert('Error: ' + data.message);
                setTimeout(() => location.reload(), 1500); // Revert on error
            } else {
                if (window.AppUI) {
                    if (targetBatchId === 'unassigned') window.AppUI.toast('Pilgrim returned to pool.', 'info');
                    else window.AppUI.toast('Pilgrim assigned to cohort.', 'success');
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (window.AppUI) window.AppUI.toast('Network error saving assignment.', 'error');
        });
    }
</script>

<?php include '../includes/footer.php'; ?>