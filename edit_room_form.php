<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$id = intval($_GET['id']);

if($id <= 0){
die("Invalid room ID");
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE room_id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();
$room = $result->fetch_assoc();

if(!$room){
die("Room not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cozy Rooms | Edit Room</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-dark: #2c1e1a; 
            --bg-light: #f8f5f2;
            --border: #e2e8f0;
            --text-main: #1a202c;
            --text-muted: #718096;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            margin: 0;
        }

        .form-container {
            max-width: 550px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .form-header {
            margin-bottom: 25px;
            text-align: center;
        }

        .form-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        form {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid var(--border);
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: var(--text-main);
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #ffffff;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(44, 30, 26, 0.1);
        }

        input[readonly] {
            background: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
            font-weight: 600;
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
            background: var(--primary-dark);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: #1a110e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary-dark);
        }
    </style>
</head>
<?php include "header.php"; ?>
<body>

<?php include "navbar.php"; ?>

<div class="content">
    <div class="form-container">
        
        <div class="form-header">
            <h2>Edit Room</h2>
        </div>

        <form method="POST" action="update_room.php">
            <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
            
            <div class="input-group">
                <label>Room Number</label>
                <input type="text" name="room_number" value="<?php echo $room['room_number']; ?>" required>
            </div>

            <div class="input-group">
                <label>Room Type</label>
                <select name="room_type" id="room_type" required>
                    <option value="Single" <?php if($room['room_type']=="Single") echo "selected"; ?>>Single</option>
                    <option value="Double" <?php if($room['room_type']=="Double") echo "selected"; ?>>Double</option>
                    <option value="Deluxe" <?php if($room['room_type']=="Deluxe") echo "selected"; ?>>Deluxe</option>
                </select>
            </div>

            <div class="input-group">
                <label>AC Type</label>
                <select name="ac_type" id="ac_type" required>
                    <option value="AC" <?php if($room['ac_type']=="AC") echo "selected"; ?>>AC</option>
                    <option value="Non-AC" <?php if($room['ac_type']=="Non-AC") echo "selected"; ?>>Non-AC</option>
                </select>
            </div>

            <div class="input-group">
                <label>Pet Friendly</label>
<select name="pet_friendly">
<option value="0">No</option>
<option value="1">Yes</option>
</select>
            </div>

            <div class="input-group">
                <label>Private Pool</label>
<select name="private_pool">
<option value="0">No</option>
<option value="1">Yes</option>
</select>
            </div>
            

            <div class="input-group">
                <label>Price per Night (₹)</label>
                <input type="number" name="price" id="price" value="<?php echo $room['price']; ?>" readonly required>
                <div class="hint">
                    <i class="fas fa-info-circle"></i> Price updates automatically based on Room & AC type.
                </div>
            </div>

            <button type="submit" class="submit-btn"><i class="fas fa-save" style="margin-right: 6px;"></i> Update Room</button>
        </form>

        <a href="rooms.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Rooms</a>
        
    </div>
</div>

<script>
function setPrice() {
    const roomType = document.getElementById("room_type").value;
    const acType = document.getElementById("ac_type").value;
    let price = "";

    if (acType === "AC") {
        if (roomType === "Single") price = 8500;
        if (roomType === "Double") price = 13500;
        if (roomType === "Deluxe") price = 17500;
    }

    if (acType === "Non-AC") {
        if (roomType === "Single") price = 7500;
        if (roomType === "Double") price = 12500;
        if (roomType === "Deluxe") price = 16500;
    }

    document.getElementById("price").value = price;
}

document.getElementById("room_type").addEventListener("change", setPrice);
document.getElementById("ac_type").addEventListener("change", setPrice);
</script>

</body>
</html>