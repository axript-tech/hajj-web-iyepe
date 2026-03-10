<?php
// verify_payment.php
session_start();
require_once 'config/db.php';
require_once 'config/constants.php';
require_once 'includes/mailer.php';

// Helper to render the outcome page consistently
function renderOutcome($title, $message, $type, $btn_link, $btn_text) {
    include 'includes/header.php';
    $color = $type === 'success' ? 'text-green-600' : ($type === 'info' ? 'text-blue-600' : 'text-red-600');
    $bg = $type === 'success' ? 'bg-green-50 border-green-200' : ($type === 'info' ? 'bg-blue-50 border-blue-200' : 'bg-red-50 border-red-200');
    $icon = $type === 'success' ? 'fa-check-circle' : ($type === 'info' ? 'fa-info-circle' : 'fa-times-circle');
    
    echo "
    <div class='flex items-center justify-center min-h-[60vh]'>
        <div class='bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center border-t-4 border-gray-200'>
            <div class='w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-6 $bg'>
                <i class='fas $icon text-4xl $color'></i>
            </div>
            <h2 class='text-2xl font-bold text-gray-800 mb-2'>$title</h2>
            <p class='text-gray-600 mb-8'>$message</p>
            <a href='$btn_link' class='block w-full bg-deepGreen text-white font-bold py-3 rounded-xl hover:bg-teal-800 transition shadow-md'>$btn_text</a>
        </div>
    </div>";
    include 'includes/footer.php';
    exit();
}

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

if (!isset($_GET['ref'])) {
    renderOutcome("Payment Error", "No transaction reference was provided.", "error", "dashboard.php", "Back to Dashboard");
}

$reference = $conn->real_escape_string($_GET['ref']);
$user_id = $_SESSION['user_id'];

// 1. Duplicate Reference Check (Crucial Security Fix)
$dup_check = $conn->query("SELECT id FROM payments WHERE reference_code = '$reference'");
if ($dup_check->num_rows > 0) {
    renderOutcome("Already Processed", "This payment reference has already been verified and recorded.", "info", "dashboard.php", "View Dashboard");
}

$new_package_id = isset($_GET['new_package_id']) ? intval($_GET['new_package_id']) : 0;
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0; 

// Fetch user details for emails
$user_res = $conn->query("SELECT email, full_name FROM members WHERE id = '$user_id'");
$user_data = $user_res->fetch_assoc();

// 2. Validate Transaction (Live API vs Simulation)
$amount_paid = 0;

if (defined('PAYMENT_MODE') && PAYMENT_MODE === 'live') {
    // ACTUAL PAYSTACK VERIFICATION
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer sk_test_f0d8f7be8f949cdbb21481255abb9ec848fe4831", // TODO: Replace with environment secret key
            "Cache-Control: no-cache",
        ),
          CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        renderOutcome("Verification Error", "Failed to connect to the payment gateway.", "error", "dashboard.php", "Return to Dashboard");
    }
    
    $tranx = json_decode($response);
    
    if (!$tranx->status || $tranx->data->status !== 'success') {
        // Log failed payment into the ledger
        $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, 0, 'installment', 'failed')");
        $log_b_id = $booking_id > 0 ? $booking_id : null;
        $stmt->bind_param("iis", $user_id, $log_b_id, $reference);
        $stmt->execute();
        
        renderOutcome("Payment Failed", "Transaction was declined or unsuccessful.", "error", "dashboard.php", "Back to Dashboard");
    }
    
    // Paystack returns amount in kobo, convert to Naira
    $amount_paid = $tranx->data->amount / 100; 

} else {
    // SIMULATION MODE (MVP fallback)
    if ($item_id > 0) {
        $item_res = $conn->query("SELECT cost FROM trip_additional_items WHERE id = '$item_id'");
        if ($item_res->num_rows > 0) $amount_paid = $item_res->fetch_assoc()['cost'];
    } elseif ($new_package_id > 0) {
        $amount_paid = COMMITMENT_FEE; 
    } elseif ($booking_id > 0 && isset($_GET['amount'])) {
        $amount_paid = floatval($_GET['amount']); 
    }
}

if ($amount_paid <= 0) {
    renderOutcome("Payment Error", "Invalid payment amount detected.", "error", "dashboard.php", "Back to Dashboard");
}

// --- 3. ROUTING LOGIC FOR SUCCESSFUL PAYMENTS ---

