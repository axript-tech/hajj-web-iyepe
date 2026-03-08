<?php
// process_payment.php
session_start();
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    
    // In a real app, integrate Paystack Initialize here
    // For this prototype, we simulate a successful transaction
    
    // 1. Verify User owns booking
    $user_id = $_SESSION['user_id'];
    $check = $conn->query("SELECT id FROM bookings WHERE id='$booking_id' AND member_id='$user_id'");
    
    if ($check->num_rows > 0) {
        // 2. Update Booking Balance
        $sql = "UPDATE bookings SET amount_paid = amount_paid + $amount WHERE id='$booking_id'";
        if ($conn->query($sql)) {
            // Optional: Insert into a 'transactions' table for history
            header("Location: dashboard.php?payment=success");
            exit();
        }
    }
}
header("Location: dashboard.php?payment=error");
?>