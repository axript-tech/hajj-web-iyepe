<?php
// admin/members_list.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../index.php"); 
    exit();
}

$msg = '';

// Handle Status Updates (Ban/Activate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $new_status = $_GET['toggle_status'] === 'active' ? 'active' : 'banned';
    
    $stmt = $conn->prepare("UPDATE members SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        $msg = "Member status updated to " . ucfirst($new_status) . ".";
    }
}

// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$where_clauses = ["m.role = 'user'"];

if (!empty($search)) {
    $where_clauses[] = "(m.full_name LIKE '%$search%' OR m.email LIKE '%$search%' OR m.phone LIKE '%$search%' OR m.passport_number LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $where_clauses[] = "m.status = '$status_filter'";
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch all users with filters applied
$sql = "SELECT m.id, m.full_name, m.email, m.phone, m.status, m.registration_date, m.passport_photo, m.passport_number,
               (SELECT COUNT(id) FROM bookings WHERE member_id = m.id) as trips_booked
        FROM members m 
        WHERE $where_sql 
        ORDER BY m.registration_date DESC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold text-deepGreen">Pilgrim Directory</h1>
        <p class="text-gray-600 mt-1">Master list of all registered members.</p>
    </div>
    <div class="flex gap-2">
        <a href="create_member.php" class="bg-deepGreen text-white px-4 py-2 rounded-lg hover:bg-teal-800 transition font-bold shadow-sm flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Add Pilgrim
        </a>
    </div>
</div>

<?php if($msg): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 border-l-4 border-green-500 font-bold">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<!-- Search and Filter Bar -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-center">
        <div class="relative flex-grow w-full">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, phone, or passport..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm transition">
        </div>
        <div class="w-full md:w-auto">
            <select name="status" class="w-full md:w-48 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 text-gray-700">
                <option value="">All Statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
            </select>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="w-full md:w-auto bg-deepGreen text-white px-6 py-2.5 rounded-lg font-bold hover:bg-teal-800 transition shadow-sm text-sm">Filter</button>
            <?php if(!empty($search) || !empty($status_filter)): ?>
                <a href="members_list.php" class="w-full md:w-auto bg-gray-100 text-gray-600 px-4 py-2.5 rounded-lg font-bold hover:bg-gray-200 transition border border-gray-300 text-sm text-center">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-4">Pilgrim</th>
                    <th class="p-4">Contact Info</th>
                    <th class="p-4">Trips</th>
                    <th class="p-4">Joined</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="members-table-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition <?php echo $row['status'] === 'banned' ? 'opacity-60 bg-red-50' : ''; ?>">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden flex-shrink-0 border border-gray-300">
                                        <?php if($row['passport_photo']): ?>
                                            <img src="../<?php echo htmlspecialchars($row['passport_photo']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400"><i class="fas fa-user"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-deepGreen text-base"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] text-gray-400 font-mono mt-0.5">
                                            ID: #<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?>
                                            <?php if(!empty($row['passport_number'])): ?>
                                                <span class="mx-1 text-gray-300">|</span> 
                                                <i class="fas fa-passport opacity-50"></i> <?php echo htmlspecialchars($row['passport_number']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($row['email']); ?></p>
                                <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($row['phone']); ?></p>
                            </td>
                            <td class="p-4 font-bold text-gray-600">
                                <?php echo $row['trips_booked']; ?>
                            </td>
                            <td class="p-4 text-gray-500 text-xs">
                                <?php echo date('M d, Y', strtotime($row['registration_date'])); ?>
                            </td>
                            <td class="p-4">
                                <?php if($row['status'] === 'active'): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border border-green-200">Active</span>
                                <?php elseif($row['status'] === 'pending'): ?>
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border border-yellow-200">Pending</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider border border-red-200">Banned</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="member_profile.php?id=<?php echo $row['id']; ?>" class="bg-white border border-gray-300 text-deepGreen px-3 py-1.5 rounded hover:bg-gray-50 transition text-xs font-bold shadow-sm">
                                        Profile
                                    </a>
                                    <?php if($row['status'] === 'active' || $row['status'] === 'pending'): ?>
                                        <a href="members_list.php?toggle_status=banned&id=<?php echo $row['id']; ?>" class="text-red-500 hover:text-red-700 p-2" title="Ban User" onclick="return confirm('Are you sure you want to ban this user?');">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="members_list.php?toggle_status=active&id=<?php echo $row['id']; ?>" class="text-green-500 hover:text-green-700 p-2" title="Reactivate User">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="p-8 text-center text-gray-500">No matching members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>