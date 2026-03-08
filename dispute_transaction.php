<?php
// dispute_transaction.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];
$msg = '';
$msg_type = '';

// Handle Dispute Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_dispute'])) {
    $payment_select = $_POST['payment_id'];
    $reason = $conn->real_escape_string(trim($_POST['reason']));

    if ($payment_select === 'missing') {
        // Handle Missing Transaction Claim
        $missing_amt = $conn->real_escape_string($_POST['missing_amount']);
        $missing_date = $conn->real_escape_string($_POST['missing_date']);
        $missing_ref = $conn->real_escape_string($_POST['missing_ref']);
        
        $missing_details = "Amount Debited: ₦" . number_format((float)$missing_amt) . " | Date: $missing_date | Bank/Ref: $missing_ref";
        
        $stmt = $conn->prepare("INSERT INTO payment_disputes (payment_id, member_id, reason, status, missing_trx_details) VALUES (NULL, ?, ?, 'pending', ?)");
        $stmt->bind_param("iss", $user_id, $reason, $missing_details);
        
        if ($stmt->execute()) {
            $msg = "Missing transaction claim submitted. Please hold on while we reconcile with our payment partners.";
            $msg_type = "success";
        } else {
            $msg = "An error occurred while submitting your dispute.";
            $msg_type = "error";
        }

    } else {
        // Handle Standard Registered Transaction Dispute
        $payment_id = intval($payment_select);
        
        // Verify payment belongs to user
        $check_payment = $conn->query("SELECT id FROM payments WHERE id = '$payment_id' AND member_id = '$user_id'");
        
        if ($check_payment->num_rows > 0) {
            // Check if a pending dispute already exists for this transaction
            $check_existing = $conn->query("SELECT id FROM payment_disputes WHERE payment_id = '$payment_id' AND status = 'pending'");
            if ($check_existing->num_rows > 0) {
                $msg = "You already have an active dispute for this transaction.";
                $msg_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO payment_disputes (payment_id, member_id, reason, status) VALUES (?, ?, ?, 'pending')");
                $stmt->bind_param("iis", $payment_id, $user_id, $reason);
                if ($stmt->execute()) {
                    $msg = "Dispute submitted successfully. Our team will review it shortly.";
                    $msg_type = "success";
                } else {
                    $msg = "An error occurred while submitting your dispute.";
                    $msg_type = "error";
                }
            }
        } else {
            $msg = "Invalid transaction selected.";
            $msg_type = "error";
        }
    }
}

// Fetch User's Payments
$payments = $conn->query("SELECT * FROM payments WHERE member_id = '$user_id' ORDER BY payment_date DESC");

