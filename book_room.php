<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

mysqli_begin_transaction($conn);

try {
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $check_in = $_POST['check_in'] ?? '';
    $checkin_time = $_POST['checkin_time'] ?? '12:00:00'; 
    $nights = intval($_POST['nights'] ?? 0);
    $price = intval($_POST['price'] ?? 0);
    $children = intval($_POST['children'] ?? 0);
    $total_people_input = intval($_POST['total_people'] ?? 0);
    $rooms_needed = intval($_POST['rooms_needed'] ?? 0);
    $room_type = $_POST['room_type'] ?? '';
    $ac_type = $_POST['ac_type'] ?? '';
    $extra_bed = $_POST['extra_bed'] ?? 'No';
    $pets = (isset($_POST['pets']) && $_POST['pets'] == "Yes") ? 1 : 0;
    $private_pool = (isset($_POST['private_pool']) && $_POST['private_pool'] == "Yes") ? 1 : 0;

    
    if(empty($name)) throw new Exception("Name is required.");
    if(!preg_match("/^[0-9]{10}$/", $phone)) throw new Exception("Invalid phone number.");
    if($nights <= 0 || $nights > 30) throw new Exception("Invalid number of nights.");
    if($total_people_input > 10 || $children > 10) throw new Exception("Invalid guest count.");
    if($rooms_needed > 10 || $rooms_needed <= 0) throw new Exception("Invalid booking request.");
    if($price <= 0) throw new Exception("Invalid price.");
    if($check_in < date("Y-m-d")) throw new Exception("Bookings cannot be made for past dates.");

    
    $extra_capacity = ($extra_bed == "Yes") ? 1 : 0;
    $counted_children = 0;
    $ignored_children = 0;
    $children_ages_arr = [];

    if($children > 0){
        if(!isset($_POST['children_ages']) || count($_POST['children_ages']) != $children){
            throw new Exception("Please select child age for all children.");
        }
        foreach($_POST['children_ages'] as $age){
            $children_ages_arr[] = intval($age);
            if($age >= 13) {
                $total_people_input++; 
            } elseif($age >= 6) {
                $counted_children++; 
            } else {
                $ignored_children++; 
            }
        }
    }
    
    $children_ages_json = json_encode($children_ages_arr);
    $total_people = $total_people_input;

    
    if($room_type == "Single"){
        if($total_people > (1 + $extra_capacity)) throw new Exception("Single room capacity exceeded.");
        if($counted_children > 1) throw new Exception("Only 1 child allowed in Single room.");
    } elseif($room_type == "Double"){
        if($total_people > (2 + $extra_capacity)) throw new Exception("Double room capacity exceeded.");
        if($counted_children > 2) throw new Exception("Too many children for Double room.");
    } elseif($room_type == "Deluxe"){
        if($total_people > (3 + $extra_capacity)) throw new Exception("Deluxe room capacity exceeded.");
        if($counted_children > 3) throw new Exception("Too many children for Deluxe room.");
    }

    $room_price = $price * $nights;
    if($extra_bed == "Yes") $room_price += 500;

    
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if(!isset($_FILES['id_proof']) || $_FILES['id_proof']['error'] !== UPLOAD_ERR_OK){
        throw new Exception("ID Proof upload failed or missing.");
    }

    $file_tmp = $_FILES['id_proof']['tmp_name'];
    $file_size = $_FILES['id_proof']['size']; 

    
    if ($file_size > 5242880) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    $mime_type = mime_content_type($file_tmp);
    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
    
    if(!in_array($mime_type, $allowed_mimes)){
        throw new Exception("Invalid file type. Only JPG, PNG, PDF allowed.");
    }

    $file_ext = strtolower(pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION));
    $new_filename = "ID_" . time() . "_" . rand(1000,9999) . "." . $file_ext;
    $target_path = $upload_dir . $new_filename;

    if(!move_uploaded_file($file_tmp, $target_path)){
        throw new Exception("File upload failed.");
    }

    
    $stmt = $conn->prepare("INSERT INTO guests (name, phone, id_proof) VALUES (?, ?, ?)");
    if(!$stmt->execute([$name, $phone, $new_filename])){
        throw new Exception("Failed to save guest to database.");
    }
    $guest_id = $conn->insert_id;

   
    $checkin_datetime = new DateTime($check_in . ' ' . $checkin_time);
    $checkout_datetime = clone $checkin_datetime;
    $checkout_datetime->modify("+$nights day");

    $check_out = $checkout_datetime->format('Y-m-d');
    $checkout_time = $checkout_datetime->format('H:i:s');
    $new_checkin = $checkin_datetime->format('Y-m-d H:i:s');
    $new_checkout = $checkout_datetime->format('Y-m-d H:i:s');

    
    $current_time = date("Y-m-d H:i:s");
    $booking_status = ($new_checkin > $current_time) ? "Booked" : "Checked In";

    
    $room_type_clean = mysqli_real_escape_string($conn, $room_type);
    $ac_type_clean = mysqli_real_escape_string($conn, $ac_type);
    $capacity_needed = ceil($total_people / $rooms_needed);

    $query = "
        SELECT * FROM rooms r
        WHERE r.room_type='$room_type_clean'
        AND r.ac_type='$ac_type_clean'
        AND r.status='Available'
        AND r.capacity >= $capacity_needed
        AND (r.pet_friendly = 1 OR $pets = 0)
        AND (r.private_pool = 1 OR $private_pool = 0)
        AND r.room_id NOT IN (
            SELECT room_id FROM bookings
            WHERE status IN ('Booked','Checked In')
            AND (
                CONCAT(check_in,' ',checkin_time) < '$new_checkout' AND
                CONCAT(check_out,' ',checkout_time) > '$new_checkin'
            )
        )
        ORDER BY r.price ASC LIMIT $rooms_needed 
        FOR UPDATE
    ";
    
    
    $rooms = mysqli_query($conn, $query);
    if(!$rooms){
        
        error_log("Database error in room search: " . mysqli_error($conn));
        
        throw new Exception("Unable to complete booking due to a system error. Please try again.");
    }

    if(mysqli_num_rows($rooms) < $rooms_needed){
        throw new Exception("Not enough rooms available for selected dates.");
    }

   
    $allocated_rooms = [];
    
    
    $book_stmt = $conn->prepare("
        INSERT INTO bookings 
        (guest_id, room_id, check_in, check_out, checkin_time, checkout_time, nights, total_people, children, children_ages, rooms_needed, pets, private_pool, status, price) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    while($room = mysqli_fetch_assoc($rooms)){
        $room_id = $room['room_id'];
        $allocated_rooms[] = $room['room_number'];

        $book_stmt->bind_param("iissssiiisiiisi", 
            $guest_id, $room_id, $check_in, $check_out, $checkin_time, $checkout_time, 
            $nights, $total_people_input, $children, $children_ages_json, 
            $rooms_needed, $pets, $private_pool, $booking_status, $room_price
        );
        
        if(!$book_stmt->execute()){
            throw new Exception("Failed to save booking data.");
        }

        if($booking_status == "Checked In"){
            $update_stmt = $conn->prepare("UPDATE rooms SET status='Occupied' WHERE room_id=?");
            $update_stmt->bind_param("i", $room_id);
            $update_stmt->execute();
        }
    }

    mysqli_commit($conn);

    
    $room_list = implode(", ", $allocated_rooms);
    header("Location: booking_success.php?rooms=" . urlencode($room_list));
    exit();

} catch(Exception $e) {
    mysqli_rollback($conn);
    
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
    echo "<script>alert('Booking failed: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>