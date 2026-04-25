<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$result = mysqli_query($conn,"
SELECT room_number
FROM rooms
ORDER BY CAST(room_number AS UNSIGNED) DESC
LIMIT 1
");

$row = mysqli_fetch_assoc($result);

if(!$row){
    $next_room = 1;
}
else{
    $last = $row['room_number'];

    if($last < 12){
        $next_room = $last + 1;
    }
    elseif($last == 12){
        $next_room = 101;
    }
    elseif($last < 112){
        $next_room = $last + 1;
    }
    elseif($last == 112){
        $next_room = 201;
    }
    elseif($last < 212){
        $next_room = $last + 1;
    } else {
        $next_room = $last + 1; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozy Rooms | Add Room</title>
    
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
            max-width: 800px; 
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
            margin-top: 30px;
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
            .form-grid {
                grid-template-columns: 1fr; 
            }
            .full-width {
                grid-column: span 1;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>

<?php include "header.php"; ?>

<body>

<?php include "navbar.php"; ?>

<div class="content">
    <div class="form-card">
        
        <div class="form-header">
            <h2><i class="fas fa-door-open" style="color: var(--text-muted);"></i> Add New Room</h2>
        </div>

        <form method="POST" action="add_room.php">
            
            <div class="form-grid">
                
                <div class="input-group full-width">
                    <label>Room Number</label>
                    <input type="text" name="room_number" value="<?php echo htmlspecialchars($next_room); ?>" readonly>
                </div>

                <div class="input-group">
                    <label>Room Type</label>
                    <select name="room_type" id="room_type" required>
                        <option value="">Select Room Type</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
                        <option value="Deluxe">Deluxe</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>AC Type</label>
                    <select name="ac_type" id="ac_type" required>
                        <option value="">Select AC Type</option>
                        <option value="AC">AC</option>
                        <option value="Non-AC">Non-AC</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Pet Friendly</label>
                    <select name="pet_friendly" id="pet_friendly" required>
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                        
                <div class="input-group">
                    <label>Private Swimming Pool</label>
                    <select name="private_pool" id="private_pool" required>
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Room Capacity</label>
                    <input type="number" name="capacity" id="capacity" placeholder="Auto-calculated" readonly required>
                </div>

                <div class="input-group">
                    <label>Price per Night (₹)</label>
                    <input type="number" name="price" id="price" placeholder="Auto-calculated" readonly required>
                </div>

            </div> <button type="submit" class="submit-btn"><i class="fas fa-check-circle"></i> Save Room Details</button>
        </form>

        <a href="rooms.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Rooms List</a>
        
    </div>
</div>

<script>
function setRoomDetails() {
    const roomType = document.getElementById("room_type").value;
    const acType = document.getElementById("ac_type").value;
    const pet = document.getElementById("pet_friendly").value;
    const pool = document.getElementById("private_pool").value;

    let price = 0;
    let capacity = "";

    
    if (roomType === "Single") capacity = 2;
    if (roomType === "Double") capacity = 3;
    if (roomType === "Deluxe") capacity = 4;

    document.getElementById("capacity").value = capacity;

   
    if (!roomType || !acType) {
        document.getElementById("price").value = "";
        return;
    }

    
    if (acType === "AC") {
        if (roomType === "Single") price = 8500;
        if (roomType === "Double") price = 13500;
        if (roomType === "Deluxe") price = 17500;
    } else if (acType === "Non-AC") {
        if (roomType === "Single") price = 7500;
        if (roomType === "Double") price = 12500;
        if (roomType === "Deluxe") price = 16500;
    }

    
    if (pet === "1") price += 1000;
    if (pool === "1") price += 4000;

    
    document.getElementById("price").value = price;
}


document.getElementById("room_type").addEventListener("change", setRoomDetails);
document.getElementById("ac_type").addEventListener("change", setRoomDetails);
document.getElementById("pet_friendly").addEventListener("change", setRoomDetails);
document.getElementById("private_pool").addEventListener("change", setRoomDetails);
</script>

</body>
</html>