<?php
// receipt.php
require_once 'includes/auth_session.php';
require_once 'config/constants.php';

if (!isset($_GET['ref'])) {
    header("Location: dashboard.php");
    exit();
}

$ref = $conn->real_escape_string($_GET['ref']);
$user_id = $_SESSION['user_id'];

// Fetch Payment Details (Securely ensuring it belongs to the logged-in user)
$sql = "SELECT p.*, m.full_name, m.email, m.phone, m.nin, m.passport_number,
               b.id as booking_ref, pkg.name as package_name
        FROM payments p
        JOIN members m ON p.member_id = m.id
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN packages pkg ON b.package_id = pkg.id
        WHERE p.reference_code = '$ref' AND p.member_id = '$user_id'";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Receipt not found or access denied.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $data['id']; ?> | Abdullateef Hajj</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { deepGreen: '#1B7D75', hajjGold: '#C8AA00', black: '#000000' },
                    fontFamily: { sans: ['Quicksand', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { background: #f3f4f6; font-family: 'Quicksand', sans-serif; }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .shadow-lg { box-shadow: none !important; }
            .border { border-color: #000 !important; }
        }
    </style>
</head>
<body class="p-8 flex flex-col items-center min-h-screen">

    <!-- Actions -->
    <div class="no-print w-full max-w-2xl mb-6 flex justify-between">
        <a href="dashboard.php" class="text-gray-500 hover:text-deepGreen font-bold flex items-center gap-2">
            &larr; Back to Dashboard
        </a>
        <button onclick="window.print()" class="bg-deepGreen text-white px-6 py-2 rounded shadow hover:bg-teal-800 font-bold">
            Print Receipt
        </button>
    </div>

    <!-- Receipt Paper -->
    <div class="bg-white w-full max-w-2xl p-10 rounded-xl shadow-lg relative overflow-hidden">
        
        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
            <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" class="w-96 grayscale">
        </div>

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-deepGreen pb-6 mb-6">
            <div class="flex items-center gap-4">
                <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" class="w-16">
                <div>
                    <h1 class="text-2xl font-bold text-deepGreen uppercase tracking-wide">Payment Receipt</h1>
                    <p class="text-xs text-gray-500">Abdullateef Integrated Hajj & Umrah Ltd.</p>
                    <p class="text-xs text-gray-500">Lagos, Nigeria | +234 800 IYEPE HAJJ</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500 uppercase">Receipt No</p>
                <p class="text-xl font-mono font-bold text-black">#<?php echo str_pad($data['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded mt-1 inline-block uppercase border border-green-200">
                    <?php echo $data['status']; ?>
                </p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <h3 class="font-bold text-gray-400 uppercase text-xs mb-1">Billed To</h3>
                <p class="font-bold text-lg text-deepGreen"><?php echo $data['full_name']; ?></p>
                <p class="text-gray-600"><?php echo $data['email']; ?></p>
                <p class="text-gray-600"><?php echo $data['phone']; ?></p>
                <?php if($data['passport_number']): ?>
                    <p class="text-gray-500 text-xs mt-1">Passport: <?php echo $data['passport_number']; ?></p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <h3 class="font-bold text-gray-400 uppercase text-xs mb-1">Payment Details</h3>
                <p class="text-gray-600">Date: <span class="font-bold text-black"><?php echo date('d M Y', strtotime($data['payment_date'])); ?></span></p>
                <p class="text-gray-600">Time: <span class="font-bold text-black"><?php echo date('h:i A', strtotime($data['payment_date'])); ?></span></p>
                <p class="text-gray-600">Ref: <span class="font-mono text-black"><?php echo $data['reference_code']; ?></span></p>
                <p class="text-gray-600 mt-1 capitalize">Type: <?php echo $data['payment_type']; ?></p>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full mb-8 border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-500 uppercase text-xs">
                    <th class="p-3 text-left">Description</th>
                    <th class="p-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200">
                    <td class="p-4">
                        <p class="font-bold text-deepGreen">
                            <?php 
                                if ($data['payment_type'] == 'commitment') {
                                    echo "Commitment Deposit (Hajj/Umrah Registration)";
                                } else {
                                    echo "Installment Payment";
                                    if ($data['package_name']) echo " - " . $data['package_name'];
                                }
                            ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Electronic Funds Transfer / Card Payment</p>
                    </td>
                    <td class="p-4 text-right font-mono font-bold text-lg">
                        <?php echo CURRENCY . number_format($data['amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="p-4 text-right font-bold text-gray-500 uppercase text-xs">Total Paid</td>
                    <td class="p-4 text-right font-bold text-2xl text-deepGreen bg-gray-50">
                        <?php echo CURRENCY . number_format($data['amount'], 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="border-t-2 border-gray-100 pt-6 text-center">
            <p class="text-sm text-gray-600 italic mb-4">
                "May Allah accept your Hajj and Umrah, and verify your transactions in this life and the hereafter."
            </p>
            <div class="flex justify-center items-center gap-2 text-xs text-gray-400 uppercase">
                <span>Authorized Signature</span>
                <div class="w-32 h-px bg-gray-300"></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-4">System Generated Receipt | Abdullateef Hajj Portal</p>
        </div>

    </div>

</body>
</html>