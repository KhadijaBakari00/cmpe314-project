<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Only staff allowed
if (!isLoggedIn() || getCurrentUser()['role'] !== 'staff') {
    header("Location: ../staff-login.php");
    exit();
}

$user = getCurrentUser();
$error = '';
$success = '';

// Handle status update from the table
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            if (isset($_POST['update_status'])) {
                $stmt = $pdo->prepare("UPDATE reservation SET Status = ? WHERE ReservationID = ?");
                $stmt->execute([$_POST['new_status'], $_POST['reservation_id']]);
                $success = 'Reservation status updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch upcoming reservations (no assignments table)
$recentReservations = $pdo->prepare("
    SELECT r.ReservationID, g.FullName AS GuestName, h.HotelName, 
           r.CheckInDate, r.CheckOutDate, r.NumGuests, r.TotalCost, r.Status
    FROM reservation r
    JOIN guest g ON r.GuestID = g.GuestID
    JOIN room rm ON r.RoomID = rm.RoomID
    JOIN hotel h ON rm.HotelID = h.HotelID
    WHERE r.CheckInDate >= CURDATE()
    ORDER BY r.CheckInDate ASC
    LIMIT 10
");
$recentReservations->execute();
$recentReservations = $recentReservations->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalReservations = $pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn();
$pendingPayments   = $pdo->query("SELECT COUNT(*) FROM payment WHERE Status='pending'")->fetchColumn();
$todayCheckins     = $pdo->query("SELECT COUNT(*) FROM reservation WHERE CheckInDate = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - LuxStay Hotels</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            color: #e0e0e0;
            background: #0f0f0f;
        }
        .img-absolute {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.4;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(0,0,0,0.7);
            border-radius: 8px;
            color: white;
        }
        .dashboard-section {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0,0,0,0.5);
            border-radius: 8px;
            border-left: 4px solid #00d8b2;
        }
        .section-title {
            color: #00d8b2;
            border-bottom: 2px solid #00d8b2;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .status-panel {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .status-item {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            padding: 15px;
            background: rgba(0,216,178,0.1);
            border-radius: 8px;
        }
        .status-item h4 {
            color: #00d8b2;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #00d8b2;
        }
        th {
            background-color: rgba(0,216,178,0.2);
            color: #00d8b2;
        }
        .status-pending {
            color: #FFC107;
            font-weight: bold;
        }
        .status-confirmed {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-cancelled {
            color: #F44336;
            font-weight: bold;
        }
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .quick-action {
            padding: 10px 15px;
            background: rgba(0,216,178,0.2);
            border-radius: 5px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        .quick-action:hover {
            background: rgba(0,216,178,0.4);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .form-inline {
            display: inline-block;
        }
        .form-inline select {
            width: auto;
            display: inline-block;
            margin-right: 5px;
            padding: 5px;
            background: rgba(255,255,255,0.1);
            border: 1px solid #00d8b2;
            border-radius: 4px;
            color: white;
        }
        .main-nav {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .content-wrapper-sm {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            color: #00d8b2;
            font-size: 1.5em;
            font-weight: bold;
            text-decoration: none;
        }
        .nav-links {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .nav-links li {
            margin-left: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.3s;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: #00d8b2;
        }
        #menu-button {
            display: none;
            cursor: pointer;
        }
        .bar1, .bar2, .bar3 {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 5px 0;
            transition: 0.4s;
        }
        @media (max-width: 768px) {
            #menu-button {
                display: block;
            }
            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 60px;
                left: 0;
                background: rgba(0, 0, 0, 0.9);
            }
            .nav-links.active {
                display: flex;
            }
            .nav-links li {
                margin: 10px 0;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <img src="../img/hotel-desk.jpg" class="img-absolute" alt="Background">

    <!-- Self-contained navigation (no external includes) -->
    <nav class="main-nav" id="main-nav">
        <div class="content-wrapper-sm">
            <a href="../index.php" class="navbar-brand">LuxStay Staff</a>
            <div id="menu-button">
                <div class="bar1"></div>
                <div class="bar2"></div>
                <div class="bar3"></div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="../admin/reservations.php">View All Reservations</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h2>
        <p>Position: <strong>Staff</strong></p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="status-panel">
            <div class="status-item">
                <h4>Total Reservations</h4>
                <p><?php echo $totalReservations; ?></p>
            </div>
            <div class="status-item">
                <h4>Pending Payments</h4>
                <p><?php echo $pendingPayments; ?></p>
            </div>
            <div class="status-item">
                <h4>Today's Check-ins</h4>
                <p><?php echo $todayCheckins; ?></p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="../admin/reservations.php" class="quick-action"><i class="fa fa-calendar"></i> Manage Reservations</a>
            <a href="../rooms.php" class="quick-action"><i class="fa fa-bed"></i> Browse Rooms</a>
        </div>

        <div class="dashboard-section">
            <h3 class="section-title">Upcoming Reservations</h3>
            <?php if ($recentReservations): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Guest</th>
                            <th>Hotel</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Guests</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReservations as $res): ?>
                        <tr>
                            <td><?php echo $res['ReservationID']; ?></td>
                            <td><?php echo htmlspecialchars($res['GuestName']); ?></td>
                            <td><?php echo htmlspecialchars($res['HotelName']); ?></td>
                            <td><?php echo $res['CheckInDate']; ?></td>
                            <td><?php echo $res['CheckOutDate']; ?></td>
                            <td><?php echo $res['NumGuests']; ?></td>
                            <td>€<?php echo number_format($res['TotalCost'], 2); ?></td>
                            <td class="status-<?php echo strtolower($res['Status']); ?>">
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN); ?>">
                                    <input type="hidden" name="reservation_id" value="<?php echo $res['ReservationID']; ?>">
                                    <select name="new_status">
                                        <option value="pending" <?php echo $res['Status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $res['Status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $res['Status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="completed" <?php echo $res['Status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-teal">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No upcoming reservations found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#menu-button').click(function() {
                $('.nav-links').toggleClass('active');
                $('.bar1').toggleClass('rotate-45 translate-y-2');
                $('.bar2').toggleClass('opacity-0');
                $('.bar3').toggleClass('-rotate-45 -translate-y-2');
            });
        });
    </script>
</body>
</html>