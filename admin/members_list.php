<?php
// admin/members_list.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete Action
    if (isset($_POST['delete_id'])) {
        $del_id = intval($_POST['delete_id']);
        if ($del_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
                $msg = "Member record deleted."; $msg_type = "success";
            } else {
                $msg = "Error deleting member."; $msg_type = "error";
            }
        }
    }
    // Toggle Status Action
    if (isset($_POST['toggle_status_id'])) {
        $tid = intval($_POST['toggle_status_id']);
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $conn->query("UPDATE members SET status = '$new_status' WHERE id = '$tid'");
        $msg = "Member status updated to " . ucfirst($new_status) . "."; 
        $msg_type = "success";
    }
}

// Fetch Data
$sql = "SELECT m.*, b.amount_paid as wallet_balance, p.name as package_name, 
               mp.blood_group, mp.genotype, mp.chronic_conditions, mp.mobility_needs
        FROM members m
        LEFT JOIN medical_profiles mp ON m.id = mp.member_id
        LEFT JOIN bookings b ON m.id = b.member_id
        LEFT JOIN packages p ON b.package_id = p.id
        WHERE m.role = 'user'
        ORDER BY m.registration_date DESC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<!-- Refined Header without redundant Dashboard Link -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Pilgrim Records</h1>
        <p class="text-gray-500 text-sm">Total Pilgrims: <span class="font-bold text-black"><?php echo $result->num_rows; ?></span></p>
    </div>
    
    <div class="flex gap-3">
        <a href="create_member.php" class="bg-deepGreen text-white px-5 py-2.5 rounded-lg shadow-md hover:bg-teal-800 transition font-bold flex items-center">
            <i class="fas fa-user-plus mr-2"></i> Add New
        </a>
        <!-- UPDATED PRINT LINK -->
        <a href="print_manifest.php?type=members" target="_blank" class="bg-white border border-gray-300 text-gray-600 px-4 py-2.5 rounded-lg hover:bg-gray-50 transition flex items-center justify-center" title="Print List">
            <i class="fas fa-print"></i>
        </a>
    </div>
</div>

<?php if(isset($msg)): ?>
    <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4 border-green-500 text-green-700 flex items-center">
        <i class="fas fa-check-circle mr-3"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<!-- Members Table (Same logic, cleaner container) -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider border-b border-gray-200">
            <tr>
                <th class="p-4">ID</th>
                <th class="p-4">Pilgrim</th>
                <th class="p-4">Contact</th>
                <th class="p-4">Package</th>
                <th class="p-4 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            <?php while($row = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50 transition group">
                    <td class="p-4 font-mono text-gray-400">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td class="p-4 cursor-pointer" onclick="toggleRow(<?php echo $row['id']; ?>)">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden flex-shrink-0 border border-gray-200">
                                <?php if($row['passport_photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($row['passport_photo']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800 group-hover:text-deepGreen transition"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                <?php if($row['status'] == 'active'): ?>
                                    <span class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold uppercase border border-green-200">Active</span>
                                <?php elseif($row['status'] == 'pending'): ?>
                                    <span class="text-[10px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-bold uppercase border border-yellow-200">Pending</span>
                                <?php else: ?>
                                    <span class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-bold uppercase border border-red-200">Banned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-gray-600">
                        <div class="text-xs"><?php echo $row['email']; ?></div>
                        <div class="font-bold"><?php echo $row['phone']; ?></div>
                    </td>
                    <td class="p-4">
                        <div class="text-xs text-gray-500 uppercase"><?php echo $row['package_name'] ?? 'No Selection'; ?></div>
                        <div class="font-bold text-deepGreen">₦<?php echo number_format($row['wallet_balance'] ?? 0); ?></div>
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Status Toggle -->
                            <?php if($row['status'] == 'pending' || $row['status'] == 'banned'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="toggle_status_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="new_status" value="active">
                                    <button class="p-2 text-green-500 hover:bg-green-50 rounded transition" title="Approve & Mark Active"><i class="fas fa-user-check"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="toggle_status_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="new_status" value="pending">
                                    <button class="p-2 text-yellow-500 hover:bg-yellow-50 rounded transition" title="Revert to Pending"><i class="fas fa-user-clock"></i></button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="member_profile.php?id=<?php echo $row['id']; ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="Edit Profile"><i class="fas fa-pen"></i></a>
                            <form method="POST" onsubmit="return confirm('Delete this record?');" class="inline">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button class="p-2 text-red-400 hover:bg-red-50 rounded transition" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <button onclick="toggleRow(<?php echo $row['id']; ?>)" class="p-2 text-gray-400 hover:text-gray-600 transition"><i class="fas fa-chevron-down" id="icon-<?php echo $row['id']; ?>"></i></button>
                        </div>
                    </td>
                </tr>
                
                <!-- Expansion Row (Simplified for brevity, logic same as before) -->
                <tr id="details-<?php echo $row['id']; ?>" class="hidden bg-gray-50 border-b shadow-inner"><td colspan="5" class="p-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="font-bold text-gray-500">Medical:</span> BG: <?php echo $row['blood_group'] ?? '-'; ?> | Gen: <?php echo $row['genotype'] ?? '-'; ?> | Mobility: <?php echo $row['mobility_needs']; ?></div>
                        <div class="text-right"><a href="member_profile.php?id=<?php echo $row['id']; ?>" class="text-deepGreen font-bold underline">Go to full profile</a></div>
                    </div>
                </td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function toggleRow(id) {
    const row = document.getElementById('details-' + id);
    if(row.classList.contains('hidden')) row.classList.remove('hidden'); else row.classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>