<?php
// verify_payment.php
session_start();
require_once 'config/db.php';
require_once 'config/constants.php';

// --- HELPER: Render Payment Status UI ---
// This replaces silent redirects with a clear, branded success/error screen
function renderOutcome($title, $message, $type, $redirectUrl, $btnText) {
    $icon = $type === 'success' ? '<i class="fas fa-check-circle text-green-500 fa-4x mb-4"></i>' : '<i class="fas fa-exclamation-circle text-red-500 fa-4x mb-4"></i>';
    $textColor = $type === 'success' ? 'text-green-700' : 'text-red-700';
    $bgColor = $type === 'success' ? 'bg-green-50' : 'bg-red-50';
    $borderColor = $type === 'success' ? 'border-green-500' : 'border-red-500';

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title | Abdullateef Hajj</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link href='https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>body { font-family: 'Quicksand', sans-serif; background: #f9fafb; }</style>
    </head>
    <body class='flex items-center justify-center min-h-screen p-4'>
        <div class='bg-white max-w-md w-full rounded-3xl shadow-2xl border-t-8 $borderColor p-10 text-center transform transition-all duration-500 hover:scale-105'>
            $icon
            <h2 class='text-2xl font-bold text-gray-800 mb-3'>$title</h2>
            <div class='$bgColor $textColor p-4 rounded-xl mb-8 text-sm font-bold border border-opacity-50 $borderColor'>
                $message
            </div>
            <a href='$redirectUrl' class='inline-block w-full bg-[#1B7D75] text-white font-bold py-4 rounded-xl hover:bg-teal-800 transition shadow-lg'>
                $btnText
            </a>
        </div>
    </body>
    </html>";
    exit();
}

// 1. Validate Input
if (!isset($_GET['ref'])) {
    renderOutcome("Payment Error", "No transaction reference was provided.", "error", "dashboard.php", "Back to Dashboard");
}

$reference = $_GET['ref'];
$user_id = $_SESSION['user_id']; 

// --- 2. PAYSTACK API SERVER-SIDE VERIFICATION ---
// NOTE: Replace with your actual Paystack Secret Key
$paystack_secret_key = "sk_test_f0d8f7be8f949cdbb21481255abb9ec848fe4831"; 

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ),
    // TEMPORARY FIX FOR LOCALHOST ONLY - BYPASS SSL VERIFICATION
    // IMPORTANT: Remove or set these to 'true' before deploying to a live production server!
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// Handle API Network Error
if ($err) {
    renderOutcome("Verification Failed", "Unable to reach the secure payment gateway. Please check your internet connection.", "error", "dashboard.php", "Return to Dashboard");
}

$tranx = json_decode($response);

// 3. Confirm Successful Transaction via Paystack Response
if (!$tranx->status || $tranx->data->status !== 'success') {
    renderOutcome("Payment Failed", "The transaction was declined or could not be completed successfully.", "error", "dashboard.php", "Return to Dashboard");
}

// 4. Ensure Transaction is Not a Duplicate (Prevents Replay Attacks)
$dup_check = $conn->prepare("SELECT id FROM payments WHERE reference_code = ?");
$dup_check->bind_param("s", $reference);
$dup_check->execute();
if ($dup_check->get_result()->num_rows > 0) {
    renderOutcome("Payment Verified", "This transaction has already been successfully processed and credited to your account.", "success", "dashboard.php", "Go to Dashboard");
}

// Extract Validated Amount (Convert back from Kobo to Naira)
$amount_paid = $tranx->data->amount / 100;

