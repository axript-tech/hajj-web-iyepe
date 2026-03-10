<?php
// select_package.php
session_start();
require_once 'config/db.php';
require_once 'config/constants.php';

// 1. Check Login Status
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_res = $conn->query("SELECT email FROM members WHERE id = '$user_id'");
$user_email = $user_res->fetch_assoc()['email'];

// 2. Enforce Business Rule: Check for active trips (In progress)
// If a booking is 'confirmed', the trip is active. Only 'completed' or 'cancelled' allows re-booking.
$active_check = $conn->query("SELECT id FROM bookings WHERE member_id = '$user_id' AND booking_status = 'confirmed'");
$has_active_trip = $active_check->num_rows > 0;

// Fetch Packages
$packages = $conn->query("SELECT * FROM packages");
?>
<?php include 'includes/header.php'; ?>

<div class="max-w-6xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Select Your Package</h1>
            <p class="text-gray-600 mt-1">Choose a spiritual journey that fits your needs.</p>
        </div>
        <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-bold transition shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if($has_active_trip): ?>
        <!-- Active Trip Warning State -->
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 md:p-8 rounded-xl shadow-md flex items-start gap-4">
            <div class="bg-yellow-100 p-3 rounded-full text-yellow-600 flex-shrink-0 mt-1 shadow-sm border border-yellow-200">
                <i class="fas fa-route text-2xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-yellow-800 mb-2">Trip Currently In Progress</h3>
                <p class="text-yellow-700 leading-relaxed mb-5 max-w-3xl">
                    You already have an active booking in progress or awaiting allocation. To ensure proper service delivery and financial accuracy, you can only book a new package once your current journey has been officially marked as <strong>Completed</strong> by the administration.
                </p>
                <a href="dashboard.php" class="inline-flex items-center gap-2 bg-yellow-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-yellow-700 transition shadow-sm">
                    View Current Trip <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Package Grid (Only visible if no active trips) -->
        <div class="grid md:grid-cols-3 gap-8">
            <?php while($pkg = $packages->fetch_assoc()): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col hover:shadow-xl transition-shadow border border-gray-100 relative group">
                    <div class="bg-deepGreen text-white p-6 border-b-4 border-hajjGold">
                        <h3 class="font-bold text-xl mb-1"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                        <div class="text-hajjGold font-extrabold text-3xl mt-2"><?php echo CURRENCY . number_format($pkg['total_cost']); ?></div>
                    </div>
                    <div class="p-6 flex-grow space-y-5 bg-gray-50/50">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-50 border border-green-100 text-deepGreen flex items-center justify-center flex-shrink-0 shadow-sm"><i class="fas fa-kaaba"></i></div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Makkah Hotel</p>
                                <p class="font-bold text-gray-800 text-sm leading-snug"><?php echo htmlspecialchars($pkg['mecca_hotel']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-50 border border-green-100 text-deepGreen flex items-center justify-center flex-shrink-0 shadow-sm"><i class="fas fa-mosque"></i></div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Madinah Hotel</p>
                                <p class="font-bold text-gray-800 text-sm leading-snug"><?php echo htmlspecialchars($pkg['medina_hotel']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-50 border border-yellow-100 text-hajjGold flex items-center justify-center flex-shrink-0 shadow-sm"><i class="fas fa-users"></i></div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Availability</p>
                                <p class="font-bold text-gray-800 text-sm leading-snug"><?php echo intval($pkg['slots_available']); ?> Slots per Cohort</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white border-t border-gray-100">
                        <form onsubmit="initiateBooking(event, this)">
                            <input type="hidden" name="package_id" value="<?php echo $pkg['id']; ?>">
                            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($pkg['name']); ?>">
                            <button type="submit" class="w-full bg-white border-2 border-deepGreen text-deepGreen font-bold py-3 rounded-xl group-hover:bg-deepGreen group-hover:text-white transition-all shadow-sm flex items-center justify-center gap-2">
                                Book This Package <i class="fas fa-chevron-right transform group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Script (Paystack / Simulation) -->
<script>
    function initiateBooking(event, form) {
        event.preventDefault();
        const packageId = form.querySelector('input[name="package_id"]').value;
        const packageName = form.querySelector('input[name="package_name"]').value;
        const email = "<?php echo htmlspecialchars($user_email); ?>";
        const amount = <?php echo COMMITMENT_FEE; ?>;
        const callbackUrl = "verify_payment.php?new_package_id=" + packageId;

        const msg = `You are about to make a commitment deposit of <br><strong class="text-xl text-deepGreen"><?php echo CURRENCY . number_format(COMMITMENT_FEE); ?></strong><br> to secure your spot for <strong>${packageName}</strong>.<br><br><span class="text-xs text-gray-500">Proceed to secure payment gateway?</span>`;

        if (typeof window.AppUI !== 'undefined') {
            window.AppUI.confirm(msg, () => {
                if (typeof payWithPaystack === 'function') {
                    payWithPaystack(email, amount, callbackUrl);
                } else {
                    // Fallback for simulation if Paystack script didn't load
                    window.location.href = callbackUrl + "&ref=SIM-" + Date.now();
                }
            });
        } else {
            // Native fallback
            if (confirm(`Pay deposit of <?php echo CURRENCY . number_format(COMMITMENT_FEE); ?> to book ${packageName}?`)) {
                if (typeof payWithPaystack === 'function') {
                    payWithPaystack(email, amount, callbackUrl);
                } else {
                    window.location.href = callbackUrl + "&ref=SIM-" + Date.now();
                }
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>