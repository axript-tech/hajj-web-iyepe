<?php
// admin/create_member.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { /* header("Location: ../index.php"); */ }

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = !empty($_POST['dob']) ? "'" . $conn->real_escape_string($_POST['dob']) . "'" : "NULL";
    
    // Extracted for duplicate validation
    $nin_val = $conn->real_escape_string($_POST['nin']);
    $passport_val = $conn->real_escape_string($_POST['passport_number']);
    
    $nin = !empty($nin_val) ? "'$nin_val'" : "NULL";
    $passport_number = !empty($passport_val) ? "'$passport_val'" : "NULL";
    $passport_expiry = !empty($_POST['passport_expiry_date']) ? "'" . $conn->real_escape_string($_POST['passport_expiry_date']) . "'" : "NULL";
    
    // Check for duplicate Email, NIN, or Passport Number
    $check_email = $conn->query("SELECT id FROM members WHERE email = '$email'");
    $check_nin = !empty($nin_val) ? $conn->query("SELECT id FROM members WHERE nin = '$nin_val'") : false;
    $check_passport = !empty($passport_val) ? $conn->query("SELECT id FROM members WHERE passport_number = '$passport_val'") : false;

    if ($check_email->num_rows > 0) {
        $error = "A pilgrim with this email address is already registered.";
    } elseif ($check_nin && $check_nin->num_rows > 0) {
        $error = "A pilgrim with this NIN is already registered.";
    } elseif ($check_passport && $check_passport->num_rows > 0) {
        $error = "A pilgrim with this Passport Number is already registered.";
    }

    if (empty($error)) {
        // 1. Passport Photo Upload
        $passport_path = 'NULL';
        if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filename = "photo_" . time() . "_" . rand(1000,9999) . "." . $ext;
                $upload_dir = '../assets/uploads/passports/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $dest = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $dest)) {
                    // Store relative path for database
                    $passport_path = "'" . $conn->real_escape_string('assets/uploads/passports/' . $filename) . "'";
                }
            }
        }

        // 2. Passport Datapage Upload (Optional)
        $datapage_path = 'NULL';
        if (isset($_FILES['passport_datapage']) && $_FILES['passport_datapage']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['passport_datapage']['name'], PATHINFO_EXTENSION));
            // Allow PDFs for datapages
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                $filename = "datapage_" . time() . "_" . rand(1000,9999) . "." . $ext;
                $upload_dir = '../assets/uploads/passports/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $dest = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['passport_datapage']['tmp_name'], $dest)) {
                    $datapage_path = "'" . $conn->real_escape_string('assets/uploads/passports/' . $filename) . "'";
                }
            }
        }

        // Insert
        $sql = "INSERT INTO members (full_name, email, phone, dob, nin, passport_number, passport_expiry_date, password_hash, passport_photo, passport_datapage) 
                VALUES ('$full_name', '$email', '$phone', $dob, $nin, $passport_number, $passport_expiry, '$password', $passport_path, $datapage_path)";
        
        if ($conn->query($sql) === TRUE) {
            // Create Empty Medical Profile to avoid join errors later
            $member_id = $conn->insert_id;
            $conn->query("INSERT INTO medical_profiles (member_id) VALUES ('$member_id')");
            $success = "Member created successfully. ID: #$member_id";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Create New Pilgrim</h1>
            <p class="text-gray-600 mt-1">Manually register a user and setup their core profile.</p>
        </div>
        <a href="members_list.php" class="text-gray-500 hover:text-deepGreen bg-white px-4 py-2 rounded shadow-sm border"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <?php if($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 border-l-4 border-green-500 shadow-sm flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i> <span class="font-bold"><?php echo $success; ?></span>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 border-l-4 border-red-500 shadow-sm flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i> <span class="font-bold"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <!-- Section 1: Core Details -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-deepGreen mb-6 flex items-center border-b pb-2"><i class="fas fa-user mr-2"></i> Personal Information</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 font-bold" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date of Birth</label>
                    <input type="date" name="dob" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50" required>
                </div>
            </div>
        </div>

        <!-- Section 2: Official Documents -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-deepGreen mb-6 flex items-center border-b pb-2"><i class="fas fa-id-card mr-2"></i> Official Identification</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">National ID Number (NIN)</label>
                    <input type="text" name="nin" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 tracking-widest font-mono">
                </div>
                <div class="md:col-span-2"></div> <!-- Spacer -->
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">International Passport Number</label>
                    <input type="text" name="passport_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 tracking-widest font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Passport Expiry Date</label>
                    <input type="date" name="passport_expiry_date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50">
                </div>
                
                <!-- Passport Photo Upload -->
                <div class="md:col-span-1 mt-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Passport Photograph</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer h-full flex flex-col justify-center" onclick="document.getElementById('passport_upload').click()">
                        <i class="fas fa-user-circle text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600 font-bold">Click to upload photo</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG (Max 2MB). Ideal for ID Cards.</p>
                        <input type="file" id="passport_upload" name="passport_photo" accept="image/jpeg, image/png" class="hidden" onchange="previewFileName(this, 'photo-name-display')">
                        <p id="photo-name-display" class="text-xs text-deepGreen font-bold mt-2 hidden"></p>
                    </div>
                </div>

                <!-- Passport Datapage Upload -->
                <div class="md:col-span-1 mt-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Passport Datapage (Optional)</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer h-full flex flex-col justify-center" onclick="document.getElementById('datapage_upload').click()">
                        <i class="fas fa-passport text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600 font-bold">Click to upload Datapage</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, PDF (Max 2MB).</p>
                        <input type="file" id="datapage_upload" name="passport_datapage" accept="image/jpeg, image/png, application/pdf" class="hidden" onchange="previewFileName(this, 'datapage-name-display')">
                        <p id="datapage-name-display" class="text-xs text-deepGreen font-bold mt-2 hidden"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: System Access -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-deepGreen mb-6 flex items-center border-b pb-2"><i class="fas fa-lock mr-2"></i> System Access</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Initial Password <span class="text-red-500">*</span></label>
                    <input type="text" name="password" value="Pilgrim@2026" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen outline-none bg-gray-50 font-mono text-sm" required>
                    <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-info-circle"></i> The user can change this later from their dashboard.</p>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="text-right">
            <button type="submit" class="bg-deepGreen text-white px-10 py-4 rounded-xl font-bold hover:bg-teal-800 transition shadow-lg text-lg flex items-center justify-center gap-2 ml-auto">
                <i class="fas fa-user-plus"></i> Create Pilgrim Account
            </button>
        </div>
    </form>
</div>

<script>
function previewFileName(input, displayId) {
    const display = document.getElementById(displayId);
    if (input.files && input.files[0]) {
        display.textContent = "Selected: " + input.files[0].name;
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
    }
}
</script>

<?php include '../includes/footer.php'; ?>