<?php
// config/db.php
$host = 'localhost';
$db   = 'abdullateef_hajj_portal';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>