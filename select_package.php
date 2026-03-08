<?php
// select_package.php
require_once 'includes/auth_session.php'; 
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];
$error = '';

// Fetch user email for Paystack gateway
$user_res = $conn->query("SELECT email FROM members WHERE id = '$user_id'");
$user_email = $user_res->fetch_assoc()['email'];

// 1. Fetch User's existing active batch IDs to prevent duplication
$my_bookings_res = $conn->query("SELECT trip_batch_id FROM bookings WHERE member_id = '$user_id' AND booking_status != 'cancelled'");
$my_batches = [];
while($mb = $my_bookings_res->fetch_assoc()) {
    $my_batches[] = $mb['trip_batch_id'];
}

// Fetch Packages (Removed the direct POST handling logic that bypassed payment)
$packages = $conn->query("SELECT * FROM packages");
?>
<?php include 'includes/header.php'; ?>

<div class="container mx-auto">
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold text-deepGreen">Select Your Trip</h2>
        <p class="text-gray-600">Your <?php echo CURRENCY . number_format(COMMITMENT_FEE); ?> deposit will be applied. Please choose a specific travel cohort.</p>
    </div>

    <!-- Error Display -->
    <?php if($error): ?>
        <div class="max-w-2xl mx-auto mb-8 bg-red-100 text-red-700 p-4 rounded-xl border-l-4 border-red-500 shadow-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="font-bold"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8">
        <?php while($pkg = $packages->fetch_assoc()): 
            // Fetch Active Batches for this package
            $batches = $conn->query("SELECT * FROM trip_batches WHERE package_id = '{$pkg['id']}' AND status != 'completed' ORDER BY start_date ASC");
        ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col hover:shadow-2xl transition border border-transparent hover:border-hajjGold">
                <div class="bg-deepGreen text-white p-4">
                    <h3 class="font-bold text-lg"><?php echo $pkg['name']; ?></h3>
                    <div class="text-hajjGold font-bold text-xl mt-1"><?php echo CURRENCY . number_format($pkg['total_cost']); ?></div>
                </div>
                <div class="p-6 flex-grow space-y-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-deepGreen mt-1"></i>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Makkah</p>
                            <p class="font-bold text-black text-sm"><?php echo $pkg['mecca_hotel']; ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-deepGreen mt-1"></i>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-bold">Madinah</p>
                            <p class="font-bold text-black text-sm"><?php echo $pkg['medina_hotel']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 border-t border-gray-100">
                    <form onsubmit="initiateBooking(event, this)">
                        <?php if($batches->num_rows > 0): ?>
                            <div class="mb-3">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Available Dates</label>
                                <select name="batch_id" class="w-full p-2 border rounded text-sm focus:border-deepGreen outline-none" required>
                                    <option value="">-- Choose Date --</option>
                                    <?php 
                                    $has_available_dates = false;
                                    while($b = $batches->fetch_assoc()): 
                                        $is_booked = in_array($b['id'], $my_batches);
                                        if (!$is_booked) $has_available_dates = true;
                                    ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo $is_booked ? 'disabled' : ''; ?>>
                                            <?php echo htmlspecialchars($b['batch_name']) . " (" . date('M d', strtotime($b['start_date'])) . ")"; ?>
                                            <?php echo $is_booked ? ' - ALREADY BOOKED' : ''; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <?php if($has_available_dates): ?>
                                <button type="submit" class="w-full border-2 border-deepGreen text-deepGreen font-bold py-2 rounded hover:bg-deepGreen hover:text-white transition shadow-sm">
                                    Book This Trip
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full bg-gray-200 text-gray-500 font-bold py-2 rounded cursor-not-allowed border border-gray-300">
                                    You've booked all dates
                                </button>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-2">
                                <p class="text-xs text-red-400 font-bold mb-2">Sold Out / No Dates Available</p>
                                <button type="button" disabled class="w-full bg-gray-200 text-gray-400 font-bold py-2 rounded cursor-not-allowed">
                                    Unavailable
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Auto-dismiss error if present and Handle Payment Trigger -->
<script>
    function initiateBooking(event, form) {
        event.preventDefault();
        
        const batchSelect = form.querySelector('select[name="batch_id"]');
        const batchId = batchSelect.value;
        
        if (!batchId) {
            if(typeof AppUI !== 'undefined') AppUI.toast("Please select a specific travel date.", "warning");
            return;
        }

        const email = "<?php echo $user_email; ?>";
        const amount = <?php echo COMMITMENT_FEE; ?>;
        const callbackUrl = "verify_payment.php?new_batch_id=" + batchId;

        // Trigger Paystack Gateway
        if (typeof payWithPaystack === 'function') {
            payWithPaystack(email, amount, callbackUrl);
        } else {
            if(typeof AppUI !== 'undefined') AppUI.toast("Payment gateway is initializing. Please wait...", "warning");
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(typeof AppUI !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.get('msg') === 'commitment_success') {
                AppUI.toast("Commitment Fee Paid. Please select your package.", "success");
                // Clean URL
                window.history.replaceState({}, document.title, "select_package.php");
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>