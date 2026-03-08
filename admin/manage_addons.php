<?php
// admin/manage_addons.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/mailer.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = '';

// Handle Adding New Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $batch_id = intval($_POST['batch_id']);
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $cost = floatval($_POST['cost']);
    $desc = $conn->real_escape_string($_POST['description']);

    if ($batch_id > 0 && !empty($item_name) && $cost > 0) {
        $stmt = $conn->prepare("INSERT INTO trip_additional_items (trip_batch_id, item_name, cost, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $batch_id, $item_name, $cost, $desc);
        if ($stmt->execute()) $msg = "Add-on created successfully.";
        else $msg = "Error creating add-on.";
    }
}

// Handle Manual Proof Verification (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_proof'])) {
    $pip_id = intval($_POST['pip_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $admin_note = $conn->real_escape_string($_POST['admin_note'] ?? '');

    // Get payment details
    $res = $conn->query("SELECT pip.*, m.email, m.full_name, tai.item_name FROM pilgrim_item_payments pip JOIN members m ON pip.member_id = m.id JOIN trip_additional_items tai ON pip.item_id = tai.id WHERE pip.id = '$pip_id'");
    
    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        
        if ($action === 'approve') {
            $conn->query("UPDATE pilgrim_item_payments SET status = 'paid', admin_note = '$admin_note' WHERE id = '$pip_id'");
            
            // Insert into official payments ledger
            $ref = "MANUAL-" . time() . "-" . $data['member_id'];
            $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'add_on', 'success')");
            $stmt->bind_param("iisd", $data['member_id'], $data['booking_id'], $ref, $data['amount']);
            $stmt->execute();
            
            // Send receipt email
            $msg_body = "Your manual payment proof for the Add-on: <strong>{$data['item_name']}</strong> has been approved.<br>Amount: ₦" . number_format($data['amount']);
            send_hajj_mail($data['email'], $data['full_name'], "Payment Approved - {$data['item_name']}", $msg_body);
            
            $msg = "Proof approved and logged to ledger.";
        } elseif ($action === 'reject') {
            $conn->query("UPDATE pilgrim_item_payments SET status = 'rejected', admin_note = '$admin_note' WHERE id = '$pip_id'");
            
            $msg_body = "Your manual payment proof for the Add-on: <strong>{$data['item_name']}</strong> was rejected.<br>Reason: $admin_note<br>Please try uploading a clearer image or pay online via your dashboard.";
            send_hajj_mail($data['email'], $data['full_name'], "Payment Proof Rejected - {$data['item_name']}", $msg_body);
            
            $msg = "Proof rejected and user notified.";
        }
    }
}

// Fetch Active Batches
$batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed'");

// Current Filter
$filter_batch = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

// Fetch Items
$sql_items = "SELECT tai.*, tb.batch_name, 
              (SELECT COUNT(*) FROM pilgrim_item_payments WHERE item_id = tai.id AND status = 'paid') as paid_count 
              FROM trip_additional_items tai 
              JOIN trip_batches tb ON tai.trip_batch_id = tb.id ";
if ($filter_batch > 0) $sql_items .= " WHERE tai.trip_batch_id = '$filter_batch' ";
$sql_items .= " ORDER BY tai.created_at DESC";
$items = $conn->query($sql_items);

// Fetch Pending Manual Verifications
$sql_pending = "SELECT pip.*, m.full_name, tai.item_name, tb.batch_name 
                FROM pilgrim_item_payments pip 
                JOIN members m ON pip.member_id = m.id 
                JOIN trip_additional_items tai ON pip.item_id = tai.id 
                JOIN trip_batches tb ON tai.trip_batch_id = tb.id 
                WHERE pip.status = 'verifying' ORDER BY pip.created_at ASC";
$pending = $conn->query($sql_pending);
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-deepGreen">Trip Add-ons Manager</h1>
    <p class="text-gray-600">Create optional packages (Qurban, Tourism) and verify manual payment proofs.</p>
