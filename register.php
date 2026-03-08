<?php
// register.php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check for duplicate email
    $check_email = $conn->query("SELECT id FROM members WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $error = "An account with this email address already exists. Please log in.";
    }

    // Handle Passport Upload
    $passport_path = NULL;
    if (empty($error) && isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['passport_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create unique name: passport_TIMESTAMP_RAND.jpg
            $new_name = "passport_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $upload_dir = "assets/uploads/passports/";
            
            // Ensure directory exists
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $new_name)) {
                $passport_path = $upload_dir . $new_name;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Only JPG and PNG files are allowed.";
        }
    }

    if (empty($error)) {
        // Insert Member
        $sql = "INSERT INTO members (full_name, email, phone, password_hash, passport_photo) 
                VALUES ('$full_name', '$email', '$phone', '$password', '$passport_path')";
        
        if ($conn->query($sql) === TRUE) {
            $last_id = $conn->insert_id;
            $_SESSION['user_id'] = $last_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['passport'] = $passport_path; // Store in session for immediate UI update
            
            // Redirect to Health Profile (Gate 1)
            header("Location: health_profile.php");
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
    <title>Register | Abdullateef Hajj</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepGreen: '#1B7D75',
                        hajjGold: '#C8AA00',
                        lightGold: '#FFF9C4',
                    },
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-deepGreen min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Decoration -->
    <div class="absolute inset-0 opacity-10 pointer-events-none" 
         style="background-image: radial-gradient(#C8AA00 1px, transparent 1px); background-size: 24px 24px;">
    </div>

    <!-- Updated: max-w-5xl for a wider, more balanced layout -->
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px]">
        
        <!-- Left Side: Visual/Context -->
        <!-- Updated width to w-5/12 and ensuring it stretches -->
        <div class="hidden md:flex md:w-5/12 bg-gray-50 flex-col items-center justify-center p-8 border-r border-gray-100 relative">
            <div class="absolute inset-0 bg-deepGreen opacity-90"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/arabesque.png')] opacity-30"></div>
            
            <div class="relative z-10 text-center text-white">
                <div class="w-20 h-20 bg-hajjGold rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg border-4 border-white transform hover:scale-105 transition duration-500">
                    <span class="text-3xl font-bold">A</span>
                </div>
                <h3 class="text-3xl font-bold mb-3 tracking-tight">Welcome Pilgrim</h3>
                <p class="text-base text-green-100 leading-relaxed max-w-xs mx-auto">
                    Begin your spiritual journey with peace of mind. Your safety and comfort are our priority.
                </p>
                <div class="mt-12 flex justify-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-white opacity-100 shadow-sm"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-white opacity-40 shadow-sm"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-white opacity-40 shadow-sm"></div>
                </div>
            </div>
        </div>

        <!-- Right Side: The Form -->
        <!-- Added: flex flex-col justify-center to vertically center the form -->
        <div class="w-full md:w-7/12 p-8 md:p-12 relative bg-white flex flex-col justify-center">
            
            <div class="text-center md:text-left mb-8">
                <h2 class="text-3xl font-bold text-deepGreen">Create Account</h2>
                <p class="text-gray-400 text-sm mt-1">Join the Abdullateef Hajj & Umrah family</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm relative animate-pulse" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Full Name -->
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400 group-focus-within:text-deepGreen transition-colors"></i>
                    </div>
                    <input type="text" name="full_name" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white" placeholder="Full Name (As on Passport)" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 group-focus-within:text-deepGreen transition-colors"></i>
                        </div>
                        <input type="email" name="email" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white" placeholder="Email Address" required>
                    </div>
                    <!-- Phone -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400 group-focus-within:text-deepGreen transition-colors"></i>
                        </div>
                        <input type="text" name="phone" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white" placeholder="Phone Number" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400 group-focus-within:text-deepGreen transition-colors"></i>
                    </div>
                    <input type="password" name="password" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-deepGreen focus:border-transparent outline-none transition-all bg-gray-50 focus:bg-white" placeholder="Create Password" required>
                </div>

                <!-- Custom Passport Upload -->
                <div class="relative pt-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Passport Photograph</label>
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-green-50 hover:border-deepGreen transition-all group overflow-hidden relative">
                        
                        <div id="upload-placeholder" class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 group-hover:text-deepGreen mb-3 transition-colors transform group-hover:scale-110 duration-300"></i>
                            <p class="text-sm text-gray-500 group-hover:text-deepGreen transition-colors"><span class="font-bold">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-400 mt-1">JPG or PNG (White background)</p>
                        </div>
                        
                        <!-- Image Preview Container -->
                        <img id="passport-preview" class="hidden absolute inset-0 w-full h-full object-cover" alt="Preview">
                        
                        <!-- File Input -->
                        <input type="file" name="passport_photo" accept="image/*" class="hidden" onchange="previewImage(event)" required />
                        
                        <!-- Change Overlay -->
                        <div id="change-overlay" class="hidden absolute inset-0 bg-black bg-opacity-50 items-center justify-center group-hover:flex transition-opacity z-10 backdrop-blur-sm">
                            <span class="text-white text-sm font-bold bg-white/20 px-4 py-2 rounded-full border border-white/50 backdrop-blur-md shadow-lg flex items-center gap-2">
                                <i class="fas fa-sync-alt"></i> Change Photo
                            </span>
                        </div>
                    </label>
                </div>

                <button type="submit" class="w-full bg-deepGreen text-white font-bold py-4 rounded-lg hover:bg-teal-800 transition-all transform hover:-translate-y-1 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 mt-6 text-lg">
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right text-sm"></i>
                </button>
            </form>

            <div class="text-center mt-8 pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-500">
                    Already have an account? 
                    <a href="index.php" class="text-deepGreen font-bold hover:text-hajjGold transition-colors border-b-2 border-transparent hover:border-hajjGold pb-0.5 ml-1">Login here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Image Preview Script -->
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.getElementById('passport-preview');
                const placeholder = document.getElementById('upload-placeholder');
                const overlay = document.getElementById('change-overlay');
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    overlay.classList.remove('hidden');
                }
                
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>