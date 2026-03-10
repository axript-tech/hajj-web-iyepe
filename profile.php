<?php
// profile.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Update Profile Details
    if (isset($_POST['update_profile'])) {
        $phone = $conn->real_escape_string($_POST['phone']);
        $emergency_name = $conn->real_escape_string($_POST['emergency_name']);
        $emergency_phone = $conn->real_escape_string($_POST['emergency_phone']);
        
        // Update Members Table
        $conn->query("UPDATE members SET phone='$phone' WHERE id='$user_id'");
        
        // Update Medical/Emergency Profile
        $check_medical = $conn->query("SELECT id FROM medical_profiles WHERE member_id='$user_id'");
        if ($check_medical->num_rows > 0) {
            $conn->query("UPDATE medical_profiles SET emergency_contact_name='$emergency_name', emergency_contact_phone='$emergency_phone' WHERE member_id='$user_id'");
        } else {
            $conn->query("INSERT INTO medical_profiles (member_id, emergency_contact_name, emergency_contact_phone) VALUES ('$user_id', '$emergency_name', '$emergency_phone')");
        }
        
        $msg = "Profile details updated successfully.";
        $msg_type = "success";
    }

    // 2. Change Password
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        // Verify Current
        $stmt = $conn->prepare("SELECT password_hash FROM members WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_pass, $res['password_hash'])) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 6) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $conn->query("UPDATE members SET password_hash='$new_hash' WHERE id='$user_id'");
                    $msg = "Password changed successfully.";
                    $msg_type = "success";
                } else {
                    $msg = "New password must be at least 6 characters.";
                    $msg_type = "error";
                }
            } else {
                $msg = "New passwords do not match.";
                $msg_type = "error";
            }
        } else {
            $msg = "Current password is incorrect.";
            $msg_type = "error";
        }
    }
}

// Fetch Current Data
$sql = "SELECT m.*, mp.emergency_contact_name, mp.emergency_contact_phone 
        FROM members m 
        LEFT JOIN medical_profiles mp ON m.id = mp.member_id 
        WHERE m.id = '$user_id'";
$user = $conn->query($sql)->fetch_assoc();
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-4xl mx-auto space-y-6 md:space-y-8">
    
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 bg-white p-4 md:p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-deepGreen flex items-center gap-2"><i class="fas fa-user-cog text-hajjGold"></i> Account Settings</h1>
            <p class="text-gray-500 text-sm mt-1">Manage your contact details and security.</p>
        </div>
        <a href="dashboard.php" class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl hover:bg-gray-200 transition font-bold shadow-sm text-sm">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl shadow-sm border-l-4 flex items-center gap-3 <?php echo ($msg_type == 'success') ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'; ?>">
            <i class="fas <?php echo ($msg_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-xl"></i>
            <span class="font-bold text-sm"><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-6 md:gap-8">
        
        <!-- Left: Profile Card (Read Only) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center border-t-4 border-deepGreen sticky top-24">
                <div class="w-24 h-24 sm:w-32 sm:h-32 mx-auto bg-gray-100 rounded-full overflow-hidden border-4 border-white shadow-md mb-4">
                    <?php if($user['passport_photo']): ?>
                        <img src="<?php echo htmlspecialchars($user['passport_photo']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fas fa-user fa-4x"></i></div>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-xl text-deepGreen leading-tight"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-xs sm:text-sm text-gray-500 mb-6 truncate px-2" title="<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="text-left bg-gray-50 p-4 rounded-xl text-sm space-y-3 border border-gray-200 shadow-inner">
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Member ID</span>
                        <span class="font-mono font-bold text-deepGreen">#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-200 pb-2">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Joined</span>
                        <span class="font-bold text-gray-700 text-xs"><?php echo date('d M Y', strtotime($user['registration_date'])); ?></span>
                    </div>
                    <div class="flex flex-col pt-1">
                         <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">NIN</span>
                         <span class="font-mono font-bold text-gray-800 text-xs truncate"><?php echo htmlspecialchars($user['nin'] ?? 'Not Provided'); ?></span>
                    </div>
                    <div class="flex flex-col">
                         <span class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-1">Passport No.</span>
                         <span class="font-mono font-bold text-gray-800 text-xs truncate uppercase"><?php echo htmlspecialchars($user['passport_number'] ?? 'Not Provided'); ?></span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 mt-2">
                        <p class="text-[10px] text-gray-400 italic leading-snug"><i class="fas fa-info-circle text-hajjGold"></i> To change your Name, Photo, NIN, or Passport details, please contact Admin Support.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Edit Forms -->
        <div class="lg:col-span-2 space-y-6 md:space-y-8">
            
            <!-- Contact Info Form -->
            <div class="bg-white rounded-2xl shadow-md p-6 sm:p-8 border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-green-50 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
                
                <h3 class="font-bold text-xl text-gray-800 mb-6 flex items-center gap-2 border-b border-gray-100 pb-3 relative z-10">
                    <i class="fas fa-address-book text-deepGreen"></i> Contact & Emergency
                </h3>
                
                <form method="POST" class="relative z-10 space-y-5">
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5">My Phone Number</label>
                        <div class="relative">
                            <i class="fas fa-phone absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full pl-11 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-deepGreen outline-none transition text-sm bg-gray-50 focus:bg-white" required>
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 p-5 rounded-xl border border-orange-100 space-y-4">
                        <h4 class="text-xs font-bold text-orange-800 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="fas fa-heartbeat"></i> In Case of Emergency</h4>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-orange-700 uppercase mb-1.5">Contact Name</label>
                                <input type="text" name="emergency_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" class="w-full p-3 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition text-sm bg-white" placeholder="Full Name" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-orange-700 uppercase mb-1.5">Contact Phone</label>
                                <input type="text" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" class="w-full p-3 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition text-sm bg-white" placeholder="Phone Number" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-right pt-2">
                        <button type="submit" name="update_profile" class="w-full sm:w-auto bg-deepGreen text-white px-8 py-3.5 rounded-xl font-bold hover:bg-teal-800 transition shadow-md text-sm active:scale-95 flex items-center justify-center gap-2">
                            Save Changes <i class="fas fa-save"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Form -->
            <div class="bg-white rounded-2xl shadow-md p-6 sm:p-8 border border-gray-100 relative overflow-hidden">
                <div class="absolute bottom-0 right-0 w-40 h-40 bg-yellow-50 rounded-full blur-3xl -mr-10 -mb-10 pointer-events-none"></div>
                
                <h3 class="font-bold text-xl text-gray-800 mb-6 flex items-center gap-2 border-b border-gray-100 pb-3 relative z-10">
                    <i class="fas fa-lock text-hajjGold"></i> Security Settings
                </h3>
                
                <form method="POST" class="relative z-10 space-y-5">
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5">Current Password</label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="password" name="current_password" class="w-full pl-11 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-hajjGold outline-none transition text-sm bg-gray-50 focus:bg-white" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="grid sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5">New Password</label>
                            <input type="password" name="new_password" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-hajjGold outline-none transition text-sm bg-gray-50 focus:bg-white" placeholder="Min 6 characters" required>
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-hajjGold outline-none transition text-sm bg-gray-50 focus:bg-white" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    
                    <div class="text-right pt-2">
                        <button type="submit" name="change_password" class="w-full sm:w-auto bg-hajjGold text-white px-8 py-3.5 rounded-xl font-bold hover:bg-yellow-600 transition shadow-md text-sm active:scale-95 flex items-center justify-center gap-2">
                            Update Password <i class="fas fa-shield-alt"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>