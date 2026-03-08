<?php
// admin/room_manager.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

// 1. Fetch Packages
$packages = $conn->query("SELECT id, name FROM packages");

// 2. Current Selection
$pkg_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
$city = isset($_GET['city']) && in_array($_GET['city'], ['mecca', 'medina']) ? $_GET['city'] : 'mecca';
$selected_month = isset($_GET['trip_month']) ? intval($_GET['trip_month']) : 0;
$selected_year = isset($_GET['trip_year']) ? intval($_GET['trip_year']) : 0;

// 3. Fetch Data
$unassigned = [];
$rooms = [];

if ($pkg_id > 0) {
    // Determine column based on city
    $room_col = ($city === 'mecca') ? 'mecca_room_no' : 'medina_room_no';
    
    $sql = "SELECT b.id as booking_id, m.full_name, m.passport_photo, m.id as member_id, 
                   mp.blood_group, mp.mobility_needs, b.$room_col as room_no
            FROM bookings b
            JOIN members m ON b.member_id = m.id
            LEFT JOIN medical_profiles mp ON m.id = mp.member_id
            WHERE b.package_id = '$pkg_id' AND b.booking_status = 'confirmed'";
    
    // Apply Date Filters
    if ($selected_month > 0) $sql .= " AND MONTH(b.travel_date) = '$selected_month'";
    if ($selected_year > 0) $sql .= " AND YEAR(b.travel_date) = '$selected_year'";

    $sql .= " ORDER BY m.full_name ASC";
            
    $res = $conn->query($sql);
    
    while($row = $res->fetch_assoc()) {
        $r_no = $row['room_no'];
        if (empty($r_no)) {
            $unassigned[] = $row;
        } else {
            $rooms[$r_no][] = $row;
        }
    }
    
    // Sort rooms naturally (101, 102...)
    ksort($rooms, SORT_NATURAL);
}

