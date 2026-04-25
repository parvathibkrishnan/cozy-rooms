<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$result = mysqli_query($conn,"
SELECT 
r.*,
CASE
WHEN EXISTS(
    SELECT 1 FROM bookings b
    WHERE b.room_id = r.room_id
    AND b.status = 'Checked In'
)
THEN 'Occupied'

WHEN EXISTS(
    SELECT 1 FROM bookings b
    WHERE b.room_id = r.room_id
    AND b.status = 'Booked'
    AND b.check_in = CURDATE()
)
THEN 'Booked'

ELSE 'Available'
END AS display_status

FROM rooms r
ORDER BY CAST(r.room_number AS UNSIGNED)
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozy Rooms | Manage Rooms</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 26px;
            color: var(--primary);
        }

        .add-btn {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .add-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .table-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
            text-align: left;
        }

        td {
            padding: 18px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        .room-num {
            font-weight: 600;
            color: var(--text-main);
            font-size: 15px;
        }

        .status-pill {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            letter-spacing: 0.5px;
        }

        .status-Available { background: #dcfce7; color: #166534; }
        .status-Booked { background: #fef3c7; color: #92400e; }
        .status-Occupied { background: #fee2e2; color: #991b1b; }

        .action-icons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .action-icon-btn {
            color: var(--text-muted);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .action-icon-btn.edit:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .action-icon-btn.delete:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        
        .text-yes { color: #10b981; }
        .text-no { color: #9ca3af; }
    </style>
</head>
<?php include "header.php"; ?>
<body>

<?php include "navbar.php"; ?>

<div class="content">

    <div class="page-header">
        <h2>Property Rooms</h2>
        <a href="add_room_form.php" class="add-btn">
            <i class="fas fa-plus" style="margin-right:8px;"></i>
            Add New Room
        </a>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <tr>
                    <th>Room No.</th>
                    <th>Type</th>
                    <th>AC</th>
                    <th>Capacity</th>
                    <th>Pets</th>
                    <th>Pool</th>
                    <th>Price / Night</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>

                <?php 
                
                $status_classes = [
                    'Available' => 'status-Available',
                    'Booked' => 'status-Booked',
                    'Occupied' => 'status-Occupied'
                ];

                while ($row = mysqli_fetch_assoc($result)) {
                    $status = $row['display_status'];
                    $pill_class = $status_classes[$status] ?? 'status-Available';
                ?>
                <tr>
                    <td class="room-num">
                        <?php echo htmlspecialchars($row['room_number']); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['room_type']); ?>
                    </td>
                    <td>
                        <?php if($row['ac_type'] == "AC"){ ?>
                            <i class="fas fa-snowflake" style="color:#38bdf8; margin-right:6px;"></i> AC
                        <?php } else { ?>
                            <i class="fas fa-wind" style="color:#9ca3af; margin-right:6px;"></i> Non-AC
                        <?php } ?>
                    </td>
                    <td>
                        <i class="fas fa-user-friends" style="color:#9ca3af; margin-right:6px;"></i>
                        <?php echo htmlspecialchars($row['capacity']); ?>
                    </td>
                    <td>
                        <?php if($row['pet_friendly']) { ?>
                            <span class="text-yes"><i class="fas fa-paw"></i> Yes</span>
                        <?php } else { ?>
                            <span class="text-no"><i class="fas fa-times"></i> No</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if($row['private_pool']) { ?>
                            <span class="text-yes"><i class="fas fa-water"></i> Yes</span>
                        <?php } else { ?>
                            <span class="text-no"><i class="fas fa-times"></i> No</span>
                        <?php } ?>
                    </td>
                    <td style="font-weight:500;">
                        ₹<?php echo number_format($row['price']); ?>
                    </td>
                    <td>
                        <span class="status-pill <?php echo $pill_class; ?>">
                            <?php echo $status; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-icons">
                            <a href="edit_room_form.php?id=<?php echo $row['room_id']; ?>" class="action-icon-btn edit" title="Edit Room">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_room.php?id=<?php echo $row['room_id']; ?>" 
                               class="action-icon-btn delete" 
                               title="Delete Room"
                               onclick="return confirm('Are you sure you want to delete Room <?php echo htmlspecialchars($row['room_number']); ?>? This action cannot be undone.');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
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