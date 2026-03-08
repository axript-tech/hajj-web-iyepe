<?php
// admin/print_manifest.php
session_start();
require_once '../config/db.php';
require_once '../config/constants.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

$type = isset($_GET['type']) ? $_GET['type'] : 'members';
$title = "Official Manifest";
$data = [];

// --- FETCH DATA BASED ON MANIFEST TYPE ---
if ($type === 'medical') {
    $batch_id = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;
    
    if ($batch_id > 0) {
        $batch_info = $conn->query("SELECT batch_name FROM trip_batches WHERE id = '$batch_id'")->fetch_assoc();
        $title = "Medical & Safety Manifest - " . ($batch_info['batch_name'] ?? 'Unknown Cohort');
        
        $sql = "SELECT m.full_name, m.phone, m.passport_number, m.nin,
                       mp.blood_group, mp.genotype, mp.chronic_conditions, mp.mobility_needs, mp.emergency_contact_phone
                FROM bookings b
                JOIN members m ON b.member_id = m.id
                LEFT JOIN medical_profiles mp ON m.id = mp.member_id
                WHERE b.trip_batch_id = '$batch_id' AND b.booking_status = 'confirmed' AND m.role = 'user'
                ORDER BY mp.mobility_needs DESC, m.full_name ASC";
    } else {
        $title = "Master Medical & Safety Manifest";
        $sql = "SELECT m.full_name, m.phone, m.passport_number, m.nin,
                       mp.blood_group, mp.genotype, mp.chronic_conditions, mp.mobility_needs, mp.emergency_contact_phone
                FROM members m
                LEFT JOIN medical_profiles mp ON m.id = mp.member_id
                WHERE m.role = 'user'
                ORDER BY mp.mobility_needs DESC, m.full_name ASC";
    }
    
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) $data[] = $row;
    
} elseif ($type === 'trip' && isset($_GET['batch_id'])) {
    $batch_id = intval($_GET['batch_id']);
    $batch_info = $conn->query("SELECT batch_name, start_date FROM trip_batches WHERE id = '$batch_id'")->fetch_assoc();
    $title = "Flight & Accommodation Manifest - " . ($batch_info['batch_name'] ?? 'Unknown Cohort');
    
    $sql = "SELECT m.full_name, m.passport_number, m.phone, b.mecca_room_no, b.medina_room_no
            FROM bookings b
            JOIN members m ON b.member_id = m.id
            WHERE b.trip_batch_id = '$batch_id' AND b.booking_status = 'confirmed'
            ORDER BY m.full_name ASC";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) $data[] = $row;

} else {
    // Default: General Members List
    $title = "Master Pilgrim Register";
    $sql = "SELECT m.id, m.full_name, m.email, m.phone, m.nin, m.status, b.amount_paid
            FROM members m
            LEFT JOIN bookings b ON m.id = b.member_id
            WHERE m.role = 'user'
            ORDER BY m.full_name ASC";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) $data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print <?php echo htmlspecialchars($title); ?></title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 11pt; 
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1B7D75;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header img {
            height: 70px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .header h1 { margin: 0 0 5px 0; color: #1B7D75; font-size: 18pt; text-transform: uppercase;}
        .header p { margin: 0; font-size: 10pt; color: #555; }
        
        .manifest-title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 15px;
            background: #f4f4f4;
            padding: 8px;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            page-break-inside: avoid;
        }
        th {
            background-color: #1B7D75;
            color: #fff;
            font-size: 10pt;
            text-transform: uppercase;
        }
        td { font-size: 10pt; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            position: relative;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin: 40px auto 5px auto;
        }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .alert-text { color: #d32f2f; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- Print Controls -->
    <div class="no-print" style="padding: 10px; background: #eee; text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; font-weight: bold; cursor: pointer; background: #1B7D75; color: white; border: none; border-radius: 5px;">
            Print Document Now
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">Close Window</button>
    </div>

    <!-- Official Document Header -->
    <div class="header">
        <img src="https://abdullateefhajjumrah.com/wp-content/uploads/elementor/thumbs/727b0-abdullateef-hajj-and-umrah-logo-qtfd71hyunrre8osnz9m8vl0hf3deqv7d71ztqylmo.png" alt="Abdullateef Hajj & Umrah Logo">
        <h1>Abdullateef Integrated Hajj & Umrah</h1>
        <p>Official System Generated Document | Printed on: <?php echo date('d M Y, h:i A'); ?></p>
    </div>

    <div class="manifest-title">
        <?php echo htmlspecialchars($title); ?> 
        <br><span style="font-size: 10pt; font-weight: normal;">Total Records: <?php echo count($data); ?></span>
    </div>

    <!-- Render Data Table based on Type -->
    <table>
        <?php if ($type === 'medical'): ?>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pilgrim Name</th>
                    <th>Contact</th>
                    <th>Blood / Geno</th>
                    <th>Conditions</th>
                    <th>Mobility</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?><br><small>Emrg: <?php echo htmlspecialchars($row['emergency_contact_phone'] ?? ''); ?></small></td>
                    <td><?php echo $row['blood_group'] ?: '-'; ?> / <span class="<?php echo ($row['genotype']=='SS')?'alert-text':''; ?>"><?php echo $row['genotype'] ?: '-'; ?></span></td>
                    <td class="<?php echo (!empty($row['chronic_conditions']) && $row['chronic_conditions']!='None')?'alert-text':''; ?>"><?php echo htmlspecialchars($row['chronic_conditions'] ?? ''); ?></td>
                    <td class="<?php echo ($row['mobility_needs']!='None')?'alert-text':''; ?>"><?php echo $row['mobility_needs']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        <?php elseif ($type === 'checkin'): ?>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pilgrim Name</th>
                    <th>Passport No.</th>
                    <th>Phone</th>
                    <th>Boarding Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($data as $row): 
                    $status_val = $row['checkin_status'] ?? 'pending';
                    $status_text = 'PENDING';
                    $status_class = '';
                    if ($status_val === 'checked_in') { $status_text = 'CHECKED IN'; $status_class = 'text-green-600'; }
                    elseif ($status_val === 'no_show') { $status_text = 'NO SHOW'; $status_class = 'alert-text'; }
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['passport_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                    <td class="text-center font-bold <?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        <?php else: ?>
            <!-- Default: Members List -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                <tr>
                    <td><?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                    <td class="text-center"><?php echo strtoupper($row['status'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        <?php endif; ?>
    </table>

    <?php if (empty($data)): ?>
        <p class="text-center" style="padding: 20px; font-style: italic;">No records found for this manifest.</p>
    <?php endif; ?>

    <div class="footer">
        <div class="signature-line"></div>
        <p>Authorized Signature / Stamp</p>
        <p style="margin-top: 20px; font-size: 8pt;">This document contains sensitive personal data and is for official operational use only.</p>
    </div>

    <script>
        // Automatically open print dialog when the page loads
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>