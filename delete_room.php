<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../public/index.php");
    exit();
}

include "../config/db.php";
$id = intval($_GET['id']);


$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE room_id=? AND status IN ('Booked', 'Checked In')");
$stmt->bind_param("i", $id);
$stmt->execute();

if($stmt->get_result()->num_rows > 0){
    echo "<script>alert('Room has active bookings and cannot be deleted right now.'); window.location='rooms.php';</script>";
    exit();
}


$stmtDelete = $conn->prepare("DELETE FROM rooms WHERE room_id=?");
$stmtDelete->bind_param("i", $id);


try {
    if($stmtDelete->execute()){
        header("Location: rooms.php");
        exit();
    } else {
        echo "Error deleting room.";
    }
} catch (mysqli_sql_exception $e) {
    echo "<script>alert('Cannot delete room due to historical booking records. Consider hiding the room instead.'); window.location='rooms.php';</script>";
}
?>
