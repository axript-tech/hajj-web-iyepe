<?php
// admin/member_profile.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

if (!isset($_GET['id'])) { header("Location: members_list.php"); exit(); }
$member_id = intval($_GET['id']);

// --- HANDLE UPDATES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $dob = $_POST['dob'];
    $nin = $_POST['nin'];
    
    // Update Member
    $conn->query("UPDATE members SET full_name='$name', email='$email', phone='$phone', dob='$dob', nin='$nin' WHERE id='$member_id'");
    
    // Update Medical (Simplified for demo)
    $blood = $_POST['blood_group'];
    $geno = $_POST['genotype'];
    $mobility = $_POST['mobility_needs'];
    $conn->query("UPDATE medical_profiles SET blood_group='$blood', genotype='$geno', mobility_needs='$mobility' WHERE member_id='$member_id'");
    
    $msg = "Member details updated successfully.";
}

// --- HANDLE OFFLINE BOOKING & PAYMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_offline'])) {
    $batch_id = intval($_POST['batch_id']);
    $amount_paid = floatval($_POST['amount_paid']);
    // Prefix with OFFLINE to easily identify manual inputs in the ledger
    $reference = "OFFLINE-" . $conn->real_escape_string(trim($_POST['reference']));

    // Validate batch
    $batch_res = $conn->query("SELECT package_id, start_date, batch_name FROM trip_batches WHERE id = '$batch_id'");
    if ($batch_res->num_rows > 0) {
        $batch = $batch_res->fetch_assoc();
        $pkg_id = $batch['package_id'];
        $travel_date = $batch['start_date'];

        // Get total package cost
        $pkg_cost = $conn->query("SELECT total_cost FROM packages WHERE id = '$pkg_id'")->fetch_assoc()['total_cost'];

        // Check if user is already booked for this specific batch
        $existing = $conn->query("SELECT id FROM bookings WHERE member_id = '$member_id' AND trip_batch_id = '$batch_id'");
        
        if ($existing->num_rows > 0) {
            // Already booked, so we just add this as an offline installment payment
            $bk_id = $existing->fetch_assoc()['id'];
            $conn->query("UPDATE bookings SET amount_paid = amount_paid + $amount_paid WHERE id = '$bk_id'");
            
            $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'installment', 'success')");
            $stmt->bind_param("iisd", $member_id, $bk_id, $reference, $amount_paid);
            $stmt->execute();
            
            $msg = "Pilgrim is already booked. The amount was successfully added as an installment.";
        } else {
            // New Booking: Enforce minimum commitment fee
            if ($amount_paid >= COMMITMENT_FEE) {
                // 1. Create Booking
                $stmt = $conn->prepare("INSERT INTO bookings (member_id, package_id, trip_batch_id, total_due, amount_paid, booking_status, travel_date) VALUES (?, ?, ?, ?, ?, 'confirmed', ?)");
                $stmt->bind_param("iiidds", $member_id, $pkg_id, $batch_id, $pkg_cost, $amount_paid, $travel_date);
                
                if ($stmt->execute()) {
                    $new_booking_id = $conn->insert_id;
                    
                    // 2. Log Payment
                    $stmt2 = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'commitment', 'success')");
                    $stmt2->bind_param("iisd", $member_id, $new_booking_id, $reference, $amount_paid);
                    $stmt2->execute();
                    
                    // 3. Mark User as Active
                    $conn->query("UPDATE members SET has_paid_commitment = 1 WHERE id = '$member_id'");
                    
                    $msg = "Offline booking assigned successfully!";
                } else {
                    $msg = "Error creating booking records.";
                }
            } else {
                $msg = "The initial payment must meet or exceed the required Commitment Fee (" . CURRENCY . number_format(COMMITMENT_FEE) . ").";
            }
        }
    } else {
        $msg = "Invalid Trip Cohort selected.";
    }
}

// 1. Fetch Member Info
$sql = "SELECT m.*, mp.blood_group, mp.genotype, mp.chronic_conditions, mp.mobility_needs, mp.emergency_contact_name, mp.emergency_contact_phone 
        FROM members m LEFT JOIN medical_profiles mp ON m.id = mp.member_id WHERE m.id = '$member_id'";
$member = $conn->query($sql)->fetch_assoc();

// 2. Fetch Booking
$booking = $conn->query("SELECT b.*, p.name as package_name, p.total_cost FROM bookings b LEFT JOIN packages p ON b.package_id = p.id WHERE b.member_id = '$member_id' ORDER BY b.created_at DESC LIMIT 1")->fetch_assoc();
$total_cost = $booking['total_cost'] ?? 0;
$total_paid = $booking['amount_paid'] ?? 0;
if ($total_paid == 0 && $member['has_paid_commitment']) $total_paid = COMMITMENT_FEE;
$balance = max(0, $total_cost - $total_paid);

