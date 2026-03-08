<?php
// admin/manage_packages.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

$msg = '';
$msg_type = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_package'])) {
    $name = $conn->real_escape_string($_POST['new_name']);
    $cost = floatval($_POST['new_total_cost']);
    $mecca = $conn->real_escape_string($_POST['new_mecca_hotel']);
    $medina = $conn->real_escape_string($_POST['new_medina_hotel']);
    
    $stmt = $conn->prepare("INSERT INTO packages (name, total_cost, mecca_hotel, medina_hotel) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $name, $cost, $mecca, $medina);
    
    if ($stmt->execute()) {
        $msg = "New package created successfully!";
        $msg_type = 'success';
    } else {
        $msg = "Error creating package: " . $conn->error;
        $msg_type = 'error';
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_package'])) {
    $id = intval($_POST['pkg_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $cost = floatval($_POST['total_cost']);
    $mecca = $conn->real_escape_string($_POST['mecca_hotel']);
    $medina = $conn->real_escape_string($_POST['medina_hotel']);
    
    $stmt = $conn->prepare("UPDATE packages SET name=?, total_cost=?, mecca_hotel=?, medina_hotel=? WHERE id=?");
    $stmt->bind_param("sdssi", $name, $cost, $mecca, $medina, $id);
    
    if ($stmt->execute()) {
        $msg = "Package updated successfully!";
        $msg_type = 'success';
    } else {
         $msg = "Error updating package: " . $conn->error;
         $msg_type = 'error';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Ensure we don't delete packages that have active bookings
    $check = $conn->query("SELECT id FROM bookings WHERE package_id = '$del_id'");
    if ($check->num_rows > 0) {
        $msg = "Cannot delete package: There are active bookings tied to it.";
        $msg_type = 'error';
    } else {
        $conn->query("DELETE FROM packages WHERE id = '$del_id'");
        $msg = "Package deleted successfully.";
        $msg_type = 'success';
        // Clean URL after delete
        header("Location: manage_packages.php?msg=deleted");
        exit();
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Package deleted successfully.";
    $msg_type = 'success';
}

$packages = $conn->query("SELECT * FROM packages ORDER BY id DESC");
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-deepGreen">Package Configuration</h1>
    <p class="text-gray-600">Set pricing and accommodation details for Hajj & Umrah offerings.</p>
</div>

<?php if($msg): ?>
    <div class="mb-6 p-4 rounded-xl border-l-4 shadow-sm flex items-center <?php echo ($msg_type === 'success') ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?>">
        <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
        <span class="font-bold"><?php echo $msg; ?></span>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">
    
    <!-- Left Column: Create New Package -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-deepGreen sticky top-24">
            <h3 class="font-bold text-lg text-deepGreen mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Create New Package
            </h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Package Name <span class="text-red-500">*</span></label>
                    <input type="text" name="new_name" placeholder="e.g. VIP Hajj 2027" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm font-bold bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Total Cost (<?php echo CURRENCY; ?>) <span class="text-red-500">*</span></label>
                    <input type="number" name="new_total_cost" placeholder="e.g. 5000000" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm font-mono bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Makkah Hotel <span class="text-red-500">*</span></label>
                    <input type="text" name="new_mecca_hotel" placeholder="e.g. Swissotel Makkah" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Madinah Hotel <span class="text-red-500">*</span></label>
                    <input type="text" name="new_medina_hotel" placeholder="e.g. Anwar Al Madinah" class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50" required>
                </div>
                <button type="submit" name="create_package" class="w-full bg-deepGreen text-white font-bold py-3 rounded-lg hover:bg-teal-800 transition shadow flex items-center justify-center gap-2 mt-2">
                    Save Package <i class="fas fa-save"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Existing Packages -->
    <div class="lg:col-span-2 space-y-6">
        <?php if($packages->num_rows > 0): ?>
            <div class="grid sm:grid-cols-2 gap-6">
                <?php while($pkg = $packages->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-hajjGold hover:shadow-lg transition flex flex-col relative group">
                        
                        <!-- Delete Button (Absolute positioning, appears on hover) -->
                        <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition">
                            <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $pkg['id']; ?>, '<?php echo htmlspecialchars($pkg['name']); ?>')" class="text-red-400 hover:text-red-600 bg-red-50 p-2 rounded-full shadow-sm" title="Delete Package">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>

                        <form method="POST" class="space-y-4 flex-grow flex flex-col">
                            <input type="hidden" name="pkg_id" value="<?php echo $pkg['id']; ?>">
                            
                            <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                                <h3 class="font-bold text-gray-400 text-sm">ID: #<?php echo $pkg['id']; ?></h3>
                                <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded uppercase tracking-wider">Active</span>
                            </div>

                            <div class="flex-grow space-y-4 pt-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Package Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($pkg['name']); ?>" class="w-full p-2 border border-gray-200 rounded font-bold text-deepGreen focus:ring-1 focus:ring-deepGreen outline-none text-sm transition" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Total Cost (<?php echo CURRENCY; ?>)</label>
                                    <input type="number" name="total_cost" value="<?php echo $pkg['total_cost']; ?>" class="w-full p-2 border border-gray-200 rounded font-bold text-gray-800 font-mono focus:ring-1 focus:ring-deepGreen outline-none text-sm transition" required>
                                </div>
                                
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 space-y-3">
                                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-1 mb-2"><i class="fas fa-bed"></i> Accommodations</h4>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Makkah</label>
                                        <input type="text" name="mecca_hotel" value="<?php echo htmlspecialchars($pkg['mecca_hotel']); ?>" class="w-full p-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-deepGreen outline-none bg-white transition" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Madinah</label>
                                        <input type="text" name="medina_hotel" value="<?php echo htmlspecialchars($pkg['medina_hotel']); ?>" class="w-full p-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-deepGreen outline-none bg-white transition" required>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 mt-auto">
                                <button type="submit" name="update_package" class="w-full bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-bold hover:bg-gray-50 hover:text-deepGreen hover:border-deepGreen transition text-sm shadow-sm">
                                    Update Details
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white p-12 rounded-xl border border-dashed border-gray-300 text-center text-gray-500 flex flex-col items-center justify-center h-full">
                <i class="fas fa-box-open fa-3x mb-4 text-gray-300"></i>
                <p class="font-bold">No packages configured.</p>
                <p class="text-sm">Use the form on the left to create your first offering.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        if(typeof AppUI !== 'undefined') {
            AppUI.confirm(`Are you sure you want to delete the package:<br><strong>${name}</strong>?<br><br><span class="text-xs text-red-500 font-normal">This action cannot be undone if there are no linked bookings.</span>`, () => {
                window.location.href = `manage_packages.php?delete=${id}`;
            });
        } else {
            if(confirm(`Delete package: ${name}?`)) {
                window.location.href = `manage_packages.php?delete=${id}`;
            }
        }
    }
</script>

<?php include '../includes/footer.php'; ?>