// Fetch User's Disputes (LEFT JOIN because payment_id can now be NULL)
$disputes = $conn->query("
    SELECT pd.*, p.reference_code, p.amount, p.payment_date 
    FROM payment_disputes pd 
    LEFT JOIN payments p ON pd.payment_id = p.id 
    WHERE pd.member_id = '$user_id' 
    ORDER BY pd.created_at DESC
");
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-5xl mx-auto space-y-8">
    
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Dispute Center</h1>
            <p class="text-gray-600">Report payment issues and track resolutions.</p>
        </div>
        <a href="dashboard.php" class="text-gray-500 hover:text-deepGreen font-bold flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl border-l-4 shadow-sm <?php echo ($msg_type === 'success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
            <span class="font-bold flex items-center gap-2">
                <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $msg; ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8">
        
        <!-- Open New Dispute Form -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-hajjGold">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-flag text-red-500"></i> Open a Dispute
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Select Transaction</label>
                        <select name="payment_id" id="payment_select" onchange="toggleMissingFields()" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50" required>
                            <option value="">-- Choose Transaction --</option>
                            <option value="missing" class="font-bold text-red-600 bg-red-50">⚠️ Transaction Not Listed (I was debited)</option>
                            <?php 
                            if ($payments->num_rows > 0) {
                                $payments->data_seek(0);
                                while($p = $payments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo date('M d', strtotime($p['payment_date'])); ?> - <?php echo $p['reference_code']; ?> (<?php echo CURRENCY . number_format($p['amount']); ?>)
                                </option>
                            <?php 
                                endwhile; 
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Hidden Fields for Missing Transactions -->
                    <div id="missing-fields" class="hidden bg-orange-50 border border-orange-200 p-4 rounded-lg space-y-3">
                        <p class="text-xs text-orange-800 font-bold mb-2"><i class="fas fa-info-circle"></i> Provide details of the unrecorded debit:</p>
                        <div>
                            <input type="number" name="missing_amount" id="missing_amount" placeholder="Amount Debited (₦)" class="w-full p-2 border border-orange-300 rounded focus:ring-1 focus:ring-orange-500 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-orange-600 uppercase mb-1">Date of Debit</label>
                            <input type="date" name="missing_date" id="missing_date" class="w-full p-2 border border-orange-300 rounded focus:ring-1 focus:ring-orange-500 outline-none text-sm text-gray-600">
                        </div>
                        <div>
                            <input type="text" name="missing_ref" id="missing_ref" placeholder="Bank Name / Session ID / Narration" class="w-full p-2 border border-orange-300 rounded focus:ring-1 focus:ring-orange-500 outline-none text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason for Dispute</label>
                        <textarea name="reason" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50" placeholder="Please explain the issue (e.g., duplicate charge, wrong amount, failed service...)" required></textarea>
                    </div>
                    <button type="submit" name="submit_dispute" class="w-full bg-deepGreen text-white font-bold py-3 rounded-lg hover:bg-teal-800 shadow-md transition">
                        Submit Dispute
                    </button>
                </form>
            </div>
        </div>

        <!-- Dispute History -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="font-bold text-gray-700">Your Dispute History</h3>
                </div>
                
                <div class="p-0">
                    <?php if ($disputes->num_rows > 0): ?>
                        <div class="divide-y divide-gray-100">
                            <?php while($d = $disputes->fetch_assoc()): ?>
                                <div class="p-6 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <?php if ($d['payment_id']): ?>
                                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider block mb-1">Transaction Ref: <?php echo $d['reference_code']; ?></span>
                                                <span class="font-bold text-lg text-gray-800"><?php echo CURRENCY . number_format($d['amount']); ?></span>
                                            <?php else: ?>
                                                <span class="text-xs text-red-500 font-bold uppercase tracking-wider block mb-1">Missing Transaction Claim</span>
                                                <span class="font-mono text-xs text-gray-600 block mt-1"><?php echo htmlspecialchars($d['missing_trx_details']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm text-gray-700 mb-3">
                                        <span class="font-bold block mb-1 text-xs uppercase text-gray-500">Your Complaint:</span>
                                        <?php echo nl2br(htmlspecialchars($d['reason'])); ?>
                                    </div>

                                    <?php if($d['admin_response']): ?>
                                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-900 border-l-4 border-l-blue-500">
                                            <span class="font-bold block mb-1 text-xs uppercase text-blue-500">Admin Response:</span>
                                            <?php echo nl2br(htmlspecialchars($d['admin_response'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-right mt-3 text-[10px] text-gray-400 uppercase font-bold">
                                        Submitted on: <?php echo date('d M Y, h:i A', strtotime($d['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center text-gray-400">
                            <i class="fas fa-shield-alt fa-3x mb-3 opacity-20"></i>
                            <p>No disputes found. Your record is clean.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function toggleMissingFields() {
        const select = document.getElementById('payment_select');
        const missingFields = document.getElementById('missing-fields');
        const amtInput = document.getElementById('missing_amount');
        const dateInput = document.getElementById('missing_date');
        const refInput = document.getElementById('missing_ref');

        if (select.value === 'missing') {
            missingFields.classList.remove('hidden');
            amtInput.setAttribute('required', 'required');
            dateInput.setAttribute('required', 'required');
            refInput.setAttribute('required', 'required');
        } else {
            missingFields.classList.add('hidden');
            amtInput.removeAttribute('required');
            dateInput.removeAttribute('required');
            refInput.removeAttribute('required');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>