<?php
// admin/manage_roles.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control - STRICTLY SUPER ADMIN (Or just 'admin' based on your current setup)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php"); 
    exit();
}

$msg = '';
$msg_type = '';

// --- HANDLE CREATE NEW STAFF ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check = $conn->query("SELECT id FROM members WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $msg = "A user with this email already exists.";
        $msg_type = "error";
    } else {
        $sql = "INSERT INTO members (full_name, email, phone, password_hash, role, status) 
                VALUES ('$full_name', '$email', '$phone', '$password', '$role', 'active')";
        
        if ($conn->query($sql)) {
            $msg = "New $role account created successfully for $full_name.";
            $msg_type = "success";
        } else {
            $msg = "Error creating account: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// --- HANDLE ROLE UPDATE / REVOKE ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Prevent self-demotion or modifying the main super admin (assuming ID 1 is super admin)
    if ($target_id == $_SESSION['user_id']) {
        $msg = "You cannot modify your own role from this page.";
        $msg_type = "error";
    } elseif ($target_id == 1) {
         $msg = "The primary Super Admin account cannot be modified.";
         $msg_type = "error";
    } else {
        if ($action === 'revoke') {
            // Demote to regular user
            $conn->query("UPDATE members SET role = 'user' WHERE id = '$target_id'");
            $msg = "Admin privileges revoked. User is now a regular pilgrim.";
            $msg_type = "success";
        } elseif ($action === 'make_manager') {
            $conn->query("UPDATE members SET role = 'manager' WHERE id = '$target_id'");
            $msg = "Role updated to Manager.";
            $msg_type = "success";
        } elseif ($action === 'make_admin') {
            $conn->query("UPDATE members SET role = 'admin' WHERE id = '$target_id'");
            $msg = "Role updated to Admin.";
            $msg_type = "success";
        }
    }
}

// Fetch all Admins and Managers
$sql = "SELECT id, full_name, email, phone, role, status, registration_date 
        FROM members 
        WHERE role IN ('admin', 'manager') 
        ORDER BY role ASC, full_name ASC";
$staff_members = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-6xl mx-auto space-y-8">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Role Management</h1>
            <p class="text-gray-600 mt-1">Configure access levels for portal administrators and managers.</p>
        </div>
        <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition font-bold shadow-sm">
            Back to Dashboard
        </a>
    </div>

    <?php if($msg): ?>
        <div class="p-4 rounded-xl border-l-4 shadow-sm flex items-center <?php echo ($msg_type === 'success') ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?>">
            <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
            <span class="font-bold"><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left: Create Staff Form -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-deepGreen sticky top-24">
                <h3 class="font-bold text-lg text-deepGreen mb-4 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i> Add New Staff
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                        <input type="text" name="full_name" required class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
                        <input type="email" name="email" required class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number</label>
                        <input type="text" name="phone" required class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Assign Role</label>
                        <select name="role" required class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-white font-bold">
                            <option value="manager">Manager (Operations Only)</option>
                            <option value="admin">Full Admin (All Access)</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Managers cannot access financial ledgers or this role settings page.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Initial Password</label>
                        <input type="text" name="password" value="Staff@2026" required class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-deepGreen outline-none text-sm bg-gray-50 font-mono">
                    </div>

                    <button type="submit" name="create_staff" class="w-full bg-deepGreen text-white font-bold py-3 rounded-lg hover:bg-teal-800 transition shadow flex items-center justify-center gap-2 mt-4">
                        Create Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Staff Directory -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Staff Directory</h3>
                    <span class="text-xs bg-white border border-gray-200 px-3 py-1 rounded-full font-bold text-gray-600 shadow-sm">
                        <?php echo $staff_members->num_rows; ?> Active Accounts
                    </span>
                </div>
                
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-500 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="p-4">Staff Member</th>
                            <th class="p-4">Contact</th>
                            <th class="p-4">Role</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($staff_members && $staff_members->num_rows > 0): ?>
                            <?php while($staff = $staff_members->fetch_assoc()): 
                                $is_me = ($staff['id'] == $_SESSION['user_id']);
                                $is_super = ($staff['id'] == 1);
                            ?>
                                <tr class="hover:bg-gray-50 transition <?php echo $is_me ? 'bg-green-50/30' : ''; ?>">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 text-gray-500 border border-gray-300">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-deepGreen">
                                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                                    <?php if($is_me) echo '<span class="text-[9px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded ml-1 uppercase">You</span>'; ?>
                                                </p>
                                                <p class="text-[10px] text-gray-400 font-mono mt-0.5">Added: <?php echo date('M Y', strtotime($staff['registration_date'])); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($staff['email']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($staff['phone']); ?></p>
                                    </td>
                                    <td class="p-4">
                                        <?php if($staff['role'] === 'admin'): ?>
                                            <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-purple-200 shadow-sm flex items-center w-fit gap-1">
                                                <i class="fas fa-crown"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-blue-200 shadow-sm flex items-center w-fit gap-1">
                                                <i class="fas fa-tasks"></i> Manager
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <?php if(!$is_me && !$is_super): ?>
                                            <div class="relative group inline-block text-left">
                                                <button class="bg-white border border-gray-300 text-gray-600 px-3 py-1.5 rounded hover:bg-gray-100 transition text-xs font-bold shadow-sm flex items-center gap-1">
                                                    Manage <i class="fas fa-chevron-down text-[10px]"></i>
                                                </button>
                                                <!-- Dropdown Menu -->
                                                <div class="absolute right-0 w-40 mt-1 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg outline-none opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                                    <div class="py-1">
                                                        <?php if($staff['role'] === 'manager'): ?>
                                                            <a href="?action=make_admin&id=<?php echo $staff['id']; ?>" class="text-gray-700 flex justify-between w-full px-4 py-2 text-xs leading-5 hover:bg-gray-50 font-bold">
                                                                Promote to Admin
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=make_manager&id=<?php echo $staff['id']; ?>" class="text-gray-700 flex justify-between w-full px-4 py-2 text-xs leading-5 hover:bg-gray-50 font-bold">
                                                                Demote to Manager
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="py-1">
                                                        <a href="javascript:void(0)" onclick="confirmRevoke(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['full_name']); ?>')" class="text-red-600 flex justify-between w-full px-4 py-2 text-xs leading-5 hover:bg-red-50 font-bold">
                                                            Revoke Access
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="p-8 text-center text-gray-500">No staff members found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<script>
    function confirmRevoke(id, name) {
        if(typeof AppUI !== 'undefined') {
            AppUI.confirm(`Are you sure you want to revoke staff privileges for <br><strong>${name}</strong>?<br><br><span class="text-xs text-red-500 font-normal">They will be demoted to a regular pilgrim account and lose access to this command center.</span>`, () => {
                window.location.href = `manage_roles.php?action=revoke&id=${id}`;
            });
        } else {
            if(confirm(`Revoke admin access for ${name}?`)) {
                window.location.href = `manage_roles.php?action=revoke&id=${id}`;
            }
        }
    }
</script>

<?php include '../includes/footer.php'; ?>