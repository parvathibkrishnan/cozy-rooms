<?php
$rooms = $_GET['rooms'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Booking Success</title>
<style>

body{
font-family:Poppins;
background:#f4f7f6;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.card{
background:white;
padding:40px;
border-radius:12px;
box-shadow:0 10px 30px rgba(0,0,0,0.1);
text-align:center;
}

h2{
color:#2c1e1a;
margin-bottom:15px;
}

.rooms{
font-weight:600;
font-size:18px;
margin:15px 0;
}

.btn{
display:inline-block;
padding:10px 20px;
background:#2c1e1a;
color:white;
text-decoration:none;
border-radius:6px;
margin-top:20px;
}

</style>
</head>

<body>

<div class="card">

<h2>Booking Successful</h2>

<p>Rooms Allocated:</p>

<div class="rooms">
<?php echo htmlspecialchars($rooms); ?>
</div>

<a class="btn" href="bookings.php">View Bookings</a>

</div>

</body>
</html>