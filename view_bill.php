<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$id = $_GET['id'];


$query = "
SELECT 
    b.*, 
    g.name, 
    g.phone,
    r.room_number, 
    r.room_type, 
    r.ac_type, 
    r.price
FROM bookings b
JOIN guests g ON b.guest_id = g.guest_id
JOIN rooms r ON b.room_id = r.room_id
WHERE b.booking_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    die("Booking not found.");
}


$checkin = new DateTime($row['check_in']);

$actual_checkout_date = !empty($row['actual_checkout']) ? $row['actual_checkout'] : $row['check_out'];
$actual_checkout = new DateTime($actual_checkout_date);
$booked_checkout = new DateTime($row['check_out']);

$stay_days = max(1, $checkin->diff($actual_checkout)->days);
$booked_days = max(1, $checkin->diff($booked_checkout)->days);


$room_cost = $row['price'] * $stay_days;


$unused_days = $booked_days - $stay_days;
$early_penalty = ($unused_days > 0) ? $unused_days * 500 : 0;


$extra_bed_total = ($row['extra_bed'] == 'Yes' || $row['extra_bed'] == 1) ? 1000 * $stay_days : 0;
$pet_total = ($row['pets'] == 'Yes' || $row['pets'] == 1) ? 1000 : 0;
$pool_total = ($row['private_pool'] == 'Yes' || $row['private_pool'] == 1) ? 4000 : 0;

$subtotal = $room_cost + $extra_bed_total + $pet_total + $pool_total + $early_penalty;

$gst = $subtotal * 0.12;
$total = $subtotal + $gst;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice | Cozy Rooms</title>

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
            --primary-light: #4a362e;
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.025);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }

        .content {
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-btn {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .back-btn:hover { color: var(--primary); }

        .print-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .print-btn:hover { background: var(--primary-light); transform: translateY(-2px); }

        
        .invoice-card {
            background: var(--white);
            padding: 50px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border);
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .brand h1 {
            margin: 0 0 5px 0;
            color: var(--primary);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand p { margin: 0; color: var(--text-muted); font-size: 13px; }

        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0 0 10px 0; font-size: 24px; letter-spacing: 2px; color: var(--text-muted); text-transform: uppercase; }
        .invoice-details p { margin: 4px 0; font-size: 14px; color: var(--text-main); }
        .invoice-details strong { color: var(--text-muted); font-weight: 500; }

        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-box h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .info-box p { margin: 6px 0; font-size: 14px; }
        .info-box strong { font-weight: 600; color: var(--text-main); display: inline-block; width: 110px; }

       
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #f9fafb;
            color: var(--text-muted);
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
        }

        .text-right { text-align: right; }
        .item-desc { font-weight: 500; margin-bottom: 4px; display: block; }
        .item-calc { font-size: 12px; color: var(--text-muted); }

        .text-red { color: #dc2626; }
        
        
        .totals-box {
            width: 350px;
            margin-left: auto;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        .totals-row.grand-total {
            border-top: 2px solid var(--border);
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        
        .payment-status {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
        }

        .status-paid { color: #059669; }
        .status-pending { color: #dc2626; }

        
        @media print {
            body { background: white; }
            .sidebar { display: none !important; }
            .content { padding: 0; width: 100%; max-width: 100%; margin: 0; }
            .actions-bar { display: none; }
            .invoice-card { box-shadow: none; border: none; padding: 20px; }
        }
    </style>
</head>
<?php include "header.php"; ?>
<body>

<?php include "navbar.php"; ?>

<div class="content">
    
    <div class="actions-bar">
        <a href="bookings.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
        <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print Invoice</button>
    </div>

    <div class="invoice-card">
        
        <div class="invoice-header">
            <div class="brand">
                <h1><i class="fa-solid fa-mug-hot"></i> Cozy Rooms</h1>
                <p>Koramangala, Bengaluru<br>admin@cozyrooms.com | +91 1234567809</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>Invoice No:</strong> INV-<?= str_pad($row['booking_id'], 5, '0', STR_PAD_LEFT) ?></p>
                <p><strong>Issue Date:</strong> <?= date("M d, Y") ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Billed To</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?></p>
            </div>
            
            <div class="info-box">
                <h3>Stay Details</h3>
                <p><strong>Room:</strong> <?= htmlspecialchars($row['room_number']) ?> (<?= htmlspecialchars($row['room_type']) ?> - <?= htmlspecialchars($row['ac_type']) ?>)</p>
                <p><strong>Check-in:</strong> <?= date("M d, Y", strtotime($row['check_in'])) ?></p>
                <p><strong>Actual Stay:</strong> <?= $stay_days ?> <?= $stay_days == 1 ? 'Night' : 'Nights' ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="item-desc">Room Charges</span>
                        <span class="item-calc">₹<?= number_format($row['price'], 2) ?> × <?= $stay_days ?> nights</span>
                    </td>
                    <td class="text-right">₹<?= number_format($room_cost, 2) ?></td>
                </tr>

                <?php if($early_penalty > 0): ?>
                <tr>
                    <td>
                        <span class="item-desc text-red">Early Checkout Penalty</span>
                        <span class="item-calc">₹500.00 × <?= $unused_days ?> unused nights</span>
                    </td>
                    <td class="text-right text-red">₹<?= number_format($early_penalty, 2) ?></td>
                </tr>
                <?php endif; ?>

                <?php if($extra_bed_total > 0): ?>
                <tr>
                    <td>
                        <span class="item-desc">Extra Bed Charges</span>
                        <span class="item-calc">₹1,000.00 × <?= $stay_days ?> nights</span>
                    </td>
                    <td class="text-right">₹<?= number_format($extra_bed_total, 2) ?></td>
                </tr>
                <?php endif; ?>

                <?php if($pet_total > 0): ?>
                <tr>
                    <td>
                        <span class="item-desc">Pet Accommodation Fee</span>
                        <span class="item-calc">Flat rate</span>
                    </td>
                    <td class="text-right">₹<?= number_format($pet_total, 2) ?></td>
                </tr>
                <?php endif; ?>

                <?php if($pool_total > 0): ?>
                <tr>
                    <td>
                        <span class="item-desc">Private Pool Access</span>
                        <span class="item-calc">Flat rate</span>
                    </td>
                    <td class="text-right">₹<?= number_format($pool_total, 2) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totals-box">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>₹<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="totals-row">
                <span>GST (12%)</span>
                <span>₹<?= number_format($gst, 2) ?></span>
            </div>
            <div class="totals-row grand-total">
                <span>Total Amount</span>
                <span>₹<?= number_format($total, 2) ?></span>
            </div>
        </div>

        <div class="payment-status">
            Payment Status: 
            <span class="<?= (isset($row['payment_status']) && $row['payment_status'] == 'Paid') ? 'status-paid' : 'status-pending' ?>">
                <i class="fas <?= (isset($row['payment_status']) && $row['payment_status'] == 'Paid') ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= isset($row['payment_status']) ? htmlspecialchars($row['payment_status']) : 'Pending' ?>
            </span>
        </div>

    </div>
</div>

</body>
</html>