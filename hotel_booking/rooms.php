<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// ── Collect filter inputs ──────────────────────────────────
$city      = isset($_GET['city'])      ? trim($_GET['city'])      : '';
$hotelId   = isset($_GET['hotel_id'])  ? (int)$_GET['hotel_id']   : 0;
$roomType  = isset($_GET['room_type']) ? trim($_GET['room_type'])  : '';
$checkIn   = isset($_GET['check_in'])  ? trim($_GET['check_in'])   : '';
$checkOut  = isset($_GET['check_out']) ? trim($_GET['check_out'])  : '';
$guests    = isset($_GET['guests'])    ? (int)$_GET['guests']      : 1;
$maxPrice  = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// ── Build dynamic query ───────────────────────────────────
$where  = ["r.IsActive = 1", "h.IsActive = 1"];
$params = [];

if ($city) {
    $where[]  = "h.City = ?";
    $params[] = $city;
}
if ($hotelId) {
    $where[]  = "h.HotelID = ?";
    $params[] = $hotelId;
}
if ($roomType) {
    $where[]  = "r.RoomType = ?";
    $params[] = $roomType;
}
if ($guests > 0) {
    $where[]  = "r.MaxGuests >= ?";
    $params[] = $guests;
}
if ($maxPrice > 0) {
    $where[]  = "r.PricePerNight <= ?";
    $params[] = $maxPrice;
}
// Exclude rooms already reserved for the chosen dates
if ($checkIn && $checkOut) {
    $where[] = "r.RoomID NOT IN (
        SELECT res.RoomID FROM reservation res
        WHERE res.Status NOT IN ('cancelled')
          AND res.CheckInDate  < ?
          AND res.CheckOutDate > ?
    )";
    $params[] = $checkOut;
    $params[] = $checkIn;
}

$whereSQL = implode(' AND ', $where);

$sql = "SELECT r.*, h.HotelName, h.City, h.Country, h.StarRating, h.ImagePath AS HotelImage
        FROM room r
        JOIN hotel h ON r.HotelID = h.HotelID
        WHERE $whereSQL
        ORDER BY h.StarRating DESC, r.PricePerNight ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
    $dbError = $e->getMessage();
}

