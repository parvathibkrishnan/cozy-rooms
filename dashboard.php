<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";


$total_rooms = 0;
$rooms_result = mysqli_query($conn,"SELECT COUNT(*) as count FROM rooms");
if ($rooms_result) {
    $total_rooms = mysqli_fetch_assoc($rooms_result)['count'];
}


$avail_query = mysqli_query($conn, "
    SELECT room_type, ac_type, COUNT(*) as count
    FROM rooms
    WHERE room_id NOT IN (
        SELECT room_id 
        FROM bookings 
        WHERE status = 'Checked In'
        OR (status = 'Booked' AND check_in = CURDATE())
    )
    GROUP BY room_type, ac_type
    ORDER BY room_type, ac_type
");

$available_rooms = 0;
$breakdown_html = "";
if ($avail_query) {
    while($row = mysqli_fetch_assoc($avail_query)) {
        $available_rooms += $row['count'];
        $breakdown_html .= "
        <div class='breakdown-item'>
            <span>" . htmlspecialchars($row['room_type']) . " — " . htmlspecialchars($row['ac_type']) . "</span>
            <span class='breakdown-count'>" . $row['count'] . "</span>
        </div>";
    }
}

if (empty($breakdown_html)) {
    $breakdown_html = "<div class='breakdown-item'>No rooms available</div>";
}


$occupied_query = mysqli_query($conn, "
    SELECT r.room_number, r.room_type, r.ac_type, g.name as guest_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN guests g ON b.guest_id = g.guest_id
    WHERE b.status = 'Checked In'
    ORDER BY CAST(r.room_number AS UNSIGNED)
");

$occupied_rooms = 0;
$occupied_html = "";
if ($occupied_query) {
    while($row = mysqli_fetch_assoc($occupied_query)) {
        $occupied_rooms++;
        $occupied_html .= "
        <div class='breakdown-item' style='flex-direction: column; align-items: flex-start; gap: 4px;'>
            <div style='display: flex; justify-content: space-between; width: 100%;'>
                <span style='color: var(--coffee-dark); font-weight: 700;'>Room " . htmlspecialchars($row['room_number']) . "</span>
                <span style='font-size: 13px; color: var(--text-muted);'>" . htmlspecialchars($row['room_type']) . " - " . htmlspecialchars($row['ac_type']) . "</span>
            </div>
            <span style='font-size: 14px; color: var(--text-main);'><i class='fas fa-user' style='font-size: 12px; color: var(--coffee-light); margin-right: 6px;'></i>" . htmlspecialchars($row['guest_name']) . "</span>
        </div>";
    }
}

if (empty($occupied_html)) {
    $occupied_html = "<div class='breakdown-item'>No rooms are currently occupied</div>";
}


$total_revenue = 0;
$revenue_result = mysqli_query($conn,"
    SELECT SUM(price) as total
    FROM bookings
    WHERE payment_status = 'Paid'
");
if ($revenue_result) {
    $revenue_data = mysqli_fetch_assoc($revenue_result);
    $total_revenue = $revenue_data['total'] ?? 0;
}


$monthly_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(check_out, '%b') as month_name, SUM(price) as monthly_total
    FROM bookings
    WHERE payment_status = 'Paid' 
    AND YEAR(check_out) = YEAR(CURDATE())
    AND check_out <= CURDATE() 
    GROUP BY MONTH(check_out), DATE_FORMAT(check_out, '%b')
    ORDER BY MONTH(check_out)
");

$chart_months = [];
$chart_revenues = [];
if ($monthly_query) {
    while($row = mysqli_fetch_assoc($monthly_query)){
        $chart_months[] = $row['month_name'];
        $chart_revenues[] = $row['monthly_total'];
    }
}

$months_json = json_encode($chart_months);
$revenues_json = json_encode($chart_revenues);


$today_checkouts = mysqli_query($conn,"
SELECT g.name, r.room_number, b.check_out
FROM bookings b
JOIN guests g ON b.guest_id = g.guest_id
JOIN rooms r ON b.room_id = r.room_id
WHERE b.check_out = CURDATE()
AND b.status = 'Checked In'
");


$overdue_guests = mysqli_query($conn,"
SELECT g.name, r.room_number, b.check_out,
DATEDIFF(CURDATE(), b.check_out) AS overdue_days
FROM bookings b
JOIN guests g ON b.guest_id = g.guest_id
JOIN rooms r ON b.room_id = r.room_id
WHERE CURDATE() > b.check_out
AND b.status = 'Checked In'
");


$rooms = mysqli_query($conn,
"
SELECT 
r.*,
CASE
WHEN EXISTS(
SELECT 1 FROM bookings b
WHERE b.room_id=r.room_id
AND b.status='Checked In'
)
THEN 'Occupied'

WHEN EXISTS(
SELECT 1 FROM bookings b
WHERE b.room_id=r.room_id
AND b.status='Booked'
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
    <title>Cozy Rooms | Admin Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --coffee-dark: #2c1e1a;
            --coffee-medium: #6f4e37;
            --coffee-light: #c7a17a;
            --bg-color: #f8f9fa;
            --text-main: #333333;
            --text-muted: #6b7280;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.025);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
        }

        .main-content { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .dashboard-header h1 { color: var(--coffee-dark); margin: 0; font-weight: 700; font-size: 28px; letter-spacing: -0.5px; }
        .date-display { font-weight: 500; color: var(--text-muted); background: white; padding: 8px 16px; border-radius: 8px; box-shadow: var(--shadow-sm); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 16px; display: flex; align-items: center; box-shadow: var(--shadow-md); transition: transform 0.2s ease, box-shadow 0.2s ease; position: relative; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 24px; }
        .stat-card h4 { margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { margin: 6px 0 0 0; font-size: 28px; font-weight: 700; color: var(--coffee-dark); }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(3px); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: white; padding: 25px; border-radius: 16px; width: 90%; max-width: 400px;
            box-shadow: var(--shadow-lg); animation: slideIn 0.3s ease;
        }
        .modal-chart { max-width: 700px; }

        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px; }
        .modal-header h3 { margin: 0; color: var(--coffee-dark); font-size: 18px; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); }
        .close-btn:hover { color: var(--text-main); }
        
        .modal-body { max-height: 60vh; overflow-y: auto; padding-right: 5px; }
        .modal-body::-webkit-scrollbar { width: 6px; }
        .modal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .breakdown-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed #e5e7eb; font-weight: 500; color: var(--text-main); }
        .breakdown-item:last-child { border-bottom: none; }
        .breakdown-count { background: var(--coffee-dark); color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 14px; }

        .alerts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .alert-section h3 { margin-top: 0; margin-bottom: 15px; color: var(--coffee-dark); font-size: 18px; font-weight: 600; }
        .alert-card { padding: 16px; margin-bottom: 12px; border-radius: 8px; display: flex; align-items: center; font-weight: 500; box-shadow: var(--shadow-sm); }
        .alert-card i { margin-right: 12px; font-size: 18px; }
        .alert-warning { background: #fff8eb; border-left: 4px solid #f59e0b; color: #92400e; }
        .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
        .alert-empty { padding: 16px; background: white; border-radius: 8px; color: var(--text-muted); border: 1px dashed #d1d5db; text-align: center; }

        .rooms-header { margin-bottom: 20px; color: var(--coffee-dark); font-size: 22px; font-weight: 600; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .room-card { background: white; border-radius: 16px; overflow: hidden; border: 1px solid #f3f4f6; transition: all 0.3s ease; box-shadow: var(--shadow-sm); }
        .room-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: #e5e7eb; }
        .room-header { padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f3f4f6; background: #fafafa; }
        .room-number { font-size: 16px; color: var(--coffee-dark); font-weight: 700; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-available { background: #dcfce7; color: #166534; }
        .badge-booked { background: #fef3c7; color: #92400e; }
        .badge-occupied { background: #fee2e2; color: #991b1b; }
        .room-body { padding: 20px; }
        .room-type { font-weight: 600; color: var(--text-main); margin-bottom: 8px; font-size: 15px; }
        .room-amenities { font-size: 13px; color: var(--text-muted); margin-bottom: 18px; display: flex; align-items: center; }
        .action-btns { display: flex; gap: 12px; }
        .btn-small { padding: 10px 16px; border-radius: 8px; font-size: 14px; text-decoration: none; flex: 1; text-align: center; font-weight: 600; transition: all 0.2s ease; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-edit { background: #f3f4f6; color: #4b5563; }
        .btn-edit:hover { background: #e5e7eb; color: #1f2937; }
        .btn-book { background: var(--coffee-medium); color: white; }
        .btn-book:hover { background: var(--coffee-dark); box-shadow: 0 4px 6px rgba(111, 78, 55, 0.25); }
        
        @media (max-width: 768px) {
            .main-content { padding: 20px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<?php include "header.php"; ?>
<body>

<?php include "navbar.php"; ?>

<div class="main-content">

    <header class="dashboard-header">
        <h1>Property Overview</h1>
        <div class="date-display">
            <i class="far fa-calendar-alt" style="margin-right: 8px; color: var(--coffee-medium);"></i>
            <?php echo date('F d, Y'); ?>
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #efe9e5; color: var(--coffee-medium);">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <div>
                <h4>Total Rooms</h4>
                <p><?php echo $total_rooms; ?></p>
            </div>
        </div>

        <div class="stat-card" onclick="document.getElementById('availableModal').style.display='flex'" style="cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.borderColor='#166534'" onmouseout="this.style.borderColor='transparent'">
            <div class="stat-icon" style="background: #dcfce7; color: #166534;">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div>
                <h4>Available</h4>
                <p><?php echo $available_rooms; ?></p>
            </div>
        </div>

        <div class="stat-card" onclick="document.getElementById('occupiedModal').style.display='flex'" style="cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.borderColor='#991b1b'" onmouseout="this.style.borderColor='transparent'">
            <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">
                <i class="fa-solid fa-bed"></i>
            </div>
            <div>
                <h4>Occupied</h4>
                <p><?php echo $occupied_rooms; ?></p>
            </div>
        </div>

        <div class="stat-card" onclick="document.getElementById('revenueModal').style.display='flex'" style="cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.borderColor='#92400e'" onmouseout="this.style.borderColor='transparent'">
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <h4>Total Revenue</h4>
                <p>₹<?php echo number_format($total_revenue); ?></p>
            </div>
        </div>
    </div>

    <div class="alerts-grid">
        <div class="alert-section">
            <h3><i class="fas fa-sign-out-alt" style="margin-right:8px; color: #f59e0b;"></i>Today's Checkouts</h3>
            <?php if($today_checkouts && mysqli_num_rows($today_checkouts) > 0){ 
                while($row = mysqli_fetch_assoc($today_checkouts)){ ?>
                    <div class="alert-card alert-warning">
                        <i class="fas fa-info-circle"></i>
                        <span><b>Room <?php echo htmlspecialchars($row['room_number']); ?></b> — <?php echo htmlspecialchars($row['name']); ?></span>
                    </div>
            <?php }} else { ?>
                <div class="alert-empty">No checkouts scheduled for today</div>
            <?php } ?>
        </div>

        <div class="alert-section">
            <h3><i class="fas fa-exclamation-circle" style="margin-right:8px; color: #ef4444;"></i>Overdue Guests</h3>
            <?php if($overdue_guests && mysqli_num_rows($overdue_guests) > 0){ 
                while($row = mysqli_fetch_assoc($overdue_guests)){ ?>
                    <div class="alert-card alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><b>Room <?php echo htmlspecialchars($row['room_number']); ?></b> — <?php echo htmlspecialchars($row['name']); ?> (Overdue by <?php echo $row['overdue_days']; ?> days)</span>
                    </div>
            <?php }} else { ?>
                <div class="alert-empty">No overdue guests. Great job!</div>
            <?php } ?>
        </div>
    </div>

    <h3 class="rooms-header">Live Room Status</h3>

    <div class="room-grid">
        <?php 
        if ($rooms) {
            while ($room = mysqli_fetch_assoc($rooms)) {
                $status = $room['display_status'];
                $status_class = strtolower($status); 
        ?>
        <div class="room-card">
            <div class="room-header">
                <span class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></span>
                <span class="status-badge badge-<?php echo $status_class; ?>">
                    <?php echo $status; ?>
                </span>
            </div>

            <div class="room-body">
                <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                <div class="room-amenities">
                    <i class="fa-solid fa-snowflake" style="margin-right: 6px; color: #9ca3af;"></i> 
                    <?php echo htmlspecialchars($room['ac_type']); ?>
                </div>

                <div class="action-btns">
                    <a href="edit_room_form.php?id=<?php echo $room['room_id']; ?>" class="btn-small btn-edit">
                        <i class="fas fa-pen"></i> Edit
                    </a>

                    <?php if($status == "Available"){ ?>
                        <a href="book_room_form.php?room_id=<?php echo $room['room_id']; ?>" class="btn-small btn-book">
                            <i class="fas fa-calendar-plus"></i> Book
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php 
            } 
        } else {
            echo "<p style='color: var(--text-muted); grid-column: 1 / -1;'>Unable to load live room status at this time.</p>";
        }
        ?>
    </div>

</div>

<div id="availableModal" class="modal-overlay" onclick="this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fa-solid fa-door-open" style="color: #166534; margin-right: 8px;"></i> Availability Breakdown</h3>
            <button class="close-btn" onclick="document.getElementById('availableModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <?php echo $breakdown_html; ?>
        </div>
    </div>
</div>

<div id="occupiedModal" class="modal-overlay" onclick="this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fa-solid fa-bed" style="color: #991b1b; margin-right: 8px;"></i> Occupied Rooms</h3>
            <button class="close-btn" onclick="document.getElementById('occupiedModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <?php echo $occupied_html; ?>
        </div>
    </div>
</div>

<div id="revenueModal" class="modal-overlay" onclick="this.style.display='none'">
    <div class="modal-content modal-chart" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fa-solid fa-chart-line" style="color: #92400e; margin-right: 8px;"></i> Monthly Revenue (<?php echo date('Y'); ?>)</h3>
            <button class="close-btn" onclick="document.getElementById('revenueModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body" style="overflow: hidden;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const chartMonths = <?php echo $months_json; ?>;
    const chartRevenues = <?php echo $revenues_json; ?>;

    const revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartMonths.length > 0 ? chartMonths : ['No Data'],
            datasets: [{
                label: 'Revenue (₹)',
                data: chartRevenues.length > 0 ? chartRevenues : [0],
                backgroundColor: '#c7a17a', 
                borderColor: '#6f4e37',     
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: '#6f4e37'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>