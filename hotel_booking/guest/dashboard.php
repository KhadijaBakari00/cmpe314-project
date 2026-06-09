<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Guests only
if (!isLoggedIn() || getCurrentUser()['role'] !== 'customer') {
    header("Location: ../guest-login.php");
    exit();
}

$user = getCurrentUser();

// Fetch guest's reservations
$reservations = $pdo->prepare("
    SELECT res.*,
           r.RoomNumber, r.RoomType, r.PricePerNight,
           h.HotelName, h.City, h.StarRating,
           py.Status AS PaymentStatus, py.TransactionID
    FROM reservation res
    JOIN room r   ON res.RoomID  = r.RoomID
    JOIN hotel h  ON r.HotelID   = h.HotelID
    LEFT JOIN payment py ON res.ReservationID = py.ReservationID
    WHERE res.GuestID = ?
    ORDER BY res.BookingDate DESC
");
$reservations->execute([$user['guest_id']]);
$reservations = $reservations->fetchAll();

// Separate upcoming vs past
$today      = date('Y-m-d');
$upcoming   = array_filter($reservations, fn($r) => $r['CheckInDate'] >= $today && $r['Status'] !== 'cancelled');
$past       = array_filter($reservations, fn($r) => $r['CheckOutDate'] < $today || $r['Status'] === 'cancelled');

// Fetch unread notifications
$notifs = $pdo->prepare("SELECT * FROM notification WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 10");
$notifs->execute([$user['user_id']]);
$notifications = $notifs->fetchAll();
$unreadCount   = count(array_filter($notifications, fn($n) => !$n['IsRead']));

// Mark all notifications as read on page load
$pdo->prepare("UPDATE notification SET IsRead = 1 WHERE UserID = ?")->execute([$user['user_id']]);

// Stats
$totalSpent = array_sum(array_column(
    array_filter($reservations, fn($r) => $r['Status'] === 'confirmed'),
    'TotalCost'
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Dashboard — LuxStay Hotels</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <style>
        body { background: #141414; min-height: 100vh; }

        .dash-header {
            background: linear-gradient(135deg, rgba(0,216,178,0.08), rgba(0,0,0,0));
            border-bottom: 1px solid rgba(0,216,178,0.15);
            padding: 30px 0 0;
            margin-bottom: 30px;
        }
        .dash-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }
        .dash-greeting h1 { color: #fff; font-size: 1.8rem; margin: 0 0 4px; }
        .dash-greeting p  { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin: 0; }

        /* Stats row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 24px;
        }
        .stat-card {
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(0,216,178,0.15);
            border-radius: 10px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card .icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            background: rgba(0,216,178,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #00d8b2;
            flex-shrink: 0;
        }
        .stat-card .info .val { font-size: 1.6rem; font-weight: 700; color: #fff; }
        .stat-card .info .lbl { font-size: 0.78rem; color: rgba(255,255,255,0.4); margin-top: 2px; }

        /* Dashboard body */
        .dash-body {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 60px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
        }
        @media (max-width: 900px) { .dash-body { grid-template-columns: 1fr; } }

        /* Panel */
        .panel {
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(0,216,178,0.12);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid rgba(0,216,178,0.1);
        }
        .panel-header h3 { color: #00d8b2; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .panel-header .count {
            background: rgba(0,216,178,0.1);
            color: #00d8b2;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .panel-body { padding: 0; }

        /* Reservation rows */
        .res-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            padding: 18px 22px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            align-items: center;
            transition: background 0.2s;
        }
        .res-item:last-child { border-bottom: none; }
        .res-item:hover { background: rgba(0,216,178,0.04); }

        .res-icon {
            width: 42px; height: 42px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .res-icon.confirmed { background: rgba(0,216,178,0.12); color: #00d8b2; }
        .res-icon.pending   { background: rgba(240,180,41,0.12); color: #f0b429; }
        .res-icon.cancelled { background: rgba(233,42,103,0.12); color: #e92a67; }
        .res-icon.completed { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); }

        .res-info .hotel { color: #fff; font-size: 0.95rem; font-weight: 500; margin-bottom: 3px; }
        .res-info .meta  { color: rgba(255,255,255,0.4); font-size: 0.8rem; }
        .res-info .meta i { color: #00d8b2; margin-right: 3px; }

        .res-right { text-align: right; }
        .res-right .cost { color: #00d8b2; font-weight: 600; font-size: 1rem; }
        .res-right .dates { color: rgba(255,255,255,0.35); font-size: 0.78rem; margin-top: 3px; }

        .status-pill {
            display: inline-block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 5px;
        }
        .status-pill.confirmed { background: rgba(0,216,178,0.12); color: #00d8b2; border: 1px solid rgba(0,216,178,0.3); }
        .status-pill.pending   { background: rgba(240,180,41,0.12); color: #f0b429; border: 1px solid rgba(240,180,41,0.3); }
        .status-pill.cancelled { background: rgba(233,42,103,0.12); color: #e92a67; border: 1px solid rgba(233,42,103,0.3); }
        .status-pill.completed { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.5); border: 1px solid rgba(255,255,255,0.1); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255,255,255,0.3);
        }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 12px; color: rgba(0,216,178,0.2); }
        .empty-state a { color: #00d8b2; text-decoration: none; }

        /* Notifications sidebar */
        .notif-item {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.5;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item .time { color: rgba(255,255,255,0.25); font-size: 0.75rem; margin-top: 4px; }
        .notif-item.unread { background: rgba(0,216,178,0.04); border-left: 2px solid #00d8b2; }

        /* Quick actions */
        .quick-actions { display: flex; flex-direction: column; gap: 10px; padding: 16px; }
        .quick-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            color: #fff; text-decoration: none; font-size: 0.9rem;
            transition: all 0.2s;
        }
        .quick-btn:hover { background: rgba(0,216,178,0.08); border-color: rgba(0,216,178,0.3); color: #00d8b2; }
        .quick-btn i { color: #00d8b2; width: 18px; text-align: center; }
    </style>
</head>
<body>

<nav class="main-nav" id="main-nav">
    <div class="content-wrapper-sm">
        <a href="../index.php" class="navbar-brand">LuxStay Hotels</a>
        <div id="menu-button">
            <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
        </div>
        <ul class="nav-links">
            <li><a href="../rooms.php">Rooms</a></li>
            <li><a href="dashboard.php" class="auth-nav-item"><i class="fa fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></a></li>
            <li><a href="../logout.php" class="auth-nav-item"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Dashboard Header -->
<div class="dash-header" style="padding-top: 80px;">
    <div class="dash-header-inner">
        <div class="dash-greeting">
            <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h1>
            <p><?php echo date('l, d F Y'); ?> &nbsp;·&nbsp; <?php echo count($upcoming); ?> upcoming reservation<?php echo count($upcoming) !== 1 ? 's' : ''; ?></p>
        </div>
        <a href="../rooms.php" class="btn btn-outline-teal" style="padding: 0.6rem 1.8rem; margin: 0; font-size: 0.9rem;">
            <i class="fa fa-plus"></i> Book a Room
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
        <div class="info">
            <div class="val"><?php echo count($upcoming); ?></div>
            <div class="lbl">Upcoming Stays</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa fa-history"></i></div>
        <div class="info">
            <div class="val"><?php echo count($reservations); ?></div>
            <div class="lbl">Total Reservations</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa fa-money"></i></div>
        <div class="info">
            <div class="val">€<?php echo number_format($totalSpent, 0); ?></div>
            <div class="lbl">Total Spent</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fa fa-bell"></i></div>
        <div class="info">
            <div class="val"><?php echo $unreadCount; ?></div>
            <div class="lbl">New Notifications</div>
        </div>
    </div>
</div>

<!-- Dashboard Body -->
<div class="dash-body">

    <!-- Main column -->
    <div>
        <!-- Upcoming reservations -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa fa-calendar"></i> Upcoming Reservations</h3>
                <span class="count"><?php echo count($upcoming); ?></span>
            </div>
            <div class="panel-body">
                <?php if (empty($upcoming)): ?>
                    <div class="empty-state">
                        <i class="fa fa-bed"></i>
                        <p>No upcoming stays.<br><a href="../rooms.php">Browse rooms →</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcoming as $res): ?>
                    <div class="res-item">
                        <div class="res-icon <?php echo $res['Status']; ?>">
                            <i class="fa fa-building"></i>
                        </div>
                        <div class="res-info">
                            <div class="hotel"><?php echo htmlspecialchars($res['HotelName']); ?> — <?php echo ucfirst($res['RoomType']); ?></div>
                            <div class="meta">
                                <i class="fa fa-map-marker"></i><?php echo htmlspecialchars($res['City']); ?>
                                &nbsp;·&nbsp;
                                <i class="fa fa-bed"></i>Room <?php echo htmlspecialchars($res['RoomNumber']); ?>
                                &nbsp;·&nbsp;
                                <i class="fa fa-users"></i><?php echo $res['NumGuests']; ?> guest<?php echo $res['NumGuests'] > 1 ? 's' : ''; ?>
                            </div>
                            <span class="status-pill <?php echo $res['Status']; ?>"><?php echo $res['Status']; ?></span>
                        </div>
                        <div class="res-right">
                            <div class="cost">€<?php echo number_format($res['TotalCost'], 2); ?></div>
                            <div class="dates">
                                <?php echo date('d M', strtotime($res['CheckInDate'])); ?> →
                                <?php echo date('d M Y', strtotime($res['CheckOutDate'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past reservations -->
        <?php if (!empty($past)): ?>
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa fa-history"></i> Past Reservations</h3>
                <span class="count"><?php echo count($past); ?></span>
            </div>
            <div class="panel-body">
                <?php foreach ($past as $res): ?>
                <div class="res-item">
                    <div class="res-icon <?php echo $res['Status']; ?>">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="res-info">
                        <div class="hotel"><?php echo htmlspecialchars($res['HotelName']); ?> — <?php echo ucfirst($res['RoomType']); ?></div>
                        <div class="meta">
                            <i class="fa fa-map-marker"></i><?php echo htmlspecialchars($res['City']); ?>
                            &nbsp;·&nbsp;
                            <i class="fa fa-bed"></i>Room <?php echo htmlspecialchars($res['RoomNumber']); ?>
                        </div>
                        <span class="status-pill <?php echo $res['Status']; ?>"><?php echo $res['Status']; ?></span>
                    </div>
                    <div class="res-right">
                        <div class="cost">€<?php echo number_format($res['TotalCost'], 2); ?></div>
                        <div class="dates">
                            <?php echo date('d M', strtotime($res['CheckInDate'])); ?> →
                            <?php echo date('d M Y', strtotime($res['CheckOutDate'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Quick actions -->
        <div class="panel" style="margin-bottom: 20px;">
            <div class="panel-header"><h3><i class="fa fa-bolt"></i> Quick Actions</h3></div>
            <div class="quick-actions">
                <a href="../rooms.php" class="quick-btn"><i class="fa fa-search"></i> Browse Rooms</a>
                <a href="../rooms.php?city=Limassol" class="quick-btn"><i class="fa fa-map-marker"></i> Hotels in Limassol</a>
                <a href="../rooms.php?city=Paphos" class="quick-btn"><i class="fa fa-map-marker"></i> Hotels in Paphos</a>
                <a href="../rooms.php?room_type=suite" class="quick-btn"><i class="fa fa-star"></i> View Suites</a>
                <a href="../logout.php" class="quick-btn" style="color: rgba(255,255,255,0.35);"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </div>

        <!-- Notifications -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa fa-bell"></i> Notifications</h3>
                <?php if ($unreadCount > 0): ?>
                    <span class="count"><?php echo $unreadCount; ?> new</span>
                <?php endif; ?>
            </div>
            <div class="panel-body">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state" style="padding: 24px;">
                        <i class="fa fa-bell-slash"></i>
                        <p style="font-size:0.85rem;">No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item <?php echo !$notif['IsRead'] ? 'unread' : ''; ?>">
                        <?php echo htmlspecialchars($notif['Message']); ?>
                        <div class="time"><?php echo date('d M Y, H:i', strtotime($notif['CreatedAt'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="../js/menu.js"></script>
<script src="../js/nav.js"></script>
</body>
</html>