// 5. Determine Payment Routing Context
$new_batch_id = isset($_GET['new_batch_id']) ? intval($_GET['new_batch_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($new_batch_id > 0) {
    // --- SCENARIO A: NEW TRIP BOOKING ---
    
    // ANTI-DUPLICATION CHECK: Ensure the user isn't already booked for this specific batch
    $existing_bk = $conn->query("SELECT id FROM bookings WHERE member_id = '$user_id' AND trip_batch_id = '$new_batch_id' AND booking_status != 'cancelled'");
    
    if ($existing_bk->num_rows > 0) {
        // User already has this trip! Treat this payment as an installment to prevent duplicate bookings.
        $bk_id = $existing_bk->fetch_assoc()['id'];
        
        $conn->query("UPDATE bookings SET amount_paid = amount_paid + $amount_paid WHERE id = '$bk_id'");
        
        $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'installment', 'success')");
        $stmt->bind_param("iisd", $user_id, $bk_id, $reference, $amount_paid);
        $stmt->execute();
        
        renderOutcome("Trip Updated", "You are already registered for this trip. The payment of ₦".number_format($amount_paid)." has been securely added as an installment to your existing booking.", "success", "dashboard.php", "View Dashboard");
    } else {
        // Strict enforcement: Must pay at least 20,000 Naira to add a new trip
        if ($amount_paid >= COMMITMENT_FEE) {
            
            // Fetch batch and package info
            $batch_res = $conn->query("SELECT package_id, start_date FROM trip_batches WHERE id = '$new_batch_id'");
            if ($batch_res->num_rows > 0) {
                $batch = $batch_res->fetch_assoc();
                $pkg_id = $batch['package_id'];
                $travel_date = $batch['start_date'];
                
                // Fetch total cost
                $pkg_res = $conn->query("SELECT total_cost FROM packages WHERE id = '$pkg_id'");
                $total_cost = $pkg_res->fetch_assoc()['total_cost'];
                
                // 1. Create the booking NOW that payment is successful
                $stmt = $conn->prepare("INSERT INTO bookings (member_id, package_id, trip_batch_id, total_due, amount_paid, booking_status, travel_date) VALUES (?, ?, ?, ?, ?, 'confirmed', ?)");
                $stmt->bind_param("iiidds", $user_id, $pkg_id, $new_batch_id, $total_cost, $amount_paid, $travel_date);
                $stmt->execute();
                $new_booking_id = $conn->insert_id;
                
                // 2. Log the payment tied to this new booking
                $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'commitment', 'success')");
                $stmt->bind_param("iisd", $user_id, $new_booking_id, $reference, $amount_paid);
                $stmt->execute();
                
                // 3. Ensure the member is marked as active/committed globally
                $conn->query("UPDATE members SET has_paid_commitment = 1 WHERE id = '$user_id'");
                
                renderOutcome("Booking Confirmed!", "Alhamdulillah! Your payment of ₦".number_format($amount_paid)." was successful. You are now officially registered for this trip.", "success", "dashboard.php", "Go to My Dashboard");
            }
        } else {
            // Error: Tried to pay less than the required 20,000 Naira commitment fee
            renderOutcome("Insufficient Payment", "A minimum deposit of ₦".number_format(COMMITMENT_FEE)." is required to secure a trip booking.", "error", "dashboard.php", "Return to Dashboard");
        }
    }

} elseif ($booking_id > 0) {
    // --- SCENARIO B: TRIP INSTALLMENT PAYMENT (Adding funds to existing trip) ---
    
    // Ensure the booking actually belongs to this user
    $bk_check = $conn->query("SELECT id FROM bookings WHERE id = '$booking_id' AND member_id = '$user_id'");
    
    if ($bk_check->num_rows > 0) {
        // Update the Booking's Paid Balance
        $conn->query("UPDATE bookings SET amount_paid = amount_paid + $amount_paid WHERE id = '$booking_id'");
        
        // Log the Installment
        $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'installment', 'success')");
        $stmt->bind_param("iisd", $user_id, $booking_id, $reference, $amount_paid);
        $stmt->execute();
        
        renderOutcome("Installment Successful", "Jazakumullah Khairan. Your payment of ₦".number_format($amount_paid)." has been credited to your booking.", "success", "dashboard.php", "View Dashboard");
    }

} else {
    // --- SCENARIO C: PURE REGISTRATION COMMITMENT (Legacy Fallback/No Batch Selected Yet) ---
    
    $check_user = $conn->query("SELECT has_paid_commitment FROM members WHERE id = '$user_id'")->fetch_assoc();
    
    if ($check_user['has_paid_commitment'] == 0) {
        if ($amount_paid >= COMMITMENT_FEE) {
            // Update Member Status
            $conn->query("UPDATE members SET has_paid_commitment = 1 WHERE id = '$user_id'");
            
            // Log the Payment
            $stmt = $conn->prepare("INSERT INTO payments (member_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, 'commitment', 'success')");
            $stmt->bind_param("isd", $user_id, $reference, $amount_paid);
            $stmt->execute();
            
            renderOutcome("Commitment Secured", "Your initial deposit of ₦".number_format($amount_paid)." was successful! You can now browse and select your preferred travel package.", "success", "select_package.php", "Select a Package");
        } else {
            renderOutcome("Insufficient Deposit", "The commitment fee requires a minimum of ₦".number_format(COMMITMENT_FEE).".", "error", "dashboard.php", "Return to Dashboard");
        }
    }
}

// Fallback error if routing failed
renderOutcome("System Notice", "Could not determine the appropriate routing for this payment. However, if debited, your funds are safe. Please contact support.", "error", "dashboard.php", "Back to Dashboard");
?>