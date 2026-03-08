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

<div class="max-w-4xl mx-auto">
    
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Account Settings</h1>
            <p class="text-gray-600">Manage your contact details and security.</p>
        </div>
        <a href="dashboard.php" class="text-gray-500 hover:text-deepGreen font-bold flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if($msg): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo ($msg_type == 'success') ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500'; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8">
        
        <!-- Left: Profile Card (Read Only) -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-deepGreen">
                <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full overflow-hidden border-4 border-white shadow mb-4">
                    <?php if($user['passport_photo']): ?>
                        <img src="<?php echo htmlspecialchars($user['passport_photo']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fas fa-user fa-3x"></i></div>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-lg text-deepGreen"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-xs text-gray-500 mb-4"><?php echo $user['email']; ?></p>
                
                <div class="text-left bg-gray-50 p-4 rounded text-sm space-y-2 border border-gray-100">
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">Member ID</span>
                        <span class="font-mono font-bold">#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 uppercase">Registration Date</span>
                        <span><?php echo date('d M Y', strtotime($user['registration_date'])); ?></span>
                    </div>
                    <div class="pt-2 border-t border-gray-200 mt-2">
                        <p class="text-xs text-gray-400 italic">To change your Name or Passport Photo, please contact support.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Edit Forms -->
        <div class="md:col-span-2 space-y-6">
            
            <!-- Contact Info Form -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-address-book mr-2 text-deepGreen"></i> Contact & Emergency</h3>
                <form method="POST">
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">My Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Emergency Contact Name</label>
                            <input type="text" name="emergency_name" value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Emergency Contact Phone</label>
                            <input type="text" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" name="update_profile" class="bg-deepGreen text-white px-6 py-2 rounded font-bold hover:bg-teal-800 transition text-sm">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Form -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-lock mr-2 text-hajjGold"></i> Security</h3>
                <form method="POST">
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Password</label>
                            <input type="password" name="current_password" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
                                <input type="password" name="new_password" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="w-full p-2 border rounded focus:border-deepGreen outline-none" required>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" name="change_password" class="bg-hajjGold text-white px-6 py-2 rounded font-bold hover:bg-yellow-600 transition text-sm">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>