// 3. Fetch Payments & Notes
$payments = $conn->query("SELECT * FROM payments WHERE member_id = '$member_id' ORDER BY payment_date DESC");
$notes = $conn->query("SELECT * FROM member_notes WHERE member_id = '$member_id' ORDER BY created_at DESC");

// 4. Fetch Active Batches & User's Existing Batches (For Offline Booking Form)
$active_batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed' ORDER BY start_date ASC");

$my_bookings_res = $conn->query("SELECT trip_batch_id FROM bookings WHERE member_id = '$member_id' AND booking_status != 'cancelled'");
$my_batches = [];
while($mb = $my_bookings_res->fetch_assoc()) {
    if ($mb['trip_batch_id']) $my_batches[] = $mb['trip_batch_id'];
}
?>

<?php include '../includes/header.php'; ?>

<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="members_list.php" class="text-gray-500 hover:text-deepGreen"><i class="fas fa-arrow-left fa-lg"></i></a>
        <h1 class="text-2xl font-bold text-deepGreen">Member Profile: <?php echo htmlspecialchars($member['full_name']); ?></h1>
    </div>
    <?php if(isset($msg)): ?>
        <span class="bg-green-100 text-green-700 px-3 py-2 rounded shadow-sm text-sm font-bold border border-green-200"><?php echo $msg; ?></span>
    <?php endif; ?>
</div>

