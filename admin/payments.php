<?php
// admin/payments.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../index.php"); 
}

// Fetch All Payments with Member Names
$sql = "SELECT p.*, m.full_name, m.passport_photo 
        FROM payments p 
        JOIN members m ON p.member_id = m.id 
        ORDER BY p.payment_date DESC";
$result = $conn->query($sql);

// Calculate Totals
// FIX: Added '?? 0' to prevent null issues if table is empty
$total_rev_res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='success'")->fetch_assoc();
$total_revenue = $total_rev_res['total'] ?? 0;

$month_rev_res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status='success' AND MONTH(payment_date) = MONTH(CURRENT_DATE)")->fetch_assoc();
$month_revenue = $month_rev_res['total'] ?? 0;
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8 flex justify-between items-end">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Financial Ledger</h1>
        <p class="text-gray-600 mt-1">Track all incoming payments and installments.</p>
    </div>
    <div class="flex gap-2">
        <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition">Back to Dashboard</a>
        <button onclick="window.print()" class="bg-white border border-deepGreen text-deepGreen px-4 py-2 rounded shadow hover:bg-gray-50 transition">
            <i class="fas fa-print"></i> Export
        </button>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-deepGreen flex justify-between items-center">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Total Revenue (All Time)</p>
            <h3 class="text-3xl font-bold text-deepGreen"><?php echo CURRENCY . number_format($total_revenue); ?></h3>
        </div>
        <div class="bg-green-50 p-3 rounded-full text-deepGreen">
            <i class="fas fa-landmark fa-2x"></i>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-hajjGold flex justify-between items-center">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase">Revenue (This Month)</p>
            <h3 class="text-3xl font-bold text-hajjGold"><?php echo CURRENCY . number_format($month_revenue); ?></h3>
        </div>
        <div class="bg-yellow-50 p-3 rounded-full text-hajjGold">
            <i class="fas fa-calendar-check fa-2x"></i>
        </div>
    </div>
</div>

<!-- Transaction Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider border-b border-gray-200">
            <tr>
                <th class="p-4">Date</th>
                <th class="p-4">Reference</th>
                <th class="p-4">Pilgrim</th>
                <th class="p-4">Type</th>
                <th class="p-4">Amount</th>
                <th class="p-4 text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition <?php echo $row['status'] == 'failed' ? 'bg-red-50/30' : ''; ?>">
                        <td class="p-4 text-gray-600">
                            <?php echo date('d M Y, h:i A', strtotime($row['payment_date'])); ?>
                        </td>
                        <td class="p-4 font-mono text-xs <?php echo $row['status'] == 'failed' ? 'text-red-400' : 'text-gray-500'; ?>">
                            <?php echo $row['reference_code']; ?>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden flex-shrink-0 border border-gray-200">
                                    <?php if($row['passport_photo']): ?>
                                        <img src="../<?php echo htmlspecialchars($row['passport_photo']); ?>" class="w-full h-full object-cover <?php echo $row['status'] == 'failed' ? 'grayscale' : ''; ?>">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-[10px]"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </div>
                                <span class="font-bold text-gray-700"><?php echo htmlspecialchars($row['full_name']); ?></span>
                            </div>
                        </td>
                        <td class="p-4">
                            <?php if($row['payment_type'] == 'commitment'): ?>
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded font-bold uppercase">Commitment</span>
                            <?php elseif($row['payment_type'] == 'add_on'): ?>
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded font-bold uppercase">Add-On</span>
                            <?php else: ?>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold uppercase">Installment</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 font-bold <?php echo $row['status'] == 'failed' ? 'text-red-500 line-through opacity-70' : 'text-deepGreen'; ?>">
                            <?php echo CURRENCY . number_format($row['amount']); ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if($row['status'] === 'success'): ?>
                                <span class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded font-bold uppercase border border-green-200">
                                    Success
                                </span>
                            <?php elseif($row['status'] === 'failed'): ?>
                                <span class="text-[10px] bg-red-100 text-red-700 px-2 py-1 rounded font-bold uppercase border border-red-200">
                                    Failed
                                </span>
                            <?php else: ?>
                                <span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-1 rounded font-bold uppercase border border-yellow-200">
                                    <?php echo $row['status']; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="p-8 text-center text-gray-500">No transactions recorded.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>