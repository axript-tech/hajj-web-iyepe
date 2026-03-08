<?php
// admin/announcements.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

// --- HANDLE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $message = $conn->real_escape_string($_POST['message']);
    $batch_id = intval($_POST['batch_id']);
    $priority = $_POST['priority'];
    
    // If batch_id is 0, store as NULL (Global)
    $batch_sql = ($batch_id === 0) ? "NULL" : "'$batch_id'";
    
    $sql = "INSERT INTO announcements (trip_batch_id, title, message, priority) VALUES ($batch_sql, '$title', '$message', '$priority')";
    
    if ($conn->query($sql)) {
        $msg = "Announcement posted successfully.";
        $msg_type = "success";
    } else {
        $msg = "Error: " . $conn->error;
        $msg_type = "error";
    }
}

// DELETE
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM announcements WHERE id = '$id'");
    $msg = "Announcement deleted.";
    $msg_type = "success";
}

// Fetch Batches for Dropdown
$batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed' ORDER BY start_date DESC");

// Fetch History
$history = $conn->query("
    SELECT a.*, tb.batch_name 
    FROM announcements a 
    LEFT JOIN trip_batches tb ON a.trip_batch_id = tb.id 
    ORDER BY a.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-deepGreen">Communication Hub</h1>
    <p class="text-gray-600">Broadcast updates to pilgrims via their dashboard.</p>
</div>

<?php if(isset($msg)): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo ($msg_type == 'success') ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500'; ?>">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">
    
    <!-- Create Form -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-deepGreen">
            <h3 class="font-bold text-lg text-gray-700 mb-4"><i class="fas fa-bullhorn mr-2"></i> New Announcement</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Target Audience</label>
                    <select name="batch_id" class="w-full p-2 border rounded focus:border-deepGreen" required>
                        <option value="0">All Pilgrims (Global)</option>
                        <?php while($b = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $b['id']; ?>">Cohort: <?php echo $b['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Priority</label>
                    <select name="priority" class="w-full p-2 border rounded focus:border-deepGreen">
                        <option value="normal">Normal Information</option>
                        <option value="urgent">Urgent / Alert</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Subject</label>
                    <input type="text" name="title" placeholder="e.g. Flight Schedule Update" class="w-full p-2 border rounded focus:border-deepGreen" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Message</label>
                    <textarea name="message" rows="5" class="w-full p-2 border rounded focus:border-deepGreen" placeholder="Type your message here..." required></textarea>
                </div>
                <button type="submit" name="post_announcement" class="w-full bg-deepGreen text-white font-bold py-2 rounded hover:bg-teal-800 transition">
                    Send Broadcast
                </button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
            <div class="p-4 bg-gray-50 border-b font-bold text-gray-600">Recent Broadcasts</div>
            <div class="divide-y divide-gray-100">
                <?php if($history->num_rows > 0): while($row = $history->fetch_assoc()): ?>
                    <div class="p-4 hover:bg-gray-50 transition group">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="font-bold text-deepGreen text-sm"><?php echo htmlspecialchars($row['title']); ?></h4>
                            <span class="text-xs text-gray-400"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <?php if($row['priority'] == 'urgent'): ?>
                                <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold uppercase">Urgent</span>
                            <?php else: ?>
                                <span class="text-[10px] bg-blue-100 text-blue-600 px-2 py-0.5 rounded font-bold uppercase">Info</span>
                            <?php endif; ?>

                            <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-bold uppercase border ml-1">
                                <?php echo $row['batch_name'] ? "To: " . $row['batch_name'] : "To: Everyone"; ?>
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        
                        <div class="mt-2 text-right opacity-0 group-hover:opacity-100 transition">
                            <a href="javascript:void(0)" class="text-xs text-red-400 hover:text-red-600" onclick="confirmAnnouncementDelete('?delete_id=<?php echo $row['id']; ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="p-8 text-center text-gray-400">No announcements found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
    function confirmAnnouncementDelete(url) {
        if(typeof AppUI !== 'undefined') {
            AppUI.confirm("Are you sure you want to delete this broadcast?<br><span class='text-sm text-gray-500 font-normal'>It will be immediately removed from all pilgrim dashboards.</span>", () => {
                window.location.href = url;
            });
        } else {
            if(confirm("Delete this announcement?")) window.location.href = url;
        }
    }
</script>

<?php include '../includes/footer.php'; ?>