<div class="grid lg:grid-cols-3 gap-8 mb-8">
    
    <!-- Col 1: Identity (Editable) -->
    <div class="space-y-6">
        <form method="POST">
            <div class="bg-white rounded-xl shadow p-6 border-t-4 border-deepGreen text-center">
                <div class="w-32 h-32 mx-auto bg-gray-100 rounded-full overflow-hidden border-4 border-white shadow-lg mb-4 relative group">
                    <?php if($member['passport_photo']): ?>
                        <img src="../<?php echo htmlspecialchars($member['passport_photo']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fas fa-user fa-4x"></i></div>
                    <?php endif; ?>
                </div>
                
                <div class="text-left space-y-3">
                    <div>
                        <label class="text-xs text-gray-400 uppercase font-bold">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" class="w-full border-b border-gray-300 focus:border-deepGreen outline-none py-1 font-bold text-deepGreen">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase font-bold">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" class="w-full border-b border-gray-300 focus:border-deepGreen outline-none py-1">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase font-bold">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" class="w-full border-b border-gray-300 focus:border-deepGreen outline-none py-1">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-400 uppercase font-bold">DOB</label>
                            <input type="date" name="dob" value="<?php echo $member['dob']; ?>" class="w-full border-b border-gray-300 focus:border-deepGreen outline-none py-1">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 uppercase font-bold">NIN</label>
                            <input type="text" name="nin" value="<?php echo $member['nin']; ?>" class="w-full border-b border-gray-300 focus:border-deepGreen outline-none py-1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical (Editable) -->
            <div class="bg-white rounded-xl shadow p-6 mt-6">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Medical Profile</h3>
                <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                    <div>
                        <label class="block text-gray-400 text-xs uppercase">Blood Group</label>
                        <select name="blood_group" class="w-full border rounded p-1">
                            <option value="<?php echo $member['blood_group']; ?>"><?php echo $member['blood_group']; ?></option>
                            <option value="O+">O+</option><option value="A+">A+</option>
                            <option value="O-">O-</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs uppercase">Genotype</label>
                        <select name="genotype" class="w-full border rounded p-1">
                            <option value="<?php echo $member['genotype']; ?>"><?php echo $member['genotype']; ?></option>
                            <option value="AA">AA</option><option value="AS">AS</option><option value="SS">SS</option><option value="AC">AC</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-400 text-xs uppercase">Mobility</label>
                    <select name="mobility_needs" class="w-full border rounded p-1">
                        <option value="<?php echo $member['mobility_needs']; ?>"><?php echo $member['mobility_needs']; ?></option>
                        <option value="None">None</option>
                        <option value="Wheelchair">Wheelchair</option>
                        <option value="Walking Stick">Walking Stick</option>
                        <option value="Stretcher">Stretcher</option>
                    </select>
                </div>
                <div class="mt-4">
                    <button type="submit" name="update_details" class="w-full bg-deepGreen text-white py-2 rounded font-bold hover:bg-teal-800 transition">Save Changes</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Col 2: Financials & Offline Booking -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Financial Overview -->
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-hajjGold">
            <h3 class="font-bold text-gray-700 mb-4 flex justify-between">
                <span>Current Financial Status</span>
                <span class="text-sm font-normal text-gray-500">Active Package: <strong class="text-black"><?php echo $booking['package_name'] ?? 'Not Selected'; ?></strong></span>
            </h3>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <span class="block text-xs text-gray-500 uppercase">Total Cost</span>
                    <span class="block text-xl font-bold text-gray-800"><?php echo CURRENCY . number_format($total_cost); ?></span>
                </div>
                <div class="bg-green-50 p-4 rounded border border-green-200">
                    <span class="block text-xs text-green-600 uppercase">Total Paid</span>
                    <span class="block text-xl font-bold text-green-700"><?php echo CURRENCY . number_format($total_paid); ?></span>
                </div>
                <div class="bg-red-50 p-4 rounded border border-red-200">
                    <span class="block text-xs text-red-500 uppercase">Balance Due</span>
                    <span class="block text-xl font-bold text-red-600"><?php echo CURRENCY . number_format($balance); ?></span>
                </div>
            </div>
        </div>

        <!-- Offline Booking Form (NEW) -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                <i class="fas fa-hand-holding-usd text-deepGreen"></i> Assign Trip / Offline Payment
            </h3>
            <form method="POST" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Select Trip Cohort</label>
                        <select name="batch_id" class="w-full p-2 border border-gray-300 rounded focus:border-deepGreen focus:ring-1 focus:ring-deepGreen outline-none text-sm bg-gray-50" required>
                            <option value="">-- Choose a Trip --</option>
                            <?php if ($active_batches->num_rows > 0): ?>
                                <?php $active_batches->data_seek(0); while($b = $active_batches->fetch_assoc()): ?>
                                    <?php $is_booked = in_array($b['id'], $my_batches); ?>
                                    <option value="<?php echo $b['id']; ?>">
                                        <?php echo htmlspecialchars($b['batch_name']); ?>
                                        <?php echo $is_booked ? ' - ALREADY BOOKED (Adds as Installment)' : ''; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No active trips available.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Amount Paid (<?php echo CURRENCY; ?>)</label>
                        <input type="number" name="amount_paid" class="w-full p-2 border border-gray-300 rounded focus:border-deepGreen focus:ring-1 focus:ring-deepGreen outline-none font-bold" placeholder="e.g. 500000" min="1000" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bank Reference / Teller ID</label>
                        <input type="text" name="reference" class="w-full p-2 border border-gray-300 rounded focus:border-deepGreen focus:ring-1 focus:ring-deepGreen outline-none" placeholder="e.g. GTB-123456" required>
                    </div>
                </div>
                <div class="text-right pt-2">
                    <button type="submit" name="book_offline" class="bg-deepGreen text-white px-6 py-2.5 rounded font-bold hover:bg-teal-800 transition shadow-md text-sm">
                        Process Offline Payment
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-2"><i class="fas fa-info-circle"></i> If the pilgrim is already assigned to the selected trip, this will automatically be added as an installment payment.</p>
            </form>
        </div>

        <!-- Ledger History -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 flex justify-between items-center">
                <span>Transaction Ledger</span>
                <span class="text-xs bg-white px-2 py-1 rounded shadow-sm border text-gray-500">Records: <?php echo $payments->num_rows; ?></span>
            </div>
            <div class="max-h-60 overflow-y-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-gray-400 uppercase text-[10px] sticky top-0 shadow-sm">
                        <tr><th class="p-3">Date</th><th class="p-3">Ref</th><th class="p-3">Amount</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if($payments->num_rows > 0): while($pay = $payments->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 text-gray-600"><?php echo date('d M Y', strtotime($pay['payment_date'])); ?></td>
                                <td class="p-3 font-mono text-[11px] text-gray-500"><?php echo $pay['reference_code']; ?></td>
                                <td class="p-3 font-bold text-deepGreen"><?php echo CURRENCY . number_format($pay['amount']); ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" class="p-4 text-center text-gray-400 italic text-xs">No payments recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Admin Notes Section -->
        <div class="bg-white rounded-xl shadow p-6">
            <h4 class="font-bold text-gray-700 mb-4 border-b pb-2">Internal Admin Notes</h4>
            <div class="space-y-3 mb-4 max-h-40 overflow-y-auto">
                <?php if($notes->num_rows > 0): while($note = $notes->fetch_assoc()): ?>
                    <div class="border-l-2 border-hajjGold pl-3 text-sm bg-gray-50 p-2 rounded-r">
                        <p class="text-gray-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                        <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-wider"><?php echo date('d M Y, h:i A', strtotime($note['created_at'])); ?> by <?php echo $note['created_by']; ?></p>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-gray-400 text-xs italic">No notes added.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>