$months = [1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'];
?>

<?php include '../includes/header.php'; ?>

<div class="h-[calc(100vh-140px)] flex flex-col">
    
    <!-- Header & Controls -->
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-deepGreen"><i class="fas fa-th-large mr-2"></i> Room Manager</h1>
            <p class="text-xs text-gray-500">Drag and drop pilgrims to assign rooms.</p>
        </div>
        
        <form method="GET" class="flex flex-wrap gap-2 items-center justify-end">
            <!-- Package -->
            <select name="package_id" onchange="this.form.submit()" class="p-2 border rounded font-bold text-gray-700 text-sm w-48">
                <option value="0">-- Select Package --</option>
                <?php 
                if ($packages->num_rows > 0) {
                    $packages->data_seek(0);
                    while($p = $packages->fetch_assoc()) {
                        $sel = ($p['id'] == $pkg_id) ? 'selected' : '';
                        echo "<option value='{$p['id']}' $sel>{$p['name']}</option>";
                    }
                }
                ?>
            </select>

            <!-- Month -->
            <select name="trip_month" onchange="this.form.submit()" class="p-2 border rounded font-bold text-gray-700 text-sm w-32">
                <option value="0">All Months</option>
                <?php foreach($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php echo ($selected_month == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Year -->
            <select name="trip_year" onchange="this.form.submit()" class="p-2 border rounded font-bold text-gray-700 text-sm w-24">
                <option value="0">All Years</option>
                <?php for($y = date('Y')-1; $y <= date('Y')+3; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            
            <!-- City Toggle -->
            <div class="flex bg-gray-100 p-1 rounded-lg ml-2">
                <button type="submit" name="city" value="mecca" class="px-4 py-1.5 rounded-md text-sm font-bold transition <?php echo $city=='mecca' ? 'bg-deepGreen text-white shadow' : 'text-gray-500 hover:text-deepGreen'; ?>">
                    Makkah
                </button>
                <button type="submit" name="city" value="medina" class="px-4 py-1.5 rounded-md text-sm font-bold transition <?php echo $city=='medina' ? 'bg-deepGreen text-white shadow' : 'text-gray-500 hover:text-deepGreen'; ?>">
                    Madinah
                </button>
            </div>
        </form>
    </div>

    <?php if($pkg_id == 0): ?>
        <div class="flex-grow flex items-center justify-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 text-gray-400">
            <div class="text-center">
                <i class="fas fa-arrow-up fa-2x mb-2"></i>
                <p>Please select a package to begin rooming.</p>
            </div>
        </div>
    <?php else: ?>

    <!-- Main Workspace -->
    <div class="flex-grow flex gap-6 overflow-hidden">
        
        <!-- Left: Unassigned Sidebar -->
        <div class="w-1/4 bg-gray-50 border border-gray-200 rounded-xl flex flex-col overflow-hidden">
            <div class="p-3 bg-gray-100 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-bold text-gray-600 text-sm">Unassigned (<?php echo count($unassigned); ?>)</h3>
            </div>
            <div class="p-3 overflow-y-auto flex-grow space-y-2 drop-zone min-h-[200px]" data-room="">
                <?php foreach($unassigned as $p): ?>
                    <?php echo renderPilgrimCard($p); ?>
                <?php endforeach; ?>
                <?php if(empty($unassigned)): ?>
                    <div class="text-center text-gray-400 text-xs py-8 italic empty-msg">All pilgrims assigned!</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Rooms Grid -->
        <div class="w-3/4 flex flex-col">
            
            <!-- Toolbar -->
            <div class="mb-4 flex justify-end">
                <button onclick="createNewRoom()" class="bg-hajjGold text-white px-4 py-2 rounded shadow hover:bg-yellow-600 font-bold text-sm transition">
                    <i class="fas fa-plus mr-1"></i> Add Room
                </button>
            </div>

            <!-- Grid Container -->
            <div class="overflow-y-auto flex-grow pr-2 pb-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="rooms-container">
                    <?php foreach($rooms as $num => $occupants): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col room-card">
                            <!-- Room Header -->
                            <div class="p-2 bg-deepGreen text-white rounded-t-lg flex justify-between items-center">
                                <span class="font-bold text-sm"><i class="fas fa-key mr-1 opacity-50"></i> Room <?php echo $num; ?></span>
                                <span class="text-xs bg-white/20 px-2 py-0.5 rounded count-badge"><?php echo count($occupants); ?></span>
                            </div>
                            
                            <!-- Drop Zone -->
                            <div class="p-3 min-h-[120px] drop-zone flex-grow space-y-2" data-room="<?php echo $num; ?>">
                                <?php foreach($occupants as $p): ?>
                                    <?php echo renderPilgrimCard($p); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php
// Helper Function to Render Card
function renderPilgrimCard($p) {
    $photo = $p['passport_photo'] ? '../'.$p['passport_photo'] : 'https://via.placeholder.com/150';
    $mobility_icon = ($p['mobility_needs'] !== 'None' && $p['mobility_needs'] !== '') 
        ? '<i class="fas fa-wheelchair text-red-500 ml-1" title="'.$p['mobility_needs'].'"></i>' 
        : '';
    
    return "
    <div class='bg-white p-2 rounded border border-gray-200 shadow-sm cursor-grab active:cursor-grabbing hover:border-deepGreen transition flex items-center gap-3 draggable-item' draggable='true' data-id='{$p['booking_id']}'>
        <div class='w-8 h-8 rounded-full bg-gray-100 overflow-hidden flex-shrink-0 border border-gray-200'>
            <img src='$photo' class='w-full h-full object-cover'>
        </div>
        <div class='overflow-hidden'>
            <p class='text-xs font-bold text-gray-700 truncate leading-tight'>{$p['full_name']} $mobility_icon</p>
            <p class='text-[10px] text-gray-400'>ID: ".str_pad($p['member_id'], 4, '0', STR_PAD_LEFT)."</p>
        </div>
    </div>
    ";
}
?>

<!-- Drag & Drop Logic -->
<script>
    const currentCity = "<?php echo $city; ?>";
    let draggedItem = null;

    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop();
    });

    function initDragAndDrop() {
        // Use 'is-initialized' class to prevent attaching duplicate events when new rooms are added
        const draggables = document.querySelectorAll('.draggable-item:not(.is-initialized)');
        const zones = document.querySelectorAll('.drop-zone:not(.is-initialized)');

        draggables.forEach(draggable => {
            draggable.classList.add('is-initialized');
            draggable.addEventListener('dragstart', () => {
                draggedItem = draggable;
                draggable.classList.add('opacity-50');
            });

            draggable.addEventListener('dragend', () => {
                draggedItem = null;
                draggable.classList.remove('opacity-50');
            });
        });

        zones.forEach(zone => {
            zone.classList.add('is-initialized');
            zone.addEventListener('dragover', (e) => {
                e.preventDefault(); // Allow dropping
                zone.classList.add('bg-green-50');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('bg-green-50');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('bg-green-50');
                
                if (draggedItem) {
                    const bookingId = draggedItem.getAttribute('data-id');
                    const newRoom = zone.getAttribute('data-room');
                    
                    // Optimistic UI Update
                    zone.appendChild(draggedItem);
                    updateCounts();
                    checkEmptyState();

                    // API Call
                    saveRoomAssignment(bookingId, newRoom);
                }
            });
        });
    }

    function saveRoomAssignment(bookingId, roomNumber) {
        fetch('api_room_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'move_pilgrim',
                booking_id: bookingId,
                room_number: roomNumber,
                city: currentCity
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success') {
                if (window.AppUI) window.AppUI.toast('Error saving room: ' + data.message, 'error');
                else alert('Error saving room: ' + data.message);
                setTimeout(() => location.reload(), 1500); // Revert on error
            }
        })
        .catch(err => {
            console.error(err);
            if (window.AppUI) window.AppUI.toast('Network error saving room.', 'error');
        });
    }

    function createNewRoom() {
        const handleRoomCreation = (rawRoomNo) => {
            if (!rawRoomNo) return;
            
            const roomNo = rawRoomNo.trim();
            if (roomNo === "") return;

            // Check if room already exists
            const exists = document.querySelector(`.drop-zone[data-room="${roomNo}"]`);
            if(exists) {
                if (window.AppUI) window.AppUI.toast("Room " + roomNo + " already exists!", "error");
                else alert("Room " + roomNo + " already exists!");
                return;
            }

            const container = document.getElementById('rooms-container');
            const newCard = document.createElement('div');
            newCard.className = 'bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col room-card animate-pulse'; 
            newCard.innerHTML = `
                <div class="p-2 bg-deepGreen text-white rounded-t-lg flex justify-between items-center">
                    <span class="font-bold text-sm"><i class="fas fa-key mr-1 opacity-50"></i> Room ${roomNo}</span>
                    <span class="text-xs bg-white/20 px-2 py-0.5 rounded count-badge">0</span>
                </div>
                <div class="p-3 min-h-[120px] drop-zone flex-grow space-y-2" data-room="${roomNo}"></div>
            `;
            
            container.prepend(newCard); // Add to top
            setTimeout(() => newCard.classList.remove('animate-pulse'), 1000);
            
            // Initialize listeners ONLY for the new items
            initDragAndDrop();
        };

        // Fallback safely to native prompt if AppUI is missing or still loading
        if (window.AppUI) {
            window.AppUI.prompt("Enter new Room Number (e.g., 301):", handleRoomCreation);
        } else {
            const val = prompt("Enter new Room Number (e.g., 301):");
            if (val !== null) handleRoomCreation(val);
        }
    }

    function updateCounts() {
        document.querySelectorAll('.room-card').forEach(card => {
            const zone = card.querySelector('.drop-zone');
            if (zone) {
                card.querySelector('.count-badge').innerText = zone.children.length;
            }
        });
    }

    function checkEmptyState() {
        const sidebar = document.querySelector('.w-1\\/4 .drop-zone');
        if (!sidebar) return; // Fail gracefully
        const emptyMsg = sidebar.querySelector('.empty-msg');
        
        const items = sidebar.querySelectorAll('.draggable-item').length;
        
        if (items === 0) {
            if(!emptyMsg) {
                sidebar.insertAdjacentHTML('beforeend', '<div class="text-center text-gray-400 text-xs py-8 italic empty-msg">All pilgrims assigned!</div>');
            }
        } else {
            if(emptyMsg) emptyMsg.remove();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>