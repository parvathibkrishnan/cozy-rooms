<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --sidebar-bg: #2c1e1a; 
        --sidebar-hover: #4a362e;
        --sidebar-active: #ffffff;
        --text-muted: #9ca3af;
        --body-bg: #f8f9fa;
    }

    
    body {
        display: flex;
        margin: 0;
        background-color: var(--body-bg);
        min-height: 100vh;
        font-family: 'Poppins', sans-serif;
    }

    .sidebar {
        width: 260px;
        min-width: 260px;
        background-color: var(--sidebar-bg);
        height: 100vh;
        position: sticky;
        top: 0;
        display: flex;
        flex-direction: column;
        color: white;
        box-sizing: border-box;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        z-index: 100;
    }

    .sidebar-header {
        padding: 35px 25px 30px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sidebar-header h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: white;
        letter-spacing: 0.5px;
    }

    .sidebar-header i {
        font-size: 24px;
        color: #d4d4d4;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        padding: 0 15px 20px 15px;
        flex-grow: 1; 
    }

    .sidebar-nav a {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        padding: 12px 16px;
        margin-bottom: 6px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }

    .sidebar-nav a i {
        width: 24px;
        font-size: 16px;
        margin-right: 10px;
        text-align: center;
        transition: color 0.2s ease;
    }

   
    .sidebar-nav a:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: white;
    }

    
    .sidebar-nav a.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--sidebar-active);
        border-left-color: #dcfce7; 
        font-weight: 600;
    }

    .sidebar-nav a.active i {
        color: #dcfce7;
    }

    .logout-btn {
        margin-top: auto !important; 
        color: #ef4444 !important; 
    }

    .logout-btn:hover {
        background-color: rgba(239, 68, 68, 0.1) !important;
        color: #f87171 !important;
    }

    
    .content, .main-content, .dashboard-container {
        flex-grow: 1;
        width: calc(100% - 260px);
        box-sizing: border-box;
        overflow-x: hidden;
    }

    
    @media (max-width: 768px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
            min-height: auto;
            position: relative;
            flex-direction: column;
            padding-bottom: 0;
        }

        .sidebar-header {
            padding: 20px;
            justify-content: center;
        }

        .sidebar-nav {
            flex-direction: row;
            overflow-x: auto;
            padding: 0 10px 10px 10px;
            white-space: nowrap;
        }

        .sidebar-nav a {
            margin-bottom: 0;
            margin-right: 8px;
            border-left: none;
            border-bottom: 3px solid transparent;
            border-radius: 6px;
        }

        .sidebar-nav a.active {
            border-left-color: transparent;
            border-bottom-color: #dcfce7;
        }

        .logout-btn {
            margin-top: 0 !important;
        }

        .content, .main-content, .dashboard-container {
            width: 100%;
        }
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-mug-hot"></i>
        <h2>Cozy Rooms</h2>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="rooms.php" class="<?php echo ($current_page == 'rooms.php') ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i> Manage Rooms
        </a>
        <a href="add_room_form.php" class="<?php echo ($current_page == 'add_room_form.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-square"></i> Add Room
        </a>
        <a href="book_room_form.php" class="<?php echo ($current_page == 'book_room_form.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Book Room
        </a>
        <a href="bookings.php" class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i> All Bookings
        </a>
        
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>