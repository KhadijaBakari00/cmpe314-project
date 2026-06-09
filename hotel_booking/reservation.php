<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// ── Must be logged in as a guest ──────────────────────────
if (!isLoggedIn() || getCurrentUser()['role'] !== 'customer') {
    $redirect = urlencode('reservation.php?' . http_build_query($_GET));
    header("Location: guest-login.php?redirect=$redirect&error=Please log in to make a reservation");
    exit();
}

$user = getCurrentUser();

// ── Get room_id from URL ──────────────────────────────────
if (!isset($_GET['room_id'])) {
    header("Location: rooms.php");
    exit();
}

$roomId   = (int)$_GET['room_id'];
$checkIn  = $_GET['check_in']  ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests   = (int)($_GET['guests'] ?? 1);

// ── Fetch room + hotel details ────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT r.*, h.HotelName, h.City, h.Country, h.StarRating, h.Location AS HotelLocation, h.ImagePath AS HotelImage
                           FROM room r
                           JOIN hotel h ON r.HotelID = h.HotelID
                           WHERE r.RoomID = ? AND r.IsActive = 1 AND h.IsActive = 1");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch();
    if (!$room) {
        header("Location: rooms.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching room: " . $e->getMessage());
}

// ── Handle form submission ────────────────────────────────
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $checkIn       = trim($_POST['check_in']);
        $checkOut      = trim($_POST['check_out']);
        $numGuests     = (int)$_POST['num_guests'];
        $specialReq    = trim($_POST['special_requests'] ?? '');

        // ── Validation ────────────────────────────────────
        $today = date('Y-m-d');
        if (empty($checkIn) || empty($checkOut)) {
            $error = "Please select both check-in and check-out dates.";
        } elseif ($checkIn < $today) {
            $error = "Check-in date cannot be in the past.";
        } elseif ($checkOut <= $checkIn) {
            $error = "Check-out must be after check-in.";
        } elseif ($numGuests < 1 || $numGuests > $room['MaxGuests']) {
            $error = "Number of guests must be between 1 and {$room['MaxGuests']}.";
        } else {
            // ── Check availability for these exact dates ──
            $availStmt = $pdo->prepare("SELECT COUNT(*) FROM reservation
                                        WHERE RoomID = ? AND Status NOT IN ('cancelled')
                                          AND CheckInDate < ? AND CheckOutDate > ?");
            $availStmt->execute([$roomId, $checkOut, $checkIn]);
            if ($availStmt->fetchColumn() > 0) {
                $error = "Sorry, this room is already booked for the selected dates. Please choose different dates.";
            }
        }

        if (!$error) {
            // ── Calculate total cost ──────────────────────
            $nights    = (new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
            $totalCost = $nights * $room['PricePerNight'];

            try {
                $pdo->beginTransaction();

                // Insert reservation
                $stmt = $pdo->prepare("INSERT INTO reservation
                    (GuestID, RoomID, CheckInDate, CheckOutDate, NumGuests, TotalCost, Status, SpecialRequests)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
                $stmt->execute([
                    $user['guest_id'],
                    $roomId,
                    $checkIn,
                    $checkOut,
                    $numGuests,
                    $totalCost,
                    $specialReq
                ]);
                $reservationId = $pdo->lastInsertId();

                // Decrement available rooms
                $pdo->prepare("UPDATE room SET AvailableRooms = AvailableRooms - 1 WHERE RoomID = ?")
                    ->execute([$roomId]);

                $pdo->commit();

                // Store in session for payment page
                $_SESSION['current_reservation'] = [
                    'id'    => $reservationId,
                    'total' => $totalCost
                ];

                // Send notification to guest
                sendNotification($user['user_id'],
                    "Your reservation at {$room['HotelName']} (Room {$room['RoomNumber']}) has been received. " .
                    "Check-in: $checkIn. Booking ID: #$reservationId — pending payment.");

                header("Location: payment.php");
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Booking failed: " . $e->getMessage();
            }
        }
    }
}

// ── Calculate price preview ───────────────────────────────
$nights    = 0;
$totalCost = 0;
if ($checkIn && $checkOut && $checkOut > $checkIn) {
    $nights    = (new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
    $totalCost = $nights * $room['PricePerNight'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Room — LuxStay Hotels</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .page-header {
            position: relative;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                        url('img/hotel-rooms-header.jpg') no-repeat center center;
            background-size: cover;
            color: #fff; padding: 80px 20px 50px; text-align: center;
        }
        .page-header h1 { font-size: 2.4rem; margin-bottom: 8px; }
        .page-header p  { color: rgba(255,255,255,0.55); }

        .reservation-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }
        @media (max-width: 900px) { .reservation-layout { grid-template-columns: 1fr; } }

        /* ── Form panel ── */
        .form-panel {
            background: rgba(0,0,0,0.7);
            border: 1px solid rgba(0,216,178,0.2);
            border-radius: 12px;
            padding: 32px;
        }
        .form-panel h2 { color: #00d8b2; margin-bottom: 24px; font-size: 1.4rem; }
        .form-section-title {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 24px 0 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .res-form .form-group { margin-bottom: 18px; }
        .res-form .form-group label {
            display: block; color: rgba(255,255,255,0.6);
            font-size: 0.82rem; margin-bottom: 7px;
        }
        .res-form .form-group input,
        .res-form .form-group select,
        .res-form .form-group textarea {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 7px;
            color: #fff;
            padding: 11px 14px;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        .res-form .form-group input:focus,
        .res-form .form-group select:focus,
        .res-form .form-group textarea:focus { border-color: #00d8b2; }
        .res-form .form-group input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        .res-form .form-group select option { background: #1a1a1a; }
        .res-form .form-group textarea { resize: vertical; min-height: 90px; }
        .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 480px) { .date-row { grid-template-columns: 1fr; } }
        .btn-reserve {
            width: 100%;
            background: linear-gradient(135deg, #00d8b2, #009688);
            color: #111;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            margin-top: 20px;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-reserve:hover { opacity: 0.87; transform: translateY(-2px); }

        /* ── Summary sidebar ── */
        .summary-panel {
            position: sticky;
            top: 90px;
            height: fit-content;
        }
        .room-summary-card {
            background: rgba(0,0,0,0.75);
            border: 1px solid rgba(0,216,178,0.2);
            border-radius: 12px;
            overflow: hidden;
        }
        .room-summary-card .sum-img {
            width: 100%; height: 180px;
            object-fit: cover; display: block;
        }
        .room-summary-card .sum-img-placeholder {
            width: 100%; height: 180px;
            background: linear-gradient(135deg, #0d2020, #142828);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: rgba(0,216,178,0.2);
        }
        .sum-body { padding: 22px; }
        .sum-body .hotel-name { color: rgba(255,255,255,0.45); font-size: 0.82rem; margin-bottom: 4px; }
        .sum-body .room-title { color: #fff; font-size: 1.2rem; font-weight: 600; margin-bottom: 8px; }
        .sum-body .type-badge {
            display: inline-block;
            background: rgba(0,216,178,0.1); border: 1px solid rgba(0,216,178,0.3);
            color: #00d8b2; font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 1px; padding: 2px 10px; border-radius: 20px; margin-bottom: 14px;
        }
        .sum-body .room-feats { display: flex; gap: 16px; margin-bottom: 16px; }
        .sum-body .feat { color: rgba(255,255,255,0.45); font-size: 0.8rem; }
        .sum-body .feat i { color: #00d8b2; margin-right: 4px; }
        .price-breakdown {
            border-top: 1px solid rgba(255,255,255,0.07);
            padding-top: 16px;
            margin-top: 6px;
        }
        .price-row {
            display: flex; justify-content: space-between;
            color: rgba(255,255,255,0.5); font-size: 0.88rem;
            margin-bottom: 8px;
        }
        .price-row.total {
            color: #fff; font-weight: 700; font-size: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 10px; margin-top: 6px;
        }
        .price-row.total .val { color: #00d8b2; font-size: 1.25rem; }

        /* ── Alert ── */
        .alert { padding: 13px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-danger { background: rgba(233,42,103,0.12); border: 1px solid #e92a67; color: #ff6b9d; }
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
            <li><a href="rooms.php">Rooms</a></li>
            <li><a href="guest/dashboard.php" class="auth-nav-item"><i class="fa fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></a></li>
            <li><a href="logout.php" class="auth-nav-item"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<header class="page-header">
    <h1>Reserve Your Room</h1>
    <p><?php echo htmlspecialchars($room['HotelName']); ?> — <?php echo htmlspecialchars($room['City']); ?></p>
</header>

<div class="reservation-layout">

    <!-- ── BOOKING FORM ── -->
    <div class="form-panel astonish animated fadeInLeft">
        <h2><i class="fa fa-calendar-check-o"></i> Reservation Details</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="res-form" method="POST" action="reservation.php?room_id=<?php echo $roomId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN); ?>">

            <p class="form-section-title">Stay Dates</p>
            <div class="date-row">
                <div class="form-group">
                    <label><i class="fa fa-calendar"></i> Check-In Date</label>
                    <input type="date" name="check_in" id="check_in"
                           value="<?php echo htmlspecialchars($checkIn); ?>"
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fa fa-calendar-check-o"></i> Check-Out Date</label>
                    <input type="date" name="check_out" id="check_out"
                           value="<?php echo htmlspecialchars($checkOut); ?>"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fa fa-users"></i> Number of Guests (max <?php echo $room['MaxGuests']; ?>)</label>
                <input type="number" name="num_guests" min="1"
                       max="<?php echo $room['MaxGuests']; ?>"
                       value="<?php echo $guests ?: 1; ?>" required>
            </div>

            <p class="form-section-title">Your Information</p>
            <div class="form-group">
                <label><i class="fa fa-user"></i> Full Name</label>
                <input type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled
                       style="opacity:0.6; cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label><i class="fa fa-envelope"></i> Email</label>
                <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                       style="opacity:0.6; cursor:not-allowed;">
            </div>

            <p class="form-section-title">Special Requests <span style="font-weight:400;opacity:0.5;">(optional)</span></p>
            <div class="form-group">
                <textarea name="special_requests" placeholder="e.g. late check-in, extra pillows, high floor…"><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn-reserve">
                <i class="fa fa-lock"></i> Confirm Reservation & Proceed to Payment
            </button>
        </form>
    </div>

    <!-- ── ROOM SUMMARY ── -->
    <div class="summary-panel astonish animated fadeInRight">
        <div class="room-summary-card">
            <?php if (!empty($room['HotelImage'])): ?>
                <img class="sum-img" src="<?php echo htmlspecialchars($room['HotelImage']); ?>"
                     alt="<?php echo htmlspecialchars($room['HotelName']); ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="sum-img-placeholder" style="display:none"><i class="fa fa-bed"></i></div>
            <?php else: ?>
                <div class="sum-img-placeholder"><i class="fa fa-bed"></i></div>
            <?php endif; ?>

            <div class="sum-body">
                <div class="hotel-name">
                    <?php echo htmlspecialchars($room['HotelName']); ?> ·
                    <?php for ($i = 0; $i < $room['StarRating']; $i++) echo '<span style="color:#f0b429;font-size:0.75rem;">★</span>'; ?>
                </div>
                <div class="room-title">Room <?php echo htmlspecialchars($room['RoomNumber']); ?></div>
                <span class="type-badge"><?php echo ucfirst($room['RoomType']); ?></span>

                <div class="room-feats">
                    <div class="feat"><i class="fa fa-map-marker"></i><?php echo htmlspecialchars($room['City']); ?></div>
                    <div class="feat"><i class="fa fa-users"></i>Up to <?php echo $room['MaxGuests']; ?></div>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Price per night</span>
                        <span>€<?php echo number_format($room['PricePerNight'], 2); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Nights</span>
                        <span id="nightsDisplay"><?php echo $nights ?: '—'; ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Total</span>
                        <span class="val" id="totalDisplay">
                            <?php echo $totalCost ? '€' . number_format($totalCost, 2) : '—'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <p style="color:rgba(255,255,255,0.3);font-size:0.78rem;margin-top:16px;text-align:center;">
            <i class="fa fa-lock"></i> Secure payment on next step
        </p>
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
                    <li><a href="guest/dashboard.php"><i class="fa fa-tachometer"></i> Dashboard</a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Contact</h3>
                <div class="footer-contact">
                    <p><i class="fa fa-phone"></i> +357 25 000 000</p>
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
<script src="js/nav.js"></script>
<script>
    const pricePerNight = <?php echo $room['PricePerNight']; ?>;

    function updateSummary() {
        const inVal  = document.getElementById('check_in').value;
        const outVal = document.getElementById('check_out').value;
        if (!inVal || !outVal || outVal <= inVal) {
            document.getElementById('nightsDisplay').textContent = '—';
            document.getElementById('totalDisplay').textContent  = '—';
            return;
        }
        const diff   = (new Date(outVal) - new Date(inVal)) / (1000 * 60 * 60 * 24);
        const total  = diff * pricePerNight;
        document.getElementById('nightsDisplay').textContent = diff;
        document.getElementById('totalDisplay').textContent  = '€' + total.toFixed(2);
    }

    document.getElementById('check_in')?.addEventListener('change', function () {
        const next = new Date(this.value);
        next.setDate(next.getDate() + 1);
        const out = document.getElementById('check_out');
        out.min = next.toISOString().split('T')[0];
        if (out.value && out.value <= this.value) out.value = '';
        updateSummary();
    });

    document.getElementById('check_out')?.addEventListener('change', updateSummary);
    updateSummary();
</script>
</body>
</html>