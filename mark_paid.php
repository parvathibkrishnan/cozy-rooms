<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = intval($_GET['id']);


$stmt = $conn->prepare("UPDATE bookings SET payment_status='Paid' WHERE booking_id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    header("Location: bookings.php");
    exit();
}else{
    echo "Error updating payment status";
}
?>