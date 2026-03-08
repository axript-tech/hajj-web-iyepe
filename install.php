<?php
// install.php
// FULL SYSTEM INSTALLER - ABDULLATEEF HAJJ PORTAL

$host = 'localhost';
$user = 'root';
$pass = ''; 
$dbname = 'abdullateef_hajj_portal';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 1. Create DB
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) echo "Database created/checked.<br>";
else die("Error creating DB: " . $conn->error);

$conn->select_db($dbname);

// 2. Define Tables (Order matters for Foreign Keys)
$tables = [
    "members" => "CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NOT NULL,
        dob DATE NULL,
        nin VARCHAR(20) NULL,
        passport_number VARCHAR(50) NULL,
        passport_expiry_date DATE NULL,
        passport_photo VARCHAR(255) NULL,
        password_hash VARCHAR(255) NOT NULL,
        reset_token_hash VARCHAR(64) NULL,
        reset_token_expires_at DATETIME NULL,
        role ENUM('user','admin') DEFAULT 'user',
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        has_completed_health TINYINT(1) DEFAULT 0,
        has_paid_commitment TINYINT(1) DEFAULT 0,
        status ENUM('active','banned') DEFAULT 'active'
    ) ENGINE=InnoDB",

    "medical_profiles" => "CREATE TABLE IF NOT EXISTS medical_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
        genotype ENUM('AA','AS','SS','AC') NULL,
        chronic_conditions TEXT NULL,
        mobility_needs ENUM('None','Wheelchair','Walking Stick','Stretcher') DEFAULT 'None',
        emergency_contact_name VARCHAR(100) NULL,
        emergency_contact_phone VARCHAR(20) NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "packages" => "CREATE TABLE IF NOT EXISTS packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        total_cost DECIMAL(15,2) NOT NULL,
        mecca_hotel VARCHAR(150) NOT NULL,
        medina_hotel VARCHAR(150) NOT NULL,
        slots_available INT DEFAULT 50
    ) ENGINE=InnoDB",

    "trip_batches" => "CREATE TABLE IF NOT EXISTS trip_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        batch_name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        return_date DATE NOT NULL,
        status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
        FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "bookings" => "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        package_id INT NOT NULL,
        trip_batch_id INT NULL,
        total_due DECIMAL(15,2) NOT NULL,
        amount_paid DECIMAL(15,2) DEFAULT 0.00,
        booking_status ENUM('confirmed','completed','cancelled') DEFAULT 'confirmed',
        travel_date DATE NULL,
        mecca_room_no VARCHAR(20) NULL,
        medina_room_no VARCHAR(20) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id),
        FOREIGN KEY (package_id) REFERENCES packages(id),
        FOREIGN KEY (trip_batch_id) REFERENCES trip_batches(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "payments" => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        booking_id INT NULL,
        reference_code VARCHAR(50) NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        payment_type ENUM('commitment','installment') DEFAULT 'installment',
        status ENUM('pending','success','failed') DEFAULT 'success',
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id),
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "announcements" => "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_batch_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('normal', 'urgent') DEFAULT 'normal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trip_batch_id) REFERENCES trip_batches(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "member_notes" => "CREATE TABLE IF NOT EXISTS member_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        note TEXT NOT NULL,
        created_by VARCHAR(100) DEFAULT 'Admin',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

// 3. Execute
foreach ($tables as $name => $sql) {
    if ($conn->query($sql) === TRUE) echo "Table '$name' checked/created.<br>";
    else echo "Error creating '$name': " . $conn->error . "<br>";
}

// 4. Seed Data
$pkg_check = $conn->query("SELECT id FROM packages");
if ($pkg_check->num_rows == 0) {
    $conn->query("INSERT INTO packages (name, total_cost, mecca_hotel, medina_hotel) VALUES
    ('VIP Hajj Package A', 4500000.00, 'Swissotel Makkah (5 Star)', 'Anwar Al Madinah'),
    ('Standard Hajj Package B', 3500000.00, 'Le Meridien Makkah', 'Pullman Zamzam'),
    ('Umrah Ramadan Special', 1800000.00, 'Hilton Suites', 'Shahd Al Madinah')");
    echo "Packages seeded.<br>";
}

$admin_email = 'admin@iyepe.com';
if ($conn->query("SELECT id FROM members WHERE email='$admin_email'")->num_rows == 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO members (full_name, email, phone, password_hash, role) VALUES ('Super Admin', '$admin_email', '0800000000', '$pass', 'admin')");
    echo "Admin created (admin@iyepe.com / admin123)<br>";
}

echo "<hr><h3>System Installation Complete!</h3> <a href='index.php'>Go to Login</a>";
?>