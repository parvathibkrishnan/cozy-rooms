<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$id = intval($_GET['id']);

$query = "
SELECT 
    b.*,
    g.name,
    g.phone,
    g.id_proof,
    r.room_number,
    r.room_type,
    r.ac_type
FROM bookings b
JOIN guests g ON b.guest_id = g.guest_id
JOIN rooms r ON b.room_id = r.room_id
WHERE b.booking_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data){
    die("Booking not found.");
}


$display_status = $data['status'];

if ($data['status'] == 'Checked Out') {
    $display_status = 'Completed';
} elseif ($data['status'] == 'Checked In' && $data['check_out'] < date('Y-m-d')) {
    $display_status = 'Overdue';
} elseif ($data['status'] == 'Booked' && ($data['check_in'] . ' ' . $data['checkin_time']) < date('Y-m-d H:i:s')) {
    $display_status = 'Late Arrival';
}

$status_classes = [
    'Checked In' => 'pill-dark',
    'Booked' => 'pill-light',
    'Completed' => 'pill-success',
    'Overdue' => 'pill-danger',
    'Late Arrival' => 'pill-warning'
];

$pill_class = $status_classes[$display_status] ?? 'pill-light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Details | Cozy Rooms</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --bg-body: #f8f9fa;
    --white: #ffffff;
    --text-main: #1f2937;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --primary: #2c1e1a;
    --primary-hover: #4a362e;
    --shadow-md: 0 4px 6px rgba(0,0,0,0.05);
}

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: var(--bg-body);
}

.content {
    padding: 40px;
    max-width: 1000px;
    margin: auto;
}

.details-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
}

.card-header {
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    background: #f9fafb;
}

.booking-id {
    font-weight: 700;
    color: var(--primary);
}

.status-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.pill-dark { background:#e0e7ff; color:#3730a3; }
.pill-light { background:#fef3c7; color:#92400e; }
.pill-success { background:#dcfce7; color:#166534; }
.pill-warning { background:#ffedd5; color:#c2410c; }
.pill-danger { background:#fee2e2; color:#991b1b; }

.card-body {
    padding: 30px;
}

.section-title {
    font-weight: 600;
    margin-bottom: 15px;
    border-bottom: 2px solid #eee;
    padding-bottom: 5px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-label {
    font-size: 12px;
    color: gray;
}

.info-value {
    font-weight: 500;
}

.val-highlight {
    font-size: 18px;
    font-weight: bold;
    color: var(--primary);
}

.val-yes { color: green; }
.val-no { color: gray; }

.btn {
    padding: 10px 16px;
    background: var(--primary);
    color: white;
    border-radius: 6px;
    text-decoration: none;
}

</style>
</head>
<?php include "header.php"; ?>

<body>

<?php include "navbar.php"; ?>

<div class="content">

<div class="details-card">

<div class="card-header">
    <div class="booking-id">
        Booking #<?php echo str_pad($data['booking_id'],5,'0',STR_PAD_LEFT); ?>
    </div>
    <div class="status-pill <?php echo $pill_class; ?>">
        <?php echo $display_status; ?>
    </div>
</div>

<div class="card-body">


<div class="section-title">Guest Details</div>
<div class="details-grid">
    <div>
        <div class="info-label">Name</div>
        <div class="info-value"><?php echo $data['name']; ?></div>
    </div>
    <div>
        <div class="info-label">Phone</div>
        <div class="info-value"><?php echo $data['phone']; ?></div>
    </div>
</div>


<div class="section-title">Stay Details</div>
<div class="details-grid">
    <div>
        <div class="info-label">Room</div>
        <div class="info-value">
            <?php echo $data['room_number']; ?> 
            (<?php echo $data['room_type']; ?> - <?php echo $data['ac_type']; ?>)
        </div>
    </div>
    <div>
        <div class="info-label">Check-in</div>
        <div class="info-value"><?php echo $data['check_in']; ?></div>
    </div>
    <div>
        <div class="info-label">Check-out</div>
        <div class="info-value"><?php echo $data['check_out']; ?></div>
    </div>
    <div>
        <div class="info-label">Nights</div>
        <div class="info-value"><?php echo $data['nights']; ?></div>
    </div>
</div>


<div class="section-title">Guests</div>
<div class="details-grid">
    <div>
        <div class="info-label">Total Guests</div>
        <div class="info-value">
            <?php echo $data['total_people']; ?> Guests
            <?php if($data['children'] > 0): ?>
                (<?php echo $data['children']; ?> Children)
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="info-label">Pets</div>
        <div class="info-value <?php echo ($data['pets']==1)?'val-yes':'val-no'; ?>">
            <?php echo ($data['pets']==1)?'Yes':'No'; ?>
        </div>
    </div>
    <div>
        <div class="info-label">Private Pool</div>
        <div class="info-value <?php echo ($data['private_pool']==1)?'val-yes':'val-no'; ?>">
            <?php echo ($data['private_pool']==1)?'Yes':'No'; ?>
        </div>
    </div>
</div>


<div class="section-title">Billing</div>
<div class="details-grid">
    <div>
        <div class="info-label">Total Amount</div>
        <div class="val-highlight">₹<?php echo number_format($data['price']); ?></div>
    </div>
    <div>
        <div class="info-label">Payment Status</div>
        <div class="info-value">
            <?php echo ($data['payment_status']=='Paid') ? 'Paid' : 'Pending'; ?>
        </div>
    </div>
</div>

</div>

</div>

</div>

</body>
</html>