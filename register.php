<?php
// register.php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Variables to hold submitted data so the form doesn't empty on error
$submitted_full_name = '';
$submitted_email = '';
$submitted_phone = '';
$submitted_nin = '';
$submitted_passport_number = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_submit'])) {
    // Capture submitted data for re-population
    $submitted_full_name = $_POST['full_name'] ?? '';
    $submitted_email = $_POST['email'] ?? '';
    $submitted_phone = $_POST['phone'] ?? '';
    $submitted_nin = $_POST['nin'] ?? '';
    $submitted_passport_number = $_POST['passport_number'] ?? '';
    
    $full_name = $conn->real_escape_string($submitted_full_name);
    $email = $conn->real_escape_string($submitted_email);
    $phone = $conn->real_escape_string($submitted_phone);
    $nin = $conn->real_escape_string($submitted_nin);
    $passport_number = $conn->real_escape_string($submitted_passport_number);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check for duplicate Email, NIN, or Passport Number
    $check_email = $conn->query("SELECT id FROM members WHERE email = '$email'");
    $check_nin = $conn->query("SELECT id FROM members WHERE nin = '$nin'");
    $check_passport = $conn->query("SELECT id FROM members WHERE passport_number = '$passport_number'");

    if ($check_email->num_rows > 0) {
        $error = "An account with this email address already exists. Please log in.";
    } elseif ($check_nin->num_rows > 0) {
        $error = "This NIN is already registered to another account.";
    } elseif ($check_passport->num_rows > 0) {
        $error = "This Passport Number is already registered to another account.";
    }

    // Process File Uploads (Now Mandatory)
    $passport_photo_path = 'NULL';
    $datapage_path = 'NULL';

    if (empty($error)) {
        // 1. Handle Passport Photo
        if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filename = "photo_" . time() . "_" . rand(1000,9999) . "." . $ext;
                $upload_dir = 'assets/uploads/passports/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $filename)) {
                    $passport_photo_path = "'" . $conn->real_escape_string('assets/uploads/passports/' . $filename) . "'";
                } else {
                    $error = "Failed to save passport photo.";
                }
            } else {
                $error = "Invalid format for Passport Photo. Only JPG and PNG allowed.";
            }
        } else {
            $error = "Passport Photo is required.";
        }

        // 2. Handle Datapage
        if (empty($error)) {
            if (isset($_FILES['passport_datapage']) && $_FILES['passport_datapage']['error'] == 0) {
                $ext = strtolower(pathinfo($_FILES['passport_datapage']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $filename = "datapage_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $upload_dir = 'assets/uploads/passports/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['passport_datapage']['tmp_name'], $upload_dir . $filename)) {
                        $datapage_path = "'" . $conn->real_escape_string('assets/uploads/passports/' . $filename) . "'";
                    } else {
                        $error = "Failed to save International Passport Datapage.";
                    }
                } else {
                    $error = "Invalid format for Datapage. Only JPG, PNG, and PDF allowed.";
                }
            } else {
                $error = "International Passport Datapage is required.";
            }
        }
    }

    // Default status is pending
    if (empty($error)) {
        $sql = "INSERT INTO members (full_name, email, phone, nin, passport_number, password_hash, status, passport_photo, passport_datapage) 
                VALUES ('$full_name', '$email', '$phone', '$nin', '$passport_number', '$password', 'active', $passport_photo_path, $datapage_path)";
        
        if ($conn->query($sql) === TRUE) {
            $member_id = $conn->insert_id;
            // Create empty medical profile
            $conn->query("INSERT INTO medical_profiles (member_id) VALUES ('$member_id')");
            
            // SEND WELCOME EMAIL
            require_once 'includes/mailer.php';
            $msg_body = "Welcome to the Abdullateef Hajj & Umrah Portal. Your account has been created successfully.<br><br>Please log in to your dashboard to complete your <strong>mandatory Medical Profile</strong> and secure your trip commitment.";
            send_hajj_mail($email, $full_name, "Welcome to Abdullateef Hajj & Umrah", $msg_body);

            $_SESSION['user_id'] = $member_id;
            $_SESSION['role'] = 'user';
            
            header("Location: dashboard.php?msg=registered");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Abdullateef Hajj App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00' }, fontFamily: { sans: ['Quicksand', 'sans-serif'] } } }
        }
    </script>
    <style>body { font-family: 'Quicksand', sans-serif; }</style>
</head>
<body class="bg-gray-100 font-sans flex flex-col md:flex-row min-h-screen">
          
    <!-- Mobile Header / Desktop Sidebar -->
    <div class="bg-[#1B7D75] pt-10 pb-16 sm:pb-24 px-4 sm:px-6 md:pb-12 md:w-1/3 lg:w-1/4 md:min-h-screen rounded-b-3xl md:rounded-none md:rounded-r-[3rem] shadow-2xl relative flex flex-col justify-center">
        <div class="absolute top-4 left-4 sm:top-6 sm:left-6 md:top-10 md:left-10">
            <a href="index.php" class="text-white flex items-center gap-2 hover:text-[#C8AA00] transition text-sm sm:text-base">
                <i class="fas fa-arrow-left"></i> <span class="hidden md:inline font-bold">Back</span>
            </a>
        </div>
        
        <div class="mt-6 sm:mt-8 md:mt-0 text-center md:text-left md:px-8">
            <div class="bg-white p-2 rounded-xl shadow-sm w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center mb-4 sm:mb-6 mx-auto md:mx-0">
                <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" alt="Logo" class="w-full object-contain">
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-2 sm:mb-3">Join the Family.</h2>
            <p class="text-green-100 text-xs sm:text-sm md:text-base leading-relaxed">
                Create your account to secure your spot for the upcoming pilgrimage season.
            </p>
        </div>
    </div>

    <!-- Form Area -->
    <div class="px-4 sm:px-6 -mt-8 sm:-mt-16 md:mt-0 flex-grow flex items-center justify-center md:p-12 py-6 sm:py-10">
        <div class="bg-white p-5 sm:p-8 md:p-10 rounded-2xl sm:rounded-3xl shadow-xl w-full max-w-xl border border-gray-100">
            
            <div class="text-center mb-6 sm:mb-8 hidden sm:block">
                <h3 class="text-xl sm:text-2xl font-bold text-gray-800">Register New Account</h3>
                <p class="text-gray-500 mt-1 text-xs sm:text-sm">Enter your details and upload required documents below.</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-xl text-xs sm:text-sm font-bold mb-4 sm:mb-6 text-center border border-red-100 shadow-sm">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-xl text-xs sm:text-sm font-bold mb-4 sm:mb-6 text-center border border-red-100 shadow-sm">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" enctype="multipart/form-data" class="space-y-3 sm:space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Full Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($submitted_full_name); ?>" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="As it appears on passport">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Email Address <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($submitted_email); ?>" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="you@email.com">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Phone Number <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-phone absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($submitted_phone); ?>" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="e.g. 08012345678">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="password" name="password" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">NIN <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-id-card absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="text" name="nin" value="<?php echo htmlspecialchars($submitted_nin); ?>" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="National ID Number">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1">Passport Number <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="fas fa-passport absolute left-3 sm:left-4 top-3 sm:top-4 text-gray-400 text-sm"></i>
                            <input type="text" name="passport_number" value="<?php echo htmlspecialchars($submitted_passport_number); ?>" required class="w-full bg-gray-50 pl-9 sm:pl-12 p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl border border-gray-200 focus:border-[#1B7D75] focus:ring-1 focus:ring-[#1B7D75] outline-none transition text-sm" placeholder="e.g. A12345678">
                        </div>
                    </div>
                </div>

                <!-- Mandatory File Uploads -->
                <div class="border-t border-gray-100 pt-4 mt-2 sm:mt-4">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-800 mb-2 sm:mb-3"><i class="fas fa-file-upload text-[#1B7D75]"></i> Required Documents</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        
                        <!-- Passport Photo -->
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5 sm:mb-2">Passport Photograph <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg sm:rounded-xl p-3 sm:p-4 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer" onclick="document.getElementById('passport_photo').click()">
                                <i class="fas fa-camera text-xl sm:text-2xl text-gray-400 mb-1"></i>
                                <p class="text-[11px] sm:text-xs text-gray-600 font-bold">Select Photo</p>
                                <p class="text-[9px] sm:text-[10px] text-gray-400">JPG, PNG (Max 2MB)</p>
                                <input type="file" id="passport_photo" name="passport_photo" accept="image/jpeg, image/png" required class="hidden" onchange="previewFile(this, 'photo-status')">
                            </div>
                            <p id="photo-status" class="text-[9px] sm:text-[10px] font-bold text-green-600 mt-1 hidden"><i class="fas fa-check-circle"></i> File attached</p>
                        </div>

                        <!-- Datapage -->
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-gray-500 uppercase mb-1.5 sm:mb-2">Int'l Passport Datapage <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg sm:rounded-xl p-3 sm:p-4 text-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer" onclick="document.getElementById('passport_datapage').click()">
                                <i class="fas fa-passport text-xl sm:text-2xl text-gray-400 mb-1"></i>
                                <p class="text-[11px] sm:text-xs text-gray-600 font-bold">Select Datapage</p>
                                <p class="text-[9px] sm:text-[10px] text-gray-400">JPG, PNG, PDF (Max 2MB)</p>
                                <input type="file" id="passport_datapage" name="passport_datapage" accept="image/jpeg, image/png, application/pdf" required class="hidden" onchange="previewFile(this, 'datapage-status')">
                            </div>
                            <p id="datapage-status" class="text-[9px] sm:text-[10px] font-bold text-green-600 mt-1 hidden"><i class="fas fa-check-circle"></i> File attached</p>
                        </div>

                    </div>
                    <?php if($error && empty($_FILES['passport_photo']['name']) && empty($_FILES['passport_datapage']['name'])): ?>
                        <p class="text-xs text-red-500 mt-2 italic"><i class="fas fa-info-circle"></i> Note: You will need to re-select your files if the form reloads with an error.</p>
                    <?php endif; ?>
                </div>

                <button type="submit" name="register_submit" class="w-full bg-[#1B7D75] text-white font-bold py-3 sm:py-4 rounded-lg sm:rounded-xl mt-4 sm:mt-6 shadow-md hover:bg-teal-800 active:scale-95 transition text-sm sm:text-lg">
                    Create Account
                </button>
            </form>

            <div class="mt-6 sm:mt-8 text-center pt-4 sm:pt-6 border-t border-gray-100">
                <p class="text-gray-600 text-xs sm:text-sm">
                    Already registered? 
                    <a href="login.php" class="text-[#C8AA00] font-bold hover:underline">Log in here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function previewFile(input, statusId) {
            const statusEl = document.getElementById(statusId);
            if (input.files && input.files.length > 0) {
                statusEl.classList.remove('hidden');
            } else {
                statusEl.classList.add('hidden');
            }
        }
    </script>
</body>
</html>