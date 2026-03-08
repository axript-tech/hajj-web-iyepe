<?php
// dashboard.php
require_once 'includes/auth_session.php'; 
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];

// Fetch User Data
$user_sql = "SELECT full_name, passport_photo, email, phone, status FROM members WHERE id = '$user_id'";
$me = $conn->query($user_sql)->fetch_assoc();

// HANDLE MANUAL PROOF UPLOAD (For Add-ons)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    $item_id = intval($_POST['item_id']);
    $booking_id = intval($_POST['booking_id']);
    $amount = floatval($_POST['amount']);

    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = "proof_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $upload_dir = 'assets/uploads/proofs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $dest = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $dest)) {
                $stmt = $conn->prepare("INSERT INTO pilgrim_item_payments (member_id, booking_id, item_id, amount, status, proof_path) VALUES (?, ?, ?, ?, 'verifying', ?)");
                $stmt->bind_param("iiids", $user_id, $booking_id, $item_id, $amount, $dest);
                $stmt->execute();
                $proof_msg = "Proof uploaded successfully! Awaiting Admin verification.";
            } else {
                $proof_error = "Upload failed due to server error.";
            }
        } else {
            $proof_error = "Invalid file format. Only JPG, PNG, and PDF allowed.";
        }
    }
}

// Fetch ALL Bookings (Active & Completed) - INCLUDES NEW FLIGHT COLUMNS
$stmt = $conn->prepare("
    SELECT b.*, p.name as package_name, p.total_cost, p.mecca_hotel, p.medina_hotel,
           tb.batch_name, tb.status as trip_status, tb.start_date, tb.return_date,
           tb.flight_name, tb.flight_number, tb.departure_airport, tb.departure_terminal,
           tb.return_flight_name, tb.return_flight_number, tb.return_airport, tb.return_terminal
    FROM bookings b 
    JOIN packages p ON b.package_id = p.id 
    LEFT JOIN trip_batches tb ON b.trip_batch_id = tb.id
    WHERE b.member_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Payments
$pay_hist = $conn->query("SELECT * FROM payments WHERE member_id = '$user_id' ORDER BY payment_date DESC");

// FETCH ANNOUNCEMENTS (Global + Any Cohort the user belongs to)
$alerts = [];
$batch_ids = array_filter(array_column($bookings, 'trip_batch_id'));
$batch_ids_str = !empty($batch_ids) ? implode(',', $batch_ids) : '0';

$alert_sql = "SELECT * FROM announcements WHERE trip_batch_id IS NULL OR trip_batch_id IN ($batch_ids_str) ORDER BY created_at DESC LIMIT 3";
$alert_res = $conn->query($alert_sql);
while($row = $alert_res->fetch_assoc()) { $alerts[] = $row; }
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-6xl mx-auto space-y-8">
    
    <!-- Hero / Header Section -->
    <div class="relative bg-gradient-to-r from-deepGreen to-[#0f4d47] rounded-3xl shadow-xl overflow-hidden text-white">
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/arabesque.png')] pointer-events-none"></div>
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-hajjGold rounded-full opacity-20 blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 p-8 md:p-10 flex flex-col md:flex-row items-center md:items-start gap-8">
            <div class="relative group flex-shrink-0">
                <div class="w-28 h-28 bg-white/10 p-1 rounded-full backdrop-blur-sm border border-white/20 shadow-2xl">
                    <div class="w-full h-full rounded-full overflow-hidden bg-gray-200">
                        <?php if($me['passport_photo']): ?>
                            <img src="<?php echo htmlspecialchars($me['passport_photo']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 bg-white"><i class="fas fa-user fa-3x"></i></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="absolute -bottom-2 inset-x-0 flex justify-center">
                    <?php if($me['status'] === 'active'): ?>
                        <span class="bg-green-500 text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase shadow-md border border-white/20 tracking-wider">Active</span>
                    <?php elseif($me['status'] === 'pending'): ?>
                        <span class="bg-yellow-500 text-yellow-900 text-[10px] px-3 py-1 rounded-full font-bold uppercase shadow-md border border-white/20 tracking-wider">Pending</span>
                    <?php else: ?>
                        <span class="bg-red-500 text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase shadow-md border border-white/20 tracking-wider"><?php echo ucfirst($me['status']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center md:text-left flex-grow">
                <h1 class="text-3xl md:text-4xl font-bold mb-1">Welcome, <?php echo htmlspecialchars($me['full_name']); ?></h1>
                <p class="text-green-100/80 mb-6 flex items-center justify-center md:justify-start gap-2">
                    <i class="fas fa-envelope text-xs"></i> <?php echo $me['email']; ?>
                </p>
                
                <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                    <a href="profile.php" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white px-5 py-2.5 rounded-xl transition backdrop-blur-sm text-sm font-semibold flex items-center gap-2"><i class="fas fa-cog"></i> Account</a>
                    <a href="user_medical.php" class="bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-white px-5 py-2.5 rounded-xl transition backdrop-blur-sm text-sm font-semibold flex items-center gap-2"><i class="fas fa-heartbeat"></i> Medical</a>
                    <a href="dispute_transaction.php" class="bg-orange-500/20 hover:bg-orange-500/30 border border-orange-500/30 text-white px-5 py-2.5 rounded-xl transition backdrop-blur-sm text-sm font-semibold flex items-center gap-2"><i class="fas fa-flag"></i> Disputes</a>
                    <?php if (!empty($bookings) && $me['status'] === 'active'): ?>
                        <a href="select_package.php" class="bg-hajjGold text-deepGreen px-5 py-2.5 rounded-xl font-bold hover:bg-yellow-500 transition shadow-lg flex items-center gap-2 text-sm ml-auto"><i class="fas fa-plus"></i> New Trip</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Feedback -->
    <?php if(isset($proof_msg)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm text-green-700 font-bold flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?php echo $proof_msg; ?>
        </div>
    <?php endif; ?>
    <?php if(isset($proof_error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm text-red-700 font-bold flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i> <?php echo $proof_error; ?>
        </div>
    <?php endif; ?>

    <!-- JOURNEY CARDS -->
    <?php if (empty($bookings)): ?>
        <!-- EMPTY STATE -->
        <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100 text-center">
            <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6"><i class="fas fa-kaaba fa-3x text-deepGreen opacity-80"></i></div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Begin Your Journey</h3>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">You have no active bookings. Select a Hajj or Umrah package and secure your slot for the upcoming season.</p>
            <a href="select_package.php" class="inline-flex items-center gap-2 bg-deepGreen text-white px-8 py-4 rounded-xl font-bold hover:bg-teal-800 shadow-lg transition transform hover:-translate-y-1">View Packages <i class="fas fa-arrow-right"></i></a>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider ml-1">Your Journeys</h3>
            
            <?php foreach($bookings as $booking): ?>
                <?php 
                    $balance = $booking['total_due'] - $booking['amount_paid'];
                    $percent = ($booking['total_due'] > 0) ? ($booking['amount_paid'] / $booking['total_due']) * 100 : 0;
                    
                    $days_left = "TBD"; $travel_display = "Pending Dates";
                    if (!empty($booking['start_date'])) {
                        $ts = strtotime($booking['start_date']);
                        $travel_display = date('F Y', $ts);
                        if (isset($booking['trip_status'])) {
                            if ($booking['trip_status'] === 'completed') $days_left = "Ended";
                            elseif ($booking['trip_status'] === 'active') $days_left = "In Progress";
                            else $days_left = floor(($ts - time()) / 86400) . " Days";
                        }
                    }
                    $is_completed = (isset($booking['trip_status']) && $booking['trip_status'] == 'completed');

                    // FETCH ADD-ONS FOR THIS SPECIFIC BOOKING
                    $batch_id = $booking['trip_batch_id'];
                    $b_id = $booking['id'];
                    $addons_sql = "SELECT tai.*, pip.status as pip_status, pip.admin_note 
                                   FROM trip_additional_items tai 
                                   LEFT JOIN pilgrim_item_payments pip ON tai.id = pip.item_id AND pip.booking_id = '$b_id' 
                                   WHERE tai.trip_batch_id = '$batch_id'";
                    $addons_res = $conn->query($addons_sql);
                    $has_addons = $addons_res && $addons_res->num_rows > 0;
                ?>

                <!-- Trip Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col transition hover:shadow-md relative <?php echo $is_completed ? 'opacity-90 grayscale-[10%]' : ''; ?>">
                    
                    <!-- Card Header -->
                    <div class="bg-gray-50/80 border-b border-gray-100 p-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-xl font-bold text-deepGreen"><?php echo $booking['package_name']; ?></h3>
                                <?php if($is_completed): ?>
                                    <span class="text-[10px] bg-gray-200 text-gray-600 px-2 py-1 rounded-full font-bold uppercase tracking-wide">Archived</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold uppercase tracking-wide border border-blue-200">Active Booking</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 font-medium">
                                <i class="fas fa-calendar-alt mr-1 text-gray-400"></i> Cohort: <?php echo $travel_display; ?>
                                <span class="mx-2 text-gray-300">|</span>
                                <i class="fas fa-hourglass-half mr-1 text-gray-400"></i> Departure: <span class="font-bold <?php echo ($days_left === 'Ended') ? 'text-gray-500' : 'text-deepGreen'; ?>"><?php echo $days_left; ?></span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a href="group_chat.php?batch_id=<?php echo $booking['trip_batch_id']; ?>" class="bg-white border border-gray-200 hover:border-deepGreen hover:text-deepGreen text-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm"><i class="fas fa-comments text-hajjGold"></i> Chat</a>
                            <a href="id_card.php?booking_id=<?php echo $booking['id']; ?>" target="_blank" class="bg-white border border-gray-200 hover:border-gray-800 hover:text-gray-800 text-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm"><i class="fas fa-id-card"></i> ID</a>
                        </div>
                    </div>

                    <!-- Main Grid -->
                    <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        
                        <!-- Finances -->
                        <div class="p-6 md:p-8 space-y-6">
                            <div class="flex justify-between items-end">
                                <div><p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Cost</p><p class="text-xl font-bold text-gray-800"><?php echo CURRENCY . number_format($booking['total_due']); ?></p></div>
                                <div class="text-right"><p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Balance</p><p class="text-2xl font-bold <?php echo ($balance > 0) ? 'text-red-500' : 'text-green-500'; ?>"><?php echo CURRENCY . number_format($balance); ?></p></div>
                            </div>
                            <!-- Progress Bar -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-2"><span class="text-gray-500">Progress</span><span class="text-deepGreen"><?php echo number_format($percent, 1); ?>%</span></div>
                                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                    <div class="bg-deepGreen h-full transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </div>
                            <!-- Payment Action -->
                            <?php if ($balance > 0 && !$is_completed): ?>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                                    <form action="process_payment.php" method="POST" class="flex gap-2">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <div class="relative flex-grow">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold"><?php echo CURRENCY; ?></span>
                                            <input type="number" id="amount_<?php echo $booking['id']; ?>" name="amount" min="5000" max="<?php echo $balance; ?>" placeholder="Amount" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-xl focus:border-deepGreen focus:ring-1 outline-none font-bold text-gray-800" required>
                                        </div>
                                        <button type="button" onclick="payWithPaystack('<?php echo $me['email']; ?>', document.getElementById('amount_<?php echo $booking['id']; ?>').value, 'verify_payment.php?booking_id=<?php echo $booking['id']; ?>')" class="bg-hajjGold text-white font-bold px-6 py-3 rounded-xl hover:bg-yellow-500 shadow-md transition whitespace-nowrap">Pay</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Logistics -->
                        <div class="p-6 md:p-8 bg-gray-50/30 flex flex-col justify-start space-y-8">
                            
                            <!-- FLIGHT ITINERARY -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2"><i class="fas fa-plane"></i> Flight Itinerary</h4>
                                <div class="space-y-3">
                                    
                                    <!-- Departure Flight -->
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="bg-blue-50 w-8 h-8 rounded-full flex items-center justify-center text-blue-500 flex-shrink-0"><i class="fas fa-plane-departure text-xs"></i></div>
                                        <div class="flex-grow">
                                            <div class="flex justify-between items-center mb-0.5">
                                                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Departure</span>
                                                <span class="text-[10px] font-bold text-deepGreen"><?php echo !empty($booking['start_date']) ? date('d M Y', strtotime($booking['start_date'])) : 'TBD'; ?></span>
                                            </div>
                                            <p class="font-bold text-gray-800 text-sm leading-tight">
                                                <?php echo !empty($booking['flight_name']) ? htmlspecialchars($booking['flight_name'] . ' (' . $booking['flight_number'] . ')') : 'Airline TBD'; ?>
                                            </p>
                                            <?php if(!empty($booking['departure_airport'])): ?>
                                                <p class="text-[10px] text-gray-500 mt-0.5"><i class="fas fa-map-marker-alt text-gray-300"></i> <?php echo htmlspecialchars($booking['departure_airport']); ?><?php echo !empty($booking['departure_terminal']) ? ' (T' . htmlspecialchars($booking['departure_terminal']) . ')' : ''; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Return Flight -->
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="bg-blue-50 w-8 h-8 rounded-full flex items-center justify-center text-red-400 flex-shrink-0"><i class="fas fa-plane-arrival text-xs"></i></div>
                                        <div class="flex-grow">
                                            <div class="flex justify-between items-center mb-0.5">
                                                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Return</span>
                                                <span class="text-[10px] font-bold text-red-500"><?php echo !empty($booking['return_date']) ? date('d M Y', strtotime($booking['return_date'])) : 'TBD'; ?></span>
                                            </div>
                                            <p class="font-bold text-gray-800 text-sm leading-tight">
                                                <?php echo !empty($booking['return_flight_name']) ? htmlspecialchars($booking['return_flight_name'] . ' (' . $booking['return_flight_number'] . ')') : 'Airline TBD'; ?>
                                            </p>
                                            <?php if(!empty($booking['return_airport'])): ?>
                                                <p class="text-[10px] text-gray-500 mt-0.5"><i class="fas fa-map-marker-alt text-gray-300"></i> <?php echo htmlspecialchars($booking['return_airport']); ?><?php echo !empty($booking['return_terminal']) ? ' (T' . htmlspecialchars($booking['return_terminal']) . ')' : ''; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ACCOMMODATION -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2"><i class="fas fa-bed"></i> Accommodation Details</h4>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-4 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="bg-green-50 w-8 h-8 rounded-full flex items-center justify-center text-deepGreen flex-shrink-0"><i class="fas fa-kaaba text-xs"></i></div>
                                        <div class="flex-grow"><p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Makkah</p><p class="font-bold text-gray-800 text-sm leading-tight"><?php echo $booking['mecca_hotel']; ?></p></div>
                                        <div class="text-right flex-shrink-0"><p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Room</p><p class="font-bold font-mono text-deepGreen text-sm"><?php echo $booking['mecca_room_no'] ?? 'TBD'; ?></p></div>
                                    </div>
                                    <div class="flex items-start gap-4 bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                        <div class="bg-green-50 w-8 h-8 rounded-full flex items-center justify-center text-deepGreen flex-shrink-0"><i class="fas fa-mosque text-xs"></i></div>
                                        <div class="flex-grow"><p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Madinah</p><p class="font-bold text-gray-800 text-sm leading-tight"><?php echo $booking['medina_hotel']; ?></p></div>
                                        <div class="text-right flex-shrink-0"><p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Room</p><p class="font-bold font-mono text-deepGreen text-sm"><?php echo $booking['medina_room_no'] ?? 'TBD'; ?></p></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- OPTIONAL ADD-ONS SECTION -->
                    <?php if($has_addons && !$is_completed): ?>
                        <div class="bg-indigo-50/30 border-t border-indigo-100 p-6 md:p-8">
                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-plus-square"></i> Optional Trip Add-ons</h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php while($addon = $addons_res->fetch_assoc()): ?>
                                    <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-sm relative overflow-hidden group">
                                        <div class="flex justify-between items-start mb-2">
                                            <h5 class="font-bold text-gray-800"><?php echo htmlspecialchars($addon['item_name']); ?></h5>
                                            <span class="font-mono font-bold text-deepGreen bg-green-50 px-2 py-0.5 rounded text-sm"><?php echo CURRENCY . number_format($addon['cost']); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-4"><?php echo htmlspecialchars($addon['description']); ?></p>
                                        
                                        <!-- Actions based on status -->
                                        <?php if($addon['pip_status'] === 'paid'): ?>
                                            <div class="bg-green-100 text-green-700 px-3 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-2"><i class="fas fa-check-circle"></i> Item Purchased</div>
                                        <?php elseif($addon['pip_status'] === 'verifying'): ?>
                                            <div class="bg-yellow-100 text-yellow-700 px-3 py-2 rounded-lg text-xs font-bold flex items-center justify-center gap-2 animate-pulse"><i class="fas fa-hourglass-half"></i> Verifying Proof...</div>
                                        <?php else: ?>
                                            <?php if($addon['pip_status'] === 'rejected'): ?>
                                                <div class="bg-red-50 text-red-600 text-xs p-2 rounded mb-3 border border-red-100"><i class="fas fa-times-circle"></i> Previous proof rejected: <?php echo htmlspecialchars($addon['admin_note']); ?></div>
                                            <?php endif; ?>
                                            
                                            <div class="flex gap-2">
                                                <!-- Paystack Trigger -->
                                                <button type="button" onclick="payWithPaystack('<?php echo $me['email']; ?>', <?php echo $addon['cost']; ?>, 'verify_payment.php?booking_id=<?php echo $booking['id']; ?>&item_id=<?php echo $addon['id']; ?>')" class="flex-1 bg-deepGreen text-white text-xs font-bold py-2 rounded-lg hover:bg-teal-800 transition shadow">Pay Online</button>
                                                
                                                <!-- Manual Upload Trigger -->
                                                <button type="button" onclick="document.getElementById('proof_upload_<?php echo $addon['id']; ?>').click()" class="flex-1 bg-white border border-gray-300 text-gray-600 text-xs font-bold py-2 rounded-lg hover:bg-gray-50 transition shadow-sm"><i class="fas fa-upload"></i> Upload Proof</button>
                                                
                                                <!-- Hidden Upload Form -->
                                                <form method="POST" enctype="multipart/form-data" class="hidden">
                                                    <input type="hidden" name="upload_proof" value="1">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $addon['id']; ?>">
                                                    <input type="hidden" name="amount" value="<?php echo $addon['cost']; ?>">
                                                    <input type="file" id="proof_upload_<?php echo $addon['id']; ?>" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" onchange="this.form.submit()">
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TRANSACTION LEDGER -->
        <div class="mt-12">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider ml-1 mb-4">Financial Ledger</h3>
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white text-gray-400 uppercase text-[10px] tracking-widest border-b border-gray-100">
                            <tr><th class="px-6 py-4 font-bold">Date & Time</th><th class="px-6 py-4 font-bold">Ref / Type</th><th class="px-6 py-4 font-bold">Amount</th><th class="px-6 py-4 font-bold text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-600">
                            <?php if($pay_hist->num_rows > 0): while($ph = $pay_hist->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="px-6 py-4"><span class="font-medium text-gray-800 block"><?php echo date('d M Y', strtotime($ph['payment_date'])); ?></span><span class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($ph['payment_date'])); ?></span></td>
                                    <td class="px-6 py-4"><p class="font-mono text-xs text-gray-500 mb-0.5"><?php echo $ph['reference_code']; ?></p><span class="text-[9px] uppercase font-bold bg-gray-100 px-2 py-0.5 rounded text-gray-600"><?php echo str_replace('_', ' ', $ph['payment_type']); ?></span></td>
                                    <td class="px-6 py-4 font-bold text-deepGreen text-base"><?php echo CURRENCY . number_format($ph['amount']); ?></td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <a href="receipt.php?ref=<?php echo $ph['reference_code']; ?>" target="_blank" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-deepGreen hover:text-white transition shadow-sm"><i class="fas fa-print"></i></a>
                                        <a href="dispute_transaction.php" class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition shadow-sm"><i class="fas fa-flag"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400">No transactions recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>