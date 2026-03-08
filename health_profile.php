<?php
// health_profile.php
session_start();
require_once 'config/db.php';

// Force Login check manually (auth_session handles gates, but we are IN a gate here)
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $blood = $_POST['blood_group'];
    $geno = $_POST['genotype'];
    
    // Treat as "None" if left blank
    $raw_conditions = trim($_POST['chronic_conditions']);
    $conditions = empty($raw_conditions) ? 'None' : $conn->real_escape_string($raw_conditions);
    
    $mobility = $_POST['mobility_needs'];
    $emergency_contact_name = $conn->real_escape_string($_POST['emergency_contact_name']);
    $emergency_contact_phone = $conn->real_escape_string($_POST['emergency_contact_phone']);

    // 1. Insert Medical Profile
    $sql = "INSERT INTO medical_profiles (member_id, blood_group, genotype, chronic_conditions, mobility_needs, emergency_contact_name, emergency_contact_phone) 
            VALUES ('$user_id', '$blood', '$geno', '$conditions', '$mobility', '$emergency_contact_name', '$emergency_contact_phone')";
    
    if ($conn->query($sql) === TRUE) {
        // 2. Update Member Status (Unlock Gate 1)
        $conn->query("UPDATE members SET has_completed_health = 1 WHERE id = '$user_id'");
        
        // Redirect to next Gate
        header("Location: make_commitment.php");
        exit();
    } else {
        $error = "Error saving profile: " . $conn->error;
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="flex justify-center">
    <div class="bg-white max-w-2xl w-full rounded-xl shadow-xl overflow-hidden border-t-8 border-deepGreen">
        <div class="p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="bg-red-100 p-3 rounded-full text-red-600"><i class="fas fa-heartbeat fa-2x"></i></div>
                <div>
                    <h2 class="text-2xl font-bold text-deepGreen">Safety First: Medical Profile</h2>
                    <p class="text-gray-500">Mandatory health data for your safety in the Holy Land.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='safety_first'): ?>
                <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4 text-sm font-bold text-center">
                    Please complete your health profile to proceed.
                </div>
            <?php endif; ?>
            
            <form method="POST" class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen outline-none" required>
                        <option value="">Select...</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Genotype</label>
                    <select name="genotype" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen outline-none" required>
                        <option value="">Select...</option>
                        <option value="AA">AA</option>
                        <option value="AS">AS</option>
                        <option value="SS">SS</option>
                        <option value="AC">AC</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <div class="flex justify-between items-end mb-1">
                        <label class="block text-sm font-bold text-gray-700">Chronic Conditions</label>
                        <button type="button" onclick="document.getElementById('chronic_conditions').value='None'" class="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded-full font-bold hover:bg-green-200 transition shadow-sm border border-green-200 flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> Mark as None
                        </button>
                    </div>
                    <textarea id="chronic_conditions" name="chronic_conditions" class="w-full p-3 border rounded bg-gray-50 h-24 focus:border-deepGreen outline-none" placeholder="e.g. Diabetes, Hypertension. Leave blank if none."></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Mobility Needs</label>
                    <select name="mobility_needs" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen outline-none">
                        <option value="None">None - I can walk freely</option>
                        <option value="Wheelchair">Wheelchair Required</option>
                        <option value="Walking Stick">Walking Stick</option>
                    </select>
                </div>
                <div>
                     <label class="block text-sm font-bold text-gray-700 mb-1">Emergency Contact Name</label>
                     <input type="text" name="emergency_contact_name" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen outline-none" required>
                </div>
                <div>
                     <label class="block text-sm font-bold text-gray-700 mb-1">Emergency Contact Phone</label>
                     <input type="text" name="emergency_contact_phone" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen outline-none" required>
                </div>

                <div class="md:col-span-2 pt-4">
                    <button type="submit" class="w-full bg-deepGreen text-white font-bold py-4 rounded hover:bg-teal-800 transition flex items-center justify-center gap-2 shadow-md">
                        Save Health Profile <i class="fas fa-check-circle"></i>
                    </button>
                    <p class="text-xs text-center text-gray-400 mt-2">Data is encrypted and accessible only to medical admins.</p>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>