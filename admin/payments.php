<?php
// admin/payments.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control - STRICTLY ADMIN (Managers blocked)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit();
}

// Analytics Queries
$total_revenue_res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success'");
$total_revenue = $total_revenue_res->fetch_assoc()['total'] ?? 0;

$failed_payments_res = $conn->query("SELECT COUNT(id) as failed_count FROM payments WHERE status = 'failed'");
$failed_payments = $failed_payments_res->fetch_assoc()['failed_count'] ?? 0;

$disputes_res = $conn->query("SELECT COUNT(id) as pending_disputes FROM payment_disputes WHERE status = 'pending'");
$pending_disputes = $disputes_res->fetch_assoc()['pending_disputes'] ?? 0;

// --- PAGINATION SETUP ---
$limit = 15; // Number of entries to show in a page.
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total number of records for pagination math
$total_records_query = $conn->query("SELECT COUNT(id) as count FROM payments");
$total_records = $total_records_query->fetch_assoc()['count'];
$total_pages = ceil($total_records / $limit);

// Fetch paginated payments
$sql = "SELECT p.*, m.full_name, m.passport_photo 
        FROM payments p 
        JOIN members m ON p.member_id = m.id 
        ORDER BY p.payment_date DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8 flex justify-between items-end">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Global Ledger</h1>
        <p class="text-gray-600 mt-1">Master view of all financial transactions.</p>
    </div>
    <a href="resolve_disputes.php" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg hover:bg-red-100 transition font-bold shadow-sm flex items-center gap-2">
        <i class="fas fa-flag"></i> Disputes <?php if($pending_disputes > 0) echo "<span class='bg-red-500 text-white rounded-full px-2 py-0.5 text-[10px]'>$pending_disputes</span>"; ?>
    </a>
</div>

<!-- Analytics Dashboard -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-hajjGold">
        <p class="text-xs text-gray-500 uppercase font-bold mb-1">Total Verified Revenue</p>
        <h3 class="text-3xl font-bold text-gray-800"><?php echo CURRENCY . number_format($total_revenue); ?></h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
        <p class="text-xs text-red-500 uppercase font-bold mb-1">Failed Transactions</p>
        <h3 class="text-3xl font-bold text-red-600"><?php echo number_format($failed_payments); ?></h3>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-400">
        <p class="text-xs text-orange-500 uppercase font-bold mb-1">Pending Disputes</p>
        <h3 class="text-3xl font-bold text-orange-600"><?php echo number_format($pending_disputes); ?></h3>
    </div>
</div>

<!-- Transaction List -->
<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
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
                <?php if ($result && $result->num_rows > 0): ?>
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
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="p-4 border-t border-gray-200 bg-gray-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-500">
                Showing <span class="font-bold text-gray-700"><?php echo $total_records > 0 ? $offset + 1 : 0; ?></span> to 
                <span class="font-bold text-gray-700"><?php echo min($offset + $limit, $total_records); ?></span> of 
                <span class="font-bold text-gray-700"><?php echo $total_records; ?></span> entries
            </div>
            
            <div class="flex items-center gap-1">
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition text-xs font-bold shadow-sm">
                        <i class="fas fa-chevron-left mr-1"></i> Prev
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-gray-100 border border-gray-200 text-gray-400 rounded-lg cursor-not-allowed text-xs font-bold">
                        <i class="fas fa-chevron-left mr-1"></i> Prev
                    </span>
                <?php endif; ?>

                <!-- Page Numbers -->
                <div class="hidden sm:flex gap-1 mx-2">
                    <?php 
                    // Show a maximum of 5 page links around the current page
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?page=1" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition text-xs font-bold shadow-sm">1</a>';
                        if ($start_page > 2) echo '<span class="px-2 py-1.5 text-gray-400">...</span>';
                    }

                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="px-3 py-1.5 border rounded-lg transition text-xs font-bold shadow-sm <?php echo $i == $page ? 'bg-deepGreen text-white border-deepGreen' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php 
                    endfor; 

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span class="px-2 py-1.5 text-gray-400">...</span>';
                        echo '<a href="?page=' . $total_pages . '" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition text-xs font-bold shadow-sm">' . $total_pages . '</a>';
                    }
                    ?>
                </div>
                <span class="sm:hidden text-xs text-gray-500 font-bold px-2">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition text-xs font-bold shadow-sm">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-gray-100 border border-gray-200 text-gray-400 rounded-lg cursor-not-allowed text-xs font-bold">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>