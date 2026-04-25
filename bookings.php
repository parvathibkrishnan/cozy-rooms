<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";


$query = "
SELECT 
    b.booking_id,
    b.status as core_status,
    b.check_in,
    b.checkin_time,
    b.check_out,
    b.checkout_time,
    b.nights,
    b.price AS total_bill,
    b.total_price,
    g.name AS guest_name,
    g.phone,
    r.room_number,
    r.room_type,
    r.ac_type,
    b.payment_status,
    CASE
        WHEN b.status = 'Checked Out' THEN 'Completed'
        WHEN b.status = 'Checked In' AND b.check_out < CURDATE() THEN 'Overdue'
        WHEN b.status = 'Booked' AND CONCAT(b.check_in, ' ', b.checkin_time) < NOW() THEN 'Late Arrival'
        WHEN b.status = 'Checked In' THEN 'Checked In'
        ELSE 'Booked'
    END AS display_status
FROM bookings b
JOIN guests g ON b.guest_id = g.guest_id
JOIN rooms r ON b.room_id = r.room_id
ORDER BY b.booking_id DESC
";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozy Rooms | Bookings</title>

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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }
        
        .content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin: 0;
            color: var(--primary);
            font-weight: 600;
            font-size: 26px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        
        .table-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f9fafb;
            color: var(--text-muted);
            padding: 16px 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: var(--text-main);
        }

        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }

        
        .guest-name { font-weight: 600; font-size: 15px; color: var(--text-main); display: block;}
        .text-small { font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px; }
        .price-bold { font-weight: 600; color: var(--text-main); font-size: 15px; }
        .room-badge { font-weight: 600; color: var(--primary); }

        
        .pay-paid { color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .pay-pending { color: #dc2626; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }

        
        .status-pill {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .pill-dark { background: #e0e7ff; color: #3730a3; }      
        .pill-light { background: #fef3c7; color: #92400e; }     
        .pill-success { background: #dcfce7; color: #166534; }   
        .pill-warning { background: #ffedd5; color: #c2410c; }   
        .pill-danger { background: #fee2e2; color: #991b1b; }    

        
        .actions-flex {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-checkout {
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--text-main);
        }

        .btn-checkout:hover {
            border-color: var(--text-main);
            background: #f3f4f6;
        }

        .btn-view {
            background: var(--primary);
            color: var(--white);
        }

        .btn-view:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .btn-checkin {
            background: #16a34a;
            color: white;
            border: 1px solid #16a34a;
        }

        .btn-checkin:hover {
            background: #15803d;
            border-color: #15803d;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .text-completed { color: #059669; font-weight: 600; font-size: 13px; padding: 8px 4px; display: inline-flex; align-items: center; gap: 4px; }
    </style>
</head>
<?php include "header.php"; ?>
<body>
<?php include "navbar.php"; ?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-book-open" style="color: var(--text-muted);"></i> All Bookings</h2>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <tr>
                    <th>Guest Details</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Stay</th>
                    <th>Rate/Night</th>
                    <th>Total Bill</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th style="text-align: right;">Actions</th>
                </tr>

                <?php 
                
                $status_classes = [
                    'Checked In' => 'pill-dark',
                    'Booked' => 'pill-light',
                    'Completed' => 'pill-success',
                    'Overdue' => 'pill-danger',
                    'Late Arrival' => 'pill-warning'
                ];

                while ($row = mysqli_fetch_assoc($result)) { 
                    $display_status = $row['display_status'];
                    $pill_class = $status_classes[$display_status] ?? 'pill-light';
                    
                    
                    $nights = max(1, $row['nights']); 
                    $rate = $row['total_bill'] / $nights; 
                ?>
                <tr>
                    <td>
                        <span class="guest-name"><?php echo htmlspecialchars($row['guest_name']); ?></span>
                        <span class="text-small">
                            <i class="fas fa-phone-alt" style="margin-right:4px;"></i>
                            <?php echo htmlspecialchars($row['phone']); ?>
                        </span>
                    </td>
                    
                    <td>
                        <span class="room-badge">Rm <?php echo htmlspecialchars($row['room_number']); ?></span>
                        <span class="text-small">
                            <?php echo htmlspecialchars($row['room_type']) . " (" . htmlspecialchars($row['ac_type']) . ")"; ?>
                        </span>
                    </td>

                    <td>
                        <?php echo date('M d, Y', strtotime($row['check_in'])); ?>
                        <span class="text-small">
                            <i class="far fa-clock"></i> 
                            <?php echo date('h:i A', strtotime($row['checkin_time'])); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo date('M d, Y', strtotime($row['check_out'])); ?>
                        <span class="text-small">
                            <i class="far fa-clock"></i> 
                            <?php echo date('h:i A', strtotime($row['checkout_time'])); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['nights']); ?> 
                        <span class="text-muted">nts</span>
                    </td>
                    
                    <td>₹<?php echo number_format($rate); ?></td>
                    
                    <td class="price-bold">₹<?php echo number_format($row['total_bill']); ?></td>

                    <td>
                        <span class="status-pill <?php echo $pill_class; ?>">
                            <?php echo $display_status; ?>
                        </span>
                    </td>

                    <td>
                        <?php if($row['payment_status'] == 'Paid'): ?>
                            <span class="pay-paid"><i class="fas fa-check-circle"></i> Paid</span>
                        <?php else: ?>
                            <span class="pay-pending"><i class="fas fa-clock"></i> Pending</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="actions-flex">

                            <?php if ($display_status == "Booked" || $display_status == "Late Arrival") { ?>
                                <a class="btn btn-checkin" href="checkin.php?id=<?php echo $row['booking_id']; ?>">
                                    Check In <i class="fas fa-sign-in-alt"></i>
                                </a>

                            <?php } elseif ($display_status == "Checked In" || $display_status == "Overdue") { ?>
                                <a class="btn btn-checkout" href="checkout.php?id=<?php echo $row['booking_id']; ?>">
                                    Check Out <i class="fas fa-sign-out-alt"></i>
                                </a>

                            <?php } else { ?>
                                <span class="text-completed">
                                    <i class="fas fa-check-circle"></i> Done
                                </span>
                            <?php } ?>

                            <a href="booking_details.php?id=<?php echo $row['booking_id']; ?>" class="btn btn-view" title="View Details">
                               <i class="fas fa-eye"></i> View
                            </a>

                            <?php if($row['core_status'] == 'Checked Out'): ?>
                                <a href="view_bill.php?id=<?= $row['booking_id'] ?>" class="btn btn-checkout" title="View Bill">
                                    <i class="fas fa-file-invoice"></i> Bill
                                </a>
                            <?php endif; ?>

                            <?php if($row['core_status'] == 'Checked Out' && $row['payment_status'] != 'Paid'): ?>
                                <a href="mark_paid.php?id=<?= $row['booking_id'] ?>" class="btn btn-checkin" title="Mark as Paid">
                                    Paid
                                </a>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>

</body>
</html>