<?php
// user_medical.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood = $_POST['blood_group'];
    $geno = $_POST['genotype'];
    $mobility = $_POST['mobility_needs'];
    $conditions = $conn->real_escape_string($_POST['chronic_conditions']);
    
    // Check if record exists
    $check = $conn->query("SELECT id FROM medical_profiles WHERE member_id='$user_id'");
    
    if ($check->num_rows > 0) {
        $sql = "UPDATE medical_profiles SET blood_group='$blood', genotype='$geno', mobility_needs='$mobility', chronic_conditions='$conditions' WHERE member_id='$user_id'";
    } else {
        $sql = "INSERT INTO medical_profiles (member_id, blood_group, genotype, mobility_needs, chronic_conditions) VALUES ('$user_id', '$blood', '$geno', '$mobility', '$conditions')";
    }
    
    if ($conn->query($sql)) {
        $msg = "Medical profile updated successfully.";
    } else {
        $msg = "Error updating profile.";
    }
}

// Fetch Current Data
$sql = "SELECT * FROM medical_profiles WHERE member_id = '$user_id'";
$med = $conn->query($sql)->fetch_assoc();
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-3xl mx-auto">
    
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-deepGreen">Medical Profile</h1>
            <p class="text-gray-600">Update your health status for the medical team.</p>
        </div>
        <a href="dashboard.php" class="text-gray-500 hover:text-deepGreen font-bold flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if($msg): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6 border-l-4 border-green-500">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-lg p-8 border-t-4 border-red-500">
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Blood Group</label>
                <select name="blood_group" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen" required>
                    <option value="<?php echo $med['blood_group']; ?>"><?php echo $med['blood_group']; ?></option>
                    <option value="O+">O+</option><option value="O-">O-</option>
                    <option value="A+">A+</option><option value="A-">A-</option>
                    <option value="B+">B+</option><option value="B-">B-</option>
                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Genotype</label>
                <select name="genotype" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen" required>
                    <option value="<?php echo $med['genotype']; ?>"><?php echo $med['genotype']; ?></option>
                    <option value="AA">AA</option><option value="AS">AS</option>
                    <option value="SS">SS</option>
                    <option value="AC">AC</option>
                </select>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Mobility Requirements</label>
            <select name="mobility_needs" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen">
                <option value="None" <?php echo ($med['mobility_needs']=='None')?'selected':''; ?>>None - I can walk freely</option>
                <option value="Wheelchair" <?php echo ($med['mobility_needs']=='Wheelchair')?'selected':''; ?>>Wheelchair Required</option>
                <option value="Walking Stick" <?php echo ($med['mobility_needs']=='Walking Stick')?'selected':''; ?>>Walking Stick</option>
                <option value="Stretcher" <?php echo ($med['mobility_needs']=='Stretcher')?'selected':''; ?>>Stretcher (Severe)</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Chronic Conditions</label>
            <textarea name="chronic_conditions" class="w-full p-3 border rounded bg-gray-50 focus:border-deepGreen h-32" placeholder="e.g. Diabetes, Hypertension, Asthma..."><?php echo htmlspecialchars($med['chronic_conditions']); ?></textarea>
            <p class="text-xs text-gray-500 mt-2">Please keep this updated. It is vital for your safety in the Holy Land.</p>
        </div>

        <div class="text-right">
            <button type="submit" class="bg-deepGreen text-white px-8 py-3 rounded font-bold hover:bg-teal-800 transition shadow">
                Update Health Data
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>