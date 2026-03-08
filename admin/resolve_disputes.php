<?php
// admin/resolve_disputes.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control - STRICTLY ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

$msg = '';

// Handle Resolution Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_dispute'])) {
    $dispute_id = intval($_POST['dispute_id']);
    $new_status = $_POST['status'];
    $admin_response = $conn->real_escape_string(trim($_POST['admin_response']));

    if (in_array($new_status, ['resolved', 'dismissed'])) {
        $stmt = $conn->prepare("UPDATE payment_disputes SET status = ?, admin_response = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $admin_response, $dispute_id);
        
        if ($stmt->execute()) {
            $msg = "Dispute #$dispute_id has been successfully marked as $new_status.";
        } else {
            $msg = "Error updating dispute: " . $conn->error;
        }
    }
}

// Fetch all disputes (Use LEFT JOIN because payment_id might be NULL for missing claims)
$sql = "
    SELECT pd.*, p.reference_code, p.amount, p.payment_date, m.full_name, m.email, m.phone
    FROM payment_disputes pd
    LEFT JOIN payments p ON pd.payment_id = p.id
    JOIN members m ON pd.member_id = m.id
    ORDER BY FIELD(pd.status, 'pending', 'resolved', 'dismissed'), pd.created_at DESC
";
$disputes = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-6xl mx-auto space-y-8">

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-balance-scale text-hajjGold"></i> Dispute Resolution</h1>
            <p class="text-gray-600 mt-1">Review and resolve pilgrim payment discrepancies.</p>
        </div>
        <a href="payments.php" class="text-sm bg-white border border-gray-300 px-4 py-2 rounded-lg font-bold text-gray-600 hover:bg-gray-50 shadow-sm transition">
            View Ledger
        </a>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl border-l-4 shadow-sm bg-green-50 border-green-500 text-green-700">
            <span class="font-bold flex items-center gap-2"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider border-b border-gray-200">
                <tr>
                    <th class="p-4">Pilgrim Details</th>
                    <th class="p-4">Transaction Info</th>
                    <th class="p-4 w-1/3">Dispute Reason</th>
                    <th class="p-4 text-center">Status</th>
                    <th class="p-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php if ($disputes->num_rows > 0): ?>
                    <?php while($row = $disputes->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition items-start">
                            <td class="p-4 align-top">
                                <p class="font-bold text-deepGreen"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                <p class="text-xs text-gray-500"><i class="fas fa-envelope mr-1"></i> <?php echo htmlspecialchars($row['email']); ?></p>
                                <p class="text-xs text-gray-500"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($row['phone']); ?></p>
                            </td>
                            <td class="p-4 align-top">
                                <?php if($row['payment_id']): ?>
                                    <p class="font-mono text-xs font-bold text-gray-800 bg-gray-100 p-1 inline-block rounded mb-1"><?php echo $row['reference_code']; ?></p>
                                    <p class="font-bold text-gray-800 text-base"><?php echo CURRENCY . number_format($row['amount']); ?></p>
                                    <p class="text-[10px] text-gray-400 mt-1">Paid: <?php echo date('d M Y', strtotime($row['payment_date'])); ?></p>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold uppercase mb-1 inline-block"><i class="fas fa-search-dollar"></i> Unrecorded Debit Claim</span>
                                    <p class="text-xs text-gray-700 font-mono mt-1 bg-gray-100 p-2 rounded leading-snug">
                                        <?php echo str_replace(' | ', '<br>', htmlspecialchars($row['missing_trx_details'])); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top">
                                <div class="text-gray-700 text-sm bg-gray-50 p-3 rounded-lg border border-gray-200 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($row['reason'])); ?>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-2 uppercase font-bold tracking-wider">Filed on <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></p>
                            </td>
                            <td class="p-4 align-top text-center">
                                <?php if($row['status'] === 'pending'): ?>
                                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold uppercase shadow-sm border border-yellow-200 animate-pulse">Needs Review</span>
                                <?php elseif($row['status'] === 'resolved'): ?>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase border border-green-200">Resolved</span>
                                <?php else: ?>
                                    <span class="bg-gray-200 text-gray-600 px-3 py-1 rounded-full text-xs font-bold uppercase border border-gray-300">Dismissed</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 align-top text-center">
                                <?php if($row['status'] === 'pending'): ?>
                                    <button onclick="openResolutionModal(<?php echo $row['id']; ?>)" class="bg-deepGreen text-white px-4 py-2 rounded shadow hover:bg-teal-800 transition text-xs font-bold whitespace-nowrap">
                                        Resolve Now
                                    </button>
                                <?php else: ?>
                                    <button onclick="viewResolution(<?php echo htmlspecialchars(json_encode($row['admin_response'])); ?>)" class="bg-white border border-gray-300 text-gray-600 px-4 py-2 rounded hover:bg-gray-50 transition text-xs font-bold whitespace-nowrap">
                                        View Log
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="p-12 text-center text-gray-400">No disputes recorded in the system.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Resolution Modal -->
<div id="resolution-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300 font-sans">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 transform scale-95 transition-transform duration-300">
        <h3 class="text-xl font-bold text-deepGreen mb-4 flex items-center gap-2 border-b pb-3"><i class="fas fa-gavel"></i> Adjudicate Dispute</h3>
        
        <form method="POST" class="space-y-5">
            <input type="hidden" name="dispute_id" id="modal-dispute-id">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Resolution Action</label>
                <div class="flex gap-4">
                    <label class="flex-1 border border-gray-300 rounded-xl p-3 cursor-pointer hover:border-green-500 hover:bg-green-50 transition relative">
                        <input type="radio" name="status" value="resolved" required class="absolute top-4 right-4">
                        <span class="block font-bold text-green-700 mb-1"><i class="fas fa-check-circle mr-1"></i> Resolve Issue</span>
                        <span class="text-[10px] text-gray-500 leading-tight block">Valid claim. Issue has been fixed or refunded.</span>
                    </label>
                    <label class="flex-1 border border-gray-300 rounded-xl p-3 cursor-pointer hover:border-red-500 hover:bg-red-50 transition relative">
                        <input type="radio" name="status" value="dismissed" required class="absolute top-4 right-4">
                        <span class="block font-bold text-gray-700 mb-1"><i class="fas fa-times-circle mr-1"></i> Dismiss Claim</span>
                        <span class="text-[10px] text-gray-500 leading-tight block">Invalid claim. System logic stands.</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Official Response (Visible to Pilgrim)</label>
                <textarea name="admin_response" rows="4" class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50" placeholder="Provide the official administrative feedback..." required></textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" name="resolve_dispute" class="flex-1 bg-deepGreen text-white font-bold py-3 rounded-xl hover:bg-teal-800 shadow-md transition">Confirm Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('resolution-modal');
    const modalContent = modal.querySelector('div');

    function openResolutionModal(id) {
        document.getElementById('modal-dispute-id').value = id;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Trigger animations
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('modal-dispute-id').value = '';
        }, 300);
    }

    function viewResolution(response) {
        if(typeof AppUI !== 'undefined') {
            AppUI.alert(response || "No notes were provided.", "info");
        } else {
            alert(response);
        }
    }
</script>

<?php include '../includes/footer.php'; ?>