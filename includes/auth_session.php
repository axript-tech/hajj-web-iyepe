<?php
// includes/auth_session.php
session_start();
require_once __DIR__ . '/../config/db.php'; // Use __DIR__ to ensure correct path to db

// 1. Check Login Status
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Status for Gates
// We prepare this statement to prevent SQL injection and ensure we have fresh data
$stmt = $conn->prepare("SELECT has_completed_health, has_paid_commitment, role FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Allow Admin to bypass all gates
if ($user['role'] === 'admin') {
    return; 
}

$current_page = basename($_SERVER['PHP_SELF']);

// 3. Gate 1: Health Profile Check
// If health is not done, and user is not currently ON the health page, redirect them.
if ($user['has_completed_health'] == 0 && $current_page != 'health_profile.php') {
    header("Location: health_profile.php?msg=safety_first");
    exit();
}

// 4. Gate 2: Commitment Fee Check
// If health IS done, but Commitment is NOT paid, and user is not on payment page, redirect.
if ($user['has_completed_health'] == 1 && $user['has_paid_commitment'] == 0 && $current_page != 'make_commitment.php') {
    // Exception: Allow them to view health profile if they want to edit it
    if ($current_page != 'health_profile.php') {
        header("Location: make_commitment.php?msg=commitment_required");
        exit();
    }
}
?>