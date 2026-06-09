<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header("Location: ../staff-login.php");
    exit();
}
$user = getCurrentUser();

$totalReservations = $pdo->query("SELECT COUNT(*) FROM reservation")->fetchColumn();
$totalHotels       = $pdo->query("SELECT COUNT(*) FROM hotel WHERE IsActive=1")->fetchColumn();
$totalRooms        = $pdo->query("SELECT COUNT(*) FROM room WHERE IsActive=1")->fetchColumn();
$pendingPayments   = $pdo->query("SELECT COUNT(*) FROM payment WHERE Status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - LuxStay</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { background: #0f0f0f; margin: 0; }
        .dash-wrap {
            max-width: 1200px;
            margin: 80px auto 30px;   /* ← 80px top margin pushes content below nav */
            padding: 30px 24px;
        }
        .stats { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: rgba(0,0,0,0.6); border-left: 3px solid #00d8b2; padding: 20px; border-radius: 8px; }
        .stat-card .num { font-size: 2rem; font-weight: 700; color: #00d8b2; }
        .quick-links { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 30px; }
        .quick-link { background: rgba(0,216,178,0.1); padding: 12px 24px; border-radius: 8px; text-decoration: none; color: #fff; }
        .quick-link:hover { background: #00d8b2; color: #111; }
    </style>
</head>
<body>
        <?php include '_nav.php'; ?>
    <div style="height: 80px;"></div>  <!-- spacer to push content below absolute nav -->
    <div class="dash-wrap">
        <h2>Welcome, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h2>
        <div class="stats">
            <div class="stat-card"><div class="num"><?php echo $totalReservations; ?></div><div>Total Reservations</div></div>
            <div class="stat-card"><div class="num"><?php echo $totalHotels; ?></div><div>Active Hotels</div></div>
            <div class="stat-card"><div class="num"><?php echo $totalRooms; ?></div><div>Active Rooms</div></div>
            <div class="stat-card"><div class="num"><?php echo $pendingPayments; ?></div><div>Pending Payments</div></div>
        </div>
        <div class="quick-links">
            <a href="reservations.php" class="quick-link"><i class="fa fa-calendar"></i> Manage Reservations</a>
            <a href="hotels.php" class="quick-link"><i class="fa fa-building"></i> Manage Hotels</a>
            <a href="rooms.php" class="quick-link"><i class="fa fa-bed"></i> Manage Rooms</a>
            <a href="guests.php" class="quick-link"><i class="fa fa-users"></i> Manage Guests</a>
            <a href="../admin_reports.php" class="quick-link"><i class="fa fa-chart-line"></i> Reports</a>
        </div>
    </div>
</body>
</html>