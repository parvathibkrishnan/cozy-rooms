<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$id          = $_POST['room_id'];
$room_number = $_POST['room_number'];
$room_type   = $_POST['room_type'];
$price       = $_POST['price'];


$stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, price=? WHERE room_id=?");
$stmt->bind_param("ssdi", $room_number, $room_type, $price, $id);

if ($stmt->execute()) {
    header("Location: rooms.php");
    exit();
} else {
    echo "Error updating room.";
}
?>