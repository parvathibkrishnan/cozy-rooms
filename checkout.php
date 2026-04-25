<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

$booking_id = intval($_GET['id']);
$today = date("Y-m-d");


$stmt = $conn->prepare("SELECT room_id FROM bookings WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $room_id = $row['room_id'];

    
    $stmtUpdate = $conn->prepare("UPDATE bookings SET status='Checked Out', actual_checkout=? WHERE booking_id=?");
    $stmtUpdate->bind_param("si", $today, $booking_id);
    $stmtUpdate->execute();

    
    $stmtRoom = $conn->prepare("UPDATE rooms SET status='Available' WHERE room_id=?");
    $stmtRoom->bind_param("i", $room_id);
    $stmtRoom->execute();
}

header("Location: bookings.php");
exit();
?>