// ROUTE A: PAYING FOR AN ADD-ON
if ($item_id > 0 && $booking_id > 0) {
    $item_data = $conn->query("SELECT item_name FROM trip_additional_items WHERE id = '$item_id'")->fetch_assoc();
    $item_name = $item_data['item_name'];

    $stmt = $conn->prepare("INSERT INTO pilgrim_item_payments (member_id, booking_id, item_id, amount, status, payment_ref) VALUES (?, ?, ?, ?, 'paid', ?)");
    $stmt->bind_param("iiids", $user_id, $booking_id, $item_id, $amount_paid, $reference);
    $stmt->execute();

    $stmt2 = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'add_on', 'success')");
    $stmt2->bind_param("iisd", $user_id, $booking_id, $reference, $amount_paid);
    $stmt2->execute();

    $msg_body = "Your payment of <strong>₦".number_format($amount_paid)."</strong> for the Add-on: <strong>$item_name</strong> has been received successfully.";
    send_hajj_mail($user_data['email'], $user_data['full_name'], "Add-on Secured: $item_name", $msg_body);

    renderOutcome("Add-on Secured", "Your payment for $item_name was successful.", "success", "dashboard.php", "View Dashboard");
}

// ROUTE B: NEW TRIP BOOKING
elseif ($new_package_id > 0) {
    
    // --- BUSINESS RULE ENFORCEMENT: Backend Check ---
    $active_check = $conn->query("SELECT id, package_id FROM bookings WHERE member_id = '$user_id' AND booking_status = 'confirmed'");
    
    if ($active_check->num_rows > 0) {
        $active_bk = $active_check->fetch_assoc();
        
        // If they are trying to pay for the EXACT SAME active package again via a stale link, treat it safely as an installment
        if ($active_bk['package_id'] == $new_package_id) {
            $bk_id = $active_bk['id'];
            $conn->query("UPDATE bookings SET amount_paid = amount_paid + $amount_paid WHERE id = '$bk_id'");
            $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'installment', 'success')");
            $stmt->bind_param("iisd", $user_id, $bk_id, $reference, $amount_paid);
            $stmt->execute();
            renderOutcome("Trip Updated", "Payment added as an installment to your existing booking.", "success", "dashboard.php", "View Dashboard");
        } else {
            // Trying to book a NEW package while one is still active -> HARD BLOCK
            renderOutcome("Booking Blocked", "You already have an active trip in progress. You cannot book a new package until your current journey is officially completed.", "error", "dashboard.php", "Back to Dashboard");
        }
    }
    // ----------------------------------------

    if ($amount_paid >= COMMITMENT_FEE) {
        $pkg_res = $conn->query("SELECT total_cost FROM packages WHERE id = '$new_package_id'");
        if ($pkg_res->num_rows > 0) {
            $total_cost = $pkg_res->fetch_assoc()['total_cost'];
            
            // Create booking with NULL trip_batch_id (Awaiting Admin Assignment)
            $stmt = $conn->prepare("INSERT INTO bookings (member_id, package_id, trip_batch_id, total_due, amount_paid, booking_status, travel_date) VALUES (?, ?, NULL, ?, ?, 'confirmed', NULL)");
            $stmt->bind_param("iidd", $user_id, $new_package_id, $total_cost, $amount_paid);
            $stmt->execute();
            $new_booking_id = $conn->insert_id;
            
            $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'commitment', 'success')");
            $stmt->bind_param("iisd", $user_id, $new_booking_id, $reference, $amount_paid);
            $stmt->execute();
            
            $conn->query("UPDATE members SET has_paid_commitment = 1 WHERE id = '$user_id'");
            
            $msg_body = "Your booking is confirmed with an initial deposit of <strong>₦".number_format($amount_paid)."</strong>. An admin will assign you to a specific trip cohort soon.";
            send_hajj_mail($user_data['email'], $user_data['full_name'], "Booking Confirmed", $msg_body);
            
            renderOutcome("Booking Confirmed!", "Payment successful. You are registered. Admin will assign your batch.", "success", "dashboard.php", "Go to Dashboard");
        }
    } else {
        renderOutcome("Insufficient Payment", "Minimum deposit is ₦".number_format(COMMITMENT_FEE).".", "error", "dashboard.php", "Return");
    }
} 

// ROUTE C: INSTALLMENT PAYMENT
elseif ($booking_id > 0) {
    $conn->query("UPDATE bookings SET amount_paid = amount_paid + $amount_paid WHERE id = '$booking_id'");
    $stmt = $conn->prepare("INSERT INTO payments (member_id, booking_id, reference_code, amount, payment_type, status) VALUES (?, ?, ?, ?, 'installment', 'success')");
    $stmt->bind_param("iisd", $user_id, $booking_id, $reference, $amount_paid);
    $stmt->execute();
    
    $msg_body = "We have received your installment payment of <strong>₦".number_format($amount_paid)."</strong> successfully.";
    send_hajj_mail($user_data['email'], $user_data['full_name'], "Installment Payment Received", $msg_body);

    renderOutcome("Payment Successful", "Your installment has been credited to your wallet.", "success", "dashboard.php", "View Dashboard");
}

// Default Fallback
else {
    renderOutcome("Unknown Error", "Transaction details are missing or invalid.", "error", "dashboard.php", "Return");
}
?>