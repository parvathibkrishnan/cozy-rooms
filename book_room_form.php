<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$rooms = mysqli_query($conn, "SELECT * FROM rooms WHERE status='Available'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozy Rooms | Book Room</title>

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
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.025);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
        }

        .content {
            padding: 40px;
            max-width: 850px;
            margin: 0 auto;
        }

        .form-card {
            background: var(--white);
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .form-header {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
        }

        .form-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 30px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
            grid-column: span 2;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: var(--text-main);
            transition: all 0.2s ease;
            box-sizing: border-box;
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        input[type="file"] {
            padding: 9px 16px;
            background: #f9fafb;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 30, 26, 0.1);
        }

        input[readonly] {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            font-weight: 600;
            border-color: transparent;
            box-shadow: none;
        }

        .hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 20px;
            grid-column: span 2;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
            justify-content: center;
            width: 100%;
        }

        .back-link:hover {
            color: var(--primary);
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .section-title { grid-column: span 1; }
            .submit-btn { grid-column: span 1; }
        }
    </style>
</head>
<?php include "header.php"; ?>
<body>

<?php include "navbar.php"; ?>

<div class="content">
    <div class="form-card">
        
        <div class="form-header">
            <h2><i class="fas fa-calendar-plus" style="color: var(--text-muted);"></i> Book a Room</h2>
        </div>

        <form method="POST" action="book_room.php" enctype="multipart/form-data">
            
            <div class="form-grid">
                
                <div class="section-title"><i class="fas fa-user-circle"></i> Guest Details</div>
                
                <div class="input-group">
                    <label>Guest Name</label>
                    <input type="text" name="name" placeholder="Full Name" required>
                </div>
                
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" pattern="[0-9]{10}" placeholder="10 digit phone number" required>
                </div>
                
                <div class="input-group full-width">
                    <label>ID Proof (Upload)</label>
                    <input type="file" name="id_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>

                <div class="section-title"><i class="fas fa-calendar-alt"></i> Stay Duration</div>

                <div class="input-group">
                    <label>Check-in Date</label>
                    <input type="date" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="input-group">
                    <label>Check-in Time</label>
                    <input type="time" name="checkin_time" required>
                </div>

                <div class="input-group full-width">
                    <label>Number of Nights</label>
                    <input type="number" name="nights" min="1" max="30" value="1" required>
                </div>

                <div class="section-title"><i class="fas fa-users"></i> Guests & Requirements</div>

                <div class="input-group">
    <label>Total People</label>
    <input type="number" name="total_people" min="1" required>
</div>

                <div class="input-group">
                    <label>Children</label>
                    <input type="number" id="children" name="children" min="0" value="0">
                </div>

                <div id="children_age_box" class="full-width" 
     style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
</div>
                <div class="input-group">
                    <label>Number of Rooms Needed</label>
                    <input type="number" name="rooms_needed" min="1" value="1" required>
                </div>

                <div class="input-group">
                    <label>Extra Bed Needed?</label>
                    <select name="extra_bed" id="extra_bed">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                

                <div class="input-group">
                    <label>Travelling with Pets?</label>
                    <select name="pets" id="pets">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Private Pool Needed?</label>
                    <select name="private_pool" id="private_pool">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>

                <div class="section-title"><i class="fas fa-bed"></i> Room Selection</div>

                <div class="input-group">
                    <label>Room Type</label>
                    <select id="room_type" name="room_type" required>
                        <option value="">Select Type</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
                        <option value="Deluxe">Deluxe</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>AC Type</label>
                    <select id="ac_type" name="ac_type" required>
                        <option value="">Select AC Status</option>
                        <option value="AC">AC</option>
                        <option value="Non-AC">Non-AC</option>
                    </select>
                </div>

                <div class="input-group full-width">
                    <label>Price per Night (₹)</label>
                    <input type="number" name="price" id="price" placeholder="Auto-calculates on selection" readonly required>
                    <div class="hint"><i class="fas fa-info-circle"></i> Price auto-calculates based on room type, AC, pets, and pool selection.</div>
                </div>

                <button type="submit" class="submit-btn"><i class="fas fa-check-circle"></i> Confirm Booking</button>

            </div> </form>
        
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<script>

function updateRoomsAndPrice() {
    const roomType = document.getElementById("room_type").value;
    const acType = document.getElementById("ac_type").value;
    const pets = document.getElementById("pets").value;
    const pool = document.getElementById("private_pool").value;

    let price = 0;

    
    if (acType === "AC") {
        if (roomType === "Single") price = 8500;
        if (roomType === "Double") price = 13500;
        if (roomType === "Deluxe") price = 17500;
    } else if (acType === "Non-AC") {
        if (roomType === "Single") price = 7500;
        if (roomType === "Double") price = 12500;
        if (roomType === "Deluxe") price = 16500;
    }

    
    if (pets === "Yes") price += 1000;
    if (pool === "Yes") price += 4000;
    const extraBed = document.getElementById("extra_bed").value;
    if (extraBed === "Yes") price += 500;

    
    if(roomType && acType) {
        document.getElementById("price").value = price;
    } else {
        document.getElementById("price").value = "";
    }
}


document.getElementById("room_type").addEventListener("change", updateRoomsAndPrice);
document.getElementById("ac_type").addEventListener("change", updateRoomsAndPrice);
document.getElementById("pets").addEventListener("change", updateRoomsAndPrice);
document.getElementById("private_pool").addEventListener("change", updateRoomsAndPrice);
document.getElementById("extra_bed").addEventListener("change", updateRoomsAndPrice);


document.getElementById("children").addEventListener("input", function () {
    let count = parseInt(this.value) || 0;
    let box = document.getElementById("children_age_box");

    box.innerHTML = ""; 

    if (count > 0) {
        for (let i = 1; i <= count; i++) {
            box.innerHTML += `
            <div class="input-group">
                <label>Child ${i} Category</label>
               <select name="child_type[]" required>
    <option value="">Select</option>
    <option value="below6">Below 6 years</option>
    <option value="6to12">6–12 years</option>
</select>
            </div>
            `;
        }
    }
});





</script>

</body>
</html>