<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";


$room_number = trim($_POST['room_number'] ?? '');
$room_type   = trim($_POST['room_type'] ?? '');
$ac_type     = trim($_POST['ac_type'] ?? '');
$capacity    = intval($_POST['capacity'] ?? 0);
$price       = floatval($_POST['price'] ?? 0);


$pet_friendly = (isset($_POST['pet_friendly']) && $_POST['pet_friendly'] == '1') ? 1 : 0;
$private_pool = (isset($_POST['private_pool']) && $_POST['private_pool'] == '1') ? 1 : 0;


if(empty($room_number) || empty($room_type) || empty($ac_type)){
    die("Invalid room details. Please fill all required fields.");
}
if($capacity <= 0 || $capacity > 10){
    die("Invalid capacity. Must be between 1 and 10.");
}
if($price <= 0){
    die("Invalid price. Must be greater than 0.");
}


$check = $conn->prepare("SELECT room_id FROM rooms WHERE room_number=?");
$check->bind_param("s", $room_number);
$check->execute();
if($check->get_result()->num_rows > 0){
    die("Room number already exists.");
}


$stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, ac_type, capacity, pet_friendly, private_pool, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Available')");
$stmt->bind_param("sssiiid", $room_number, $room_type, $ac_type, $capacity, $pet_friendly, $private_pool, $price);

if($stmt->execute()){
    header("Location: rooms.php");
    exit();
} else {
    echo "Error adding room.";
}
?>