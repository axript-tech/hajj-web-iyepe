<?php
// admin/announcements.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';
require_once '../includes/mailer.php'; // Required for Broadcasting

// Access Control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) { header("Location: ../index.php"); exit(); }

$msg = '';

// Process New Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $batch_id = intval($_POST['batch_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $message = $conn->real_escape_string($_POST['message']);
    $priority = $_POST['priority'];
    $send_email = isset($_POST['send_email']) ? true : false;

    $b_id_sql = ($batch_id === 0) ? "NULL" : "'$batch_id'";

    $sql = "INSERT INTO announcements (trip_batch_id, title, message, priority) VALUES ($b_id_sql, '$title', '$message', '$priority')";
    
    if ($conn->query($sql)) {
        $msg = "Announcement posted successfully.";
        
        // BROADCAST EMAIL LOGIC
        if ($send_email) {
            $email_query = "";
            if ($batch_id === 0) {
                // Global: Send to all active users
                $email_query = "SELECT email, full_name FROM members WHERE role = 'user' AND status = 'active'";
            } else {
                // Cohort Specific
                $email_query = "SELECT m.email, m.full_name FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.trip_batch_id = '$batch_id' AND b.booking_status = 'confirmed'";
            }
            
            $recipients = $conn->query($email_query);
            $sent_count = 0;
            
            if ($recipients->num_rows > 0) {
                $priority_tag = ($priority === 'urgent') ? "🚨 URGENT: " : "";
                $email_subject = $priority_tag . stripslashes($_POST['title']);
                $email_body = "<strong>Official Update:</strong><br><br>" . nl2br(stripslashes($_POST['message']));
                
                while ($user = $recipients->fetch_assoc()) {
                    if(send_hajj_mail($user['email'], $user['full_name'], $email_subject, $email_body)) {
                        $sent_count++;
                    }
                }
                $msg .= " Broadcasted via email to $sent_count pilgrims.";
            }
        }
    } else {
        $msg = "Error posting: " . $conn->error;
    }
}

// Delete Announcement
// ... (rest of the file remains exactly the same, I just need to add the checkbox to the form) ...
if (isset($_GET['delete_id'])) {
    $del = intval($_GET['delete_id']);
    $conn->query("DELETE FROM announcements WHERE id = '$del'");
    header("Location: announcements.php");
    exit();
}

$batches = $conn->query("SELECT id, batch_name FROM trip_batches WHERE status != 'completed'");
$announcements = $conn->query("
    SELECT a.*, tb.batch_name 
    FROM announcements a 
    LEFT JOIN trip_batches tb ON a.trip_batch_id = tb.id 
    ORDER BY a.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-6xl mx-auto space-y-8">
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-bullhorn text-hajjGold"></i> Communications Hub</h1>
        <p class="text-gray-600 mt-1">Broadcast updates and alerts to pilgrim dashboards and emails.</p>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl border-l-4 shadow-sm bg-green-50 border-green-500 text-green-700">
            <span class="font-bold flex items-center gap-2"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8">
        
        <!-- Post Form -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-deepGreen">
                <h3 class="text-lg font-bold text-gray-800 mb-4">New Broadcast</h3>
                <form method="POST" class="space-y-4">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Target Audience</label>
                        <select name="batch_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50">
                            <option value="0">Global (All Active Pilgrims)</option>
                            <?php while($b = $batches->fetch_assoc()): ?>
                                <option value="<?php echo $b['id']; ?>">Cohort: <?php echo $b['batch_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Title</label>
                        <input type="text" name="title" required placeholder="e.g. Flight Schedule Update" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm font-bold bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Message</label>
                        <textarea name="message" rows="5" required placeholder="Enter announcement details..." class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50"></textarea>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="priority" value="normal" checked class="text-deepGreen">
                            <span class="text-sm font-bold text-gray-600">Normal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="priority" value="urgent" class="text-red-500">
                            <span class="text-sm font-bold text-red-600">Urgent</span>
                        </label>
                    </div>

                    <!-- New Email Toggle -->
                    <div class="bg-gray-100 p-3 rounded-lg border border-gray-200 mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="send_email" value="1" checked class="w-4 h-4 text-deepGreen">
                            <span class="text-sm font-bold text-gray-700">Also deliver via Email</span>
                        </label>
                    </div>

                    <button type="submit" name="post_announcement" class="w-full bg-deepGreen text-white font-bold py-3 rounded-lg hover:bg-teal-800 shadow-md transition flex items-center justify-center gap-2 mt-4">
                        Publish <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- History -->
        <div class="md:col-span-2">
            <div class="space-y-4">
                <?php if ($announcements->num_rows > 0): while($row = $announcements->fetch_assoc()): ?>
                    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 <?php echo ($row['priority'] == 'urgent') ? 'border-red-500' : 'border-blue-500'; ?> group relative overflow-hidden transition hover:shadow-md">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($row['title']); ?></h4>
                                <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                    <i class="fas fa-users text-gray-400 mr-1"></i> <?php echo $row['batch_name'] ? $row['batch_name'] : 'Global Audience'; ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <?php if($row['priority'] == 'urgent'): ?>
                                    <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold uppercase tracking-wider border border-red-200"><i class="fas fa-exclamation-triangle"></i> Urgent</span>
                                <?php endif; ?>
                                <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold"><?php echo date('d M, h:i A', strtotime($row['created_at'])); ?></p>
                            </div>
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