// ── Fetch hotel list for filter dropdown ──────────────────
$hotels = $pdo->query("SELECT HotelID, HotelName, City FROM hotel WHERE IsActive = 1 ORDER BY HotelName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Rooms — LuxStay Hotels</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        /* ── Page header ── */
        .page-header {
            position: relative;
            background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
                        url('img/hotel-rooms-header.jpg') no-repeat center center;
            background-size: cover;
            color: #fff;
            padding: 90px 20px 60px;
            text-align: center;
        }
        .page-header h1 { font-size: 2.8rem; margin-bottom: 10px; }
        .page-header p  { color: rgba(255,255,255,0.6); }

        /* ── Layout: sidebar + results ── */
        .rooms-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        @media (max-width: 900px) { .rooms-layout { grid-template-columns: 1fr; } }

        /* ── Filter sidebar ── */
        .filter-panel {
            background: rgba(0,0,0,0.7);
            border: 1px solid rgba(0,216,178,0.2);
            border-radius: 12px;
            padding: 28px 22px;
            height: fit-content;
            position: sticky;
            top: 80px;
        }
        .filter-panel h3 {
            color: #00d8b2;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,216,178,0.2);
        }
        .filter-group { margin-bottom: 20px; }
        .filter-group label {
            display: block;
            color: rgba(255,255,255,0.55);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 6px;
            color: #fff;
            padding: 9px 12px;
            font-size: 0.9rem;
            outline: none;
            transition: border 0.2s;
        }
        .filter-group input:focus,
        .filter-group select:focus { border-color: #00d8b2; }
        .filter-group select option { background: #1a1a1a; }
        .filter-group input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        .filter-group input[type="range"] {
            padding: 0;
            border: none;
            background: transparent;
            accent-color: #00d8b2;
            cursor: pointer;
        }
        .price-display { color: #00d8b2; font-size: 0.9rem; text-align: right; margin-top: 4px; }
        .btn-filter {
            width: 100%;
            background: linear-gradient(135deg, #00d8b2, #009688);
            color: #111;
            border: none;
            border-radius: 6px;
            padding: 11px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity 0.2s;
        }
        .btn-filter:hover { opacity: 0.85; }
        .btn-reset {
            width: 100%;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.4);
            border-radius: 6px;
            padding: 9px;
            font-size: 0.85rem;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
        }
        .btn-reset:hover { border-color: #e92a67; color: #e92a67; }

        /* ── Results area ── */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .results-header h2 { color: #fff; font-size: 1.4rem; }
        .results-count { color: rgba(255,255,255,0.4); font-size: 0.9rem; }

        /* ── Room cards ── */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .room-card {
            background: rgba(15,15,15,0.9);
            border: 1px solid rgba(0,216,178,0.12);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 35px rgba(0,216,178,0.12);
            border-color: rgba(0,216,178,0.3);
        }
        .room-card .room-img {
            width: 100%; height: 180px;
            object-fit: cover; display: block;
        }
        .room-card .room-img-placeholder {
            width: 100%; height: 180px;
            background: linear-gradient(135deg, #0d2020, #142828);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: rgba(0,216,178,0.25);
        }
        .room-card .room-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .room-card .hotel-tag {
            display: flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.4); font-size: 0.78rem;
            margin-bottom: 8px;
        }
        .room-card .hotel-tag .stars { color: #f0b429; letter-spacing: -1px; }
        .room-card h3 { color: #fff; font-size: 1.05rem; margin-bottom: 4px; }
        .room-card .room-meta {
            display: flex; gap: 14px;
            margin: 10px 0 12px;
            font-size: 0.8rem;
        }
        .room-card .room-meta span { color: rgba(255,255,255,0.45); }
        .room-card .room-meta i { color: #00d8b2; margin-right: 4px; }
        .room-card p.room-desc { color: rgba(255,255,255,0.55); font-size: 0.85rem; line-height: 1.6; flex: 1; margin-bottom: 16px; }
        .room-card .room-foot {
            display: flex; justify-content: space-between; align-items: center;
            border-top: 1px solid rgba(255,255,255,0.07); padding-top: 14px;
        }
        .room-card .price { color: #00d8b2; }
        .room-card .price .amount { font-size: 1.4rem; font-weight: 700; }
        .room-card .price small { color: rgba(255,255,255,0.35); font-size: 0.75rem; }
        .type-badge {
            display: inline-block;
            background: rgba(0,216,178,0.1);
            border: 1px solid rgba(0,216,178,0.3);
            color: #00d8b2;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 8px;
        }

        /* ── No results ── */
        .no-results {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.35);
        }
        .no-results i { font-size: 3rem; display: block; margin-bottom: 16px; color: rgba(0,216,178,0.2); }
    </style>
</head>
<body>

<nav class="main-nav" id="main-nav">
    <div class="content-wrapper-sm">
        <a href="index.php" class="navbar-brand">LuxStay Hotels</a>
        <div id="menu-button">
            <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
        </div>
        <ul class="nav-links">
            <li><a href="index.php#about">About</a></li>
            <li><a href="rooms.php" class="active">Rooms</a></li>
            <li><a href="index.php#how">How It Works</a></li>
            <?php if (isLoggedIn()): ?>
                <?php $user = getCurrentUser(); ?>
                <li><a href="<?php echo $user['role'] === 'customer' ? 'guest/dashboard.php' : 'staff/dashboard.php'; ?>" class="auth-nav-item">
                    <i class="fa fa-user"></i> <?php echo htmlspecialchars($user['username']); ?>
                </a></li>
                <li><a href="logout.php" class="auth-nav-item"><i class="fa fa-sign-out"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="guest-login.php" class="auth-nav-item"><i class="fa fa-user"></i> Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<header class="page-header">
    <h1>Browse Hotel Rooms</h1>
    <p>Find the perfect room across <?php echo count($hotels); ?> hotels in Cyprus</p>
</header>

<div class="rooms-layout">

    <!-- ── FILTER SIDEBAR ── -->
    <aside class="filter-panel">
        <h3><i class="fa fa-sliders"></i> Filter Rooms</h3>
        <form method="GET" action="rooms.php" id="filterForm">

            <div class="filter-group">
                <label><i class="fa fa-map-marker"></i> City</label>
                <select name="city">
                    <option value="">All Cities</option>
                    <?php foreach (['Limassol','Paphos','Kyrenia','Nicosia','Ayia Napa'] as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo $city === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fa fa-hotel"></i> Hotel</label>
                <select name="hotel_id">
                    <option value="">All Hotels</option>
                    <?php foreach ($hotels as $h): ?>
                        <option value="<?php echo $h['HotelID']; ?>" <?php echo $hotelId == $h['HotelID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['HotelName']); ?> (<?php echo htmlspecialchars($h['City']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fa fa-bed"></i> Room Type</label>
                <select name="room_type">
                    <option value="">All Types</option>
                    <?php foreach (['single','double','suite','deluxe','family'] as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $roomType === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fa fa-calendar"></i> Check-In</label>
                <input type="date" name="check_in" id="fi_checkin"
                       value="<?php echo htmlspecialchars($checkIn); ?>"
                       min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="filter-group">
                <label><i class="fa fa-calendar-check-o"></i> Check-Out</label>
                <input type="date" name="check_out" id="fi_checkout"
                       value="<?php echo htmlspecialchars($checkOut); ?>"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>

            <div class="filter-group">
                <label><i class="fa fa-users"></i> Guests</label>
                <input type="number" name="guests" min="1" max="10"
                       value="<?php echo $guests ?: 1; ?>">
            </div>

            <div class="filter-group">
                <label><i class="fa fa-money"></i> Max Price/Night: <span id="priceVal">€<?php echo $maxPrice ?: 500; ?></span></label>
                <input type="range" name="max_price" id="priceRange"
                       min="50" max="500" step="10"
                       value="<?php echo $maxPrice ?: 500; ?>">
            </div>

            <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Apply Filters</button>
            <a href="rooms.php"><button type="button" class="btn-reset">Reset Filters</button></a>
        </form>
    </aside>

    <!-- ── RESULTS ── -->
    <div class="results-area">
        <div class="results-header">
            <h2>Available Rooms</h2>
            <span class="results-count"><?php echo count($rooms); ?> room<?php echo count($rooms) !== 1 ? 's' : ''; ?> found</span>
        </div>

        <?php if (isset($dbError)): ?>
            <div style="background:rgba(233,42,103,0.1);border:1px solid #e92a67;padding:16px;border-radius:8px;color:#ff6b9d;margin-bottom:20px;">
                DB Error: <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>

        <div class="room-grid">
            <?php if (empty($rooms)): ?>
                <div class="no-results">
                    <i class="fa fa-search"></i>
                    <p>No rooms match your filters.<br><a href="rooms.php" style="color:#00d8b2;">Clear filters</a> to see all available rooms.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                <div class="room-card astonish" data-animation="fadeInUp">
                    <?php if (!empty($room['HotelImage'])): ?>
                        <img class="room-img" src="<?php echo htmlspecialchars($room['HotelImage']); ?>"
                             alt="<?php echo htmlspecialchars($room['HotelName']); ?>"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="room-img-placeholder" style="display:none"><i class="fa fa-bed"></i></div>
                    <?php else: ?>
                        <div class="room-img-placeholder"><i class="fa fa-bed"></i></div>
                    <?php endif; ?>

                    <div class="room-body">
                        <div class="hotel-tag">
                            <i class="fa fa-building"></i>
                            <?php echo htmlspecialchars($room['HotelName']); ?> &nbsp;·&nbsp;
                            <i class="fa fa-map-marker"></i><?php echo htmlspecialchars($room['City']); ?>
                            <span class="stars">
                                <?php for ($i = 0; $i < $room['StarRating']; $i++) echo '★'; ?>
                            </span>
                        </div>

                        <span class="type-badge"><?php echo ucfirst($room['RoomType']); ?></span>
                        <h3>Room <?php echo htmlspecialchars($room['RoomNumber']); ?></h3>

                        <div class="room-meta">
                            <span><i class="fa fa-users"></i>Up to <?php echo $room['MaxGuests']; ?> guests</span>
                            <span><i class="fa fa-check-circle"></i><?php echo $room['AvailableRooms']; ?> available</span>
                        </div>

                        <p class="room-desc"><?php echo htmlspecialchars($room['Description']); ?></p>

                        <div class="room-foot">
                            <div class="price">
                                <div class="amount">€<?php echo number_format($room['PricePerNight'], 2); ?></div>
                                <small>per night</small>
                            </div>
                            <?php
                            // Build reservation URL with carry-forward of dates/guests
                            $resUrl = 'reservation.php?room_id=' . $room['RoomID'];
                            if ($checkIn)  $resUrl .= '&check_in='  . urlencode($checkIn);
                            if ($checkOut) $resUrl .= '&check_out=' . urlencode($checkOut);
                            if ($guests)   $resUrl .= '&guests='    . $guests;
                            ?>
                            <a href="<?php echo $resUrl; ?>" class="btn btn-primary" style="padding:9px 18px;font-size:0.88rem;">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <div class="content-wrapper-sm footer-container">
        <div class="footer-grid">
            <div class="footer-section">
                <h3 class="footer-title">LuxStay</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fa fa-home"></i> Home</a></li>
                    <li><a href="rooms.php"><i class="fa fa-bed"></i> All Rooms</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Account</h3>
                <ul class="footer-links">
                    <li><a href="guest-login.php"><i class="fa fa-sign-in"></i> Login</a></li>
                    <li><a href="guest-signup.php"><i class="fa fa-user-plus"></i> Sign Up</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Contact</h3>
                <div class="footer-contact">
                    <p><i class="fa fa-phone"></i> +357 25 000 000</p>
                    <p><i class="fa fa-envelope"></i> info@luxstay.cy</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <small>© 2025 LuxStay Hotels. All rights reserved.</small>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="js/menu.js"></script>
<script src="js/astonish.js"></script>
<script src="js/nav.js"></script>
<script>
    // Live price range label
    const range = document.getElementById('priceRange');
    const val   = document.getElementById('priceVal');
    range?.addEventListener('input', () => { val.textContent = '€' + range.value; });

    // Check-in → auto-bump check-out minimum
    document.getElementById('fi_checkin')?.addEventListener('change', function () {
        const next = new Date(this.value);
        next.setDate(next.getDate() + 1);
        const out = document.getElementById('fi_checkout');
        out.min = next.toISOString().split('T')[0];
        if (out.value && out.value <= this.value) out.value = '';
    });
</script>
</body>
</html>