</div>

<?php if($msg): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 border-l-4 border-green-500 font-bold"><?php echo $msg; ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">
    
    <!-- LEFT: Add New Item & Pending Verifications -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Add Item Form -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-deepGreen">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-plus-circle text-deepGreen"></i> Create New Add-on</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Target Trip Batch</label>
                    <select name="batch_id" required class="w-full p-2 border rounded focus:border-deepGreen outline-none text-sm">
                        <option value="">Select Batch...</option>
                        <?php $batches->data_seek(0); while($b = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo $b['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Item Name</label>
                    <input type="text" name="item_name" placeholder="e.g., Qurban (Ram), Ziyarah Tour" required class="w-full p-2 border rounded focus:border-deepGreen outline-none font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cost (<?php echo CURRENCY; ?>)</label>
                    <input type="number" name="cost" placeholder="250000" required class="w-full p-2 border rounded focus:border-deepGreen outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description (Optional)</label>
                    <textarea name="description" rows="2" class="w-full p-2 border rounded focus:border-deepGreen outline-none text-sm"></textarea>
                </div>
                <button type="submit" name="add_item" class="w-full bg-deepGreen text-white font-bold py-2 rounded hover:bg-teal-800 transition shadow">
                    Create Item
                </button>
            </form>
        </div>

        <!-- Pending Verifications List -->
        <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-hajjGold">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-search-dollar text-hajjGold"></i> Pending Proofs</h3>
            <div class="space-y-4">
                <?php if($pending->num_rows > 0): while($p = $pending->fetch_assoc()): ?>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm">
                        <p class="font-bold text-deepGreen"><?php echo $p['full_name']; ?></p>
                        <p class="text-xs text-gray-500 mb-2"><?php echo $p['item_name']; ?> - <?php echo CURRENCY . number_format($p['amount']); ?></p>
                        
                        <a href="../<?php echo $p['proof_path']; ?>" target="_blank" class="inline-flex items-center gap-1 text-blue-600 bg-blue-50 px-3 py-1 rounded text-xs font-bold hover:bg-blue-100 transition mb-3">
                            <i class="fas fa-image"></i> View Receipt
                        </a>
                        
                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="pip_id" value="<?php echo $p['id']; ?>">
                            <input type="text" name="admin_note" placeholder="Note (optional)" class="flex-grow p-1.5 border rounded text-xs">
                            <button type="submit" name="action" value="approve" class="bg-green-500 text-white px-3 py-1.5 rounded font-bold hover:bg-green-600" title="Approve"><i class="fas fa-check"></i></button>
                            <button type="submit" name="action" value="reject" class="bg-red-500 text-white px-3 py-1.5 rounded font-bold hover:bg-red-600" title="Reject"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                <?php endwhile; else: ?>
                    <p class="text-gray-400 text-sm text-center py-4 italic">No pending proofs.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: List of Available Add-ons -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Active Add-ons</h3>
                <form method="GET">
                    <select name="batch_id" onchange="this.form.submit()" class="p-1 border rounded text-xs bg-white text-gray-600">
                        <option value="0">All Batches</option>
                        <?php $batches->data_seek(0); while($b = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo ($filter_batch == $b['id']) ? 'selected' : ''; ?>><?php echo $b['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-500 uppercase text-xs">
                    <tr><th class="p-4">Item & Batch</th><th class="p-4">Cost</th><th class="p-4 text-center">Purchases</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if($items->num_rows > 0): while($item = $items->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4">
                                <p class="font-bold text-deepGreen text-base"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                <p class="text-xs text-gray-400 uppercase"><?php echo htmlspecialchars($item['batch_name']); ?></p>
                                <?php if($item['description']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono font-bold text-gray-800">
                                <?php echo CURRENCY . number_format($item['cost']); ?>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold text-xs">
                                    <?php echo $item['paid_count']; ?> Paid
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="3" class="p-8 text-center text-gray-400">No add-ons created yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>