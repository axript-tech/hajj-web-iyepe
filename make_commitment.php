<?php
// make_commitment.php
session_start();
require_once 'config/db.php';
require_once 'config/constants.php';

// Force Login
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Simulate Payment Process (In reality, this would be a Paystack Webhook or Callback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_now'])) {
    
    // 1. Record Transaction (Simulated)
    // In production: Only do this after Paystack confirms verification
    $ref = "REF-" . time() . "-" . $user_id;
    $amount = COMMITMENT_FEE;
    
    $sql = "INSERT INTO bookings (member_id, package_id, total_due, amount_paid, booking_status) 
            VALUES (?, NULL, 0, ?, 'confirmed')"; // Temp record, or just track in payments table
            // Actually, based on schema, we should probably just update member status 
            // and maybe have a dedicated payments table. 
            // For now, let's just update the member flag as that's the gate key.

    // Update Gate 2 Status
    $conn->query("UPDATE members SET has_paid_commitment = 1 WHERE id = '$user_id'");
    
    header("Location: dashboard.php");
    exit();
}
?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-center min-h-[60vh] items-center">
    <div class="bg-white max-w-md w-full rounded-xl shadow-2xl overflow-hidden relative border-t-4 border-hajjGold">
        <div class="p-8 text-center">
            <div class="mx-auto w-20 h-20 bg-green-50 rounded-full flex items-center justify-center text-deepGreen mb-4">
                <i class="fas fa-wallet fa-2x"></i>
            </div>
            <h2 class="text-2xl font-bold text-deepGreen mb-2">Commitment Phase</h2>
            <p class="text-black mb-6 text-sm opacity-80">
                To unlock exclusive packages and secure your slot, a refundable deposit of <span class="font-bold text-deepGreen"><?php echo CURRENCY . number_format(COMMITMENT_FEE); ?></span> is required.
            </p>

            <div class="bg-deepGreen text-white p-4 rounded-lg mb-6 shadow-inner">
                <div class="flex justify-between text-sm opacity-80 mb-1">
                    <span>Deposit Amount</span>
                    <span>NGN</span>
                </div>
                <div class="text-3xl font-bold"><?php echo number_format(COMMITMENT_FEE, 2); ?></div>
            </div>

            <form method="POST">
                <button type="submit" name="pay_now" class="w-full bg-hajjGold text-white font-bold py-3 rounded-lg hover:bg-yellow-600 transition transform hover:scale-105 shadow-md">
                    PAY NOW (SECURE)
                </button>
            </form>
            
            <div class="mt-4 text-xs font-bold text-gray-400 flex justify-center gap-2 items-center">
                <i class="fas fa-lock"></i> Secured by Paystack/Flutterwave
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>