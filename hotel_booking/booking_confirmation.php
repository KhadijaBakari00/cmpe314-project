<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$reservationId = (int)$_GET['id'];

// Fetch full reservation + payment info
$stmt = $pdo->prepare("
    SELECT res.*,
           r.RoomNumber, r.RoomType, r.PricePerNight,
           h.HotelName, h.City, h.Country, h.StarRating, h.Location AS HotelAddress,
           g.FullName, g.Email,
           py.Amount, py.TransactionID, py.PaymentMethod, py.PaymentDate
    FROM reservation res
    JOIN room r   ON res.RoomID  = r.RoomID
    JOIN hotel h  ON r.HotelID   = h.HotelID
    JOIN guest g  ON res.GuestID = g.GuestID
    LEFT JOIN payment py ON res.ReservationID = py.ReservationID
    WHERE res.ReservationID = ?
    ORDER BY py.PaymentDate DESC
    LIMIT 1
");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: index.php");
    exit();
}

// Fallback to session if payment row not yet joined
$paymentAmount  = $reservation['Amount']        ?? $_SESSION['current_reservation']['total'] ?? 0;
$transactionId  = $reservation['TransactionID'] ?? 'N/A';
$paymentDate    = $reservation['PaymentDate']   ?? date('Y-m-d H:i:s');
$paymentMethod  = $reservation['PaymentMethod'] ?? 'N/A';

$nights = (new DateTime($reservation['CheckInDate']))->diff(new DateTime($reservation['CheckOutDate']))->days;

// Clear session booking data now that we've confirmed
if (isset($_SESSION['current_reservation']))  unset($_SESSION['current_reservation']);
if (isset($_SESSION['payment_success']))      unset($_SESSION['payment_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed — LuxStay Hotels</title>
    <!-- Reuses payment.css for the same dark container style -->
    <link rel="stylesheet" href="payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        .confirmation {
            background: rgba(0,0,0,0.75);
            border-radius: 1rem;
            padding: 4rem 3.5rem;
            text-align: center;
            max-width: 620px;
            margin: 0 auto;
            box-shadow: 0 0 30px rgba(0,216,178,0.15);
            border: 1px solid rgba(0,216,178,0.2);
        }
        .confirmation__icon { margin-bottom: 2rem; }
        .confirmation__icon svg { filter: drop-shadow(0 0 12px rgba(0,216,178,0.5)); }

        .confirmation h1 {
            font-size: 2.8rem;
            color: #00d8b2;
            margin-bottom: 0.8rem;
            font-family: 'Montserrat', sans-serif;
        }
        .confirmation__text {
            font-size: 1.4rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 3rem;
        }

        .confirmation__details {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(0,216,178,0.12);
            border-radius: 0.8rem;
            padding: 2.5rem;
            margin-bottom: 3rem;
            text-align: left;
        }
        .confirmation__details h2 {
            font-size: 1.6rem;
            color: #00d8b2;
            margin-bottom: 2rem;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,216,178,0.15);
        }

        /* Two-column detail grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.4rem 2rem;
        }
        @media (max-width: 500px) { .detail-grid { grid-template-columns: 1fr; } }

        .detail-item .lbl {
            font-size: 1rem;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }
        .detail-item .val {
            font-size: 1.35rem;
            color: #f0f0f0;
            font-weight: 400;
        }
        .detail-item .val.highlight { color: #00d8b2; font-weight: 700; }

        /* Stars */
        .stars { color: #f0b429; letter-spacing: 1px; }

        /* Total box */
        .total-box {
            background: rgba(0,216,178,0.07);
            border: 1px solid rgba(0,216,178,0.25);
            border-radius: 0.6rem;
            padding: 1.4rem 2rem;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            grid-column: 1 / -1;
        }
        .total-box .total-lbl { font-size: 1.4rem; color: #fff; }
        .total-box .total-val { font-size: 2rem; font-weight: 700; color: #00d8b2; }

        /* Buttons */
        .confirmation__actions {
            display: flex;
            gap: 1.4rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .confirmation__btn {
            display: inline-block;
            padding: 1.2rem 2.8rem;
            background: #00d8b2;
            color: #111;
            border-radius: 0.6rem;
            text-decoration: none;
            font-size: 1.4rem;
            font-weight: 700;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .confirmation__btn:hover { background: #00c7a3; transform: translateY(-2px); }
        .confirmation__btn.outline {
            background: transparent;
            border: 2px solid #00d8b2;
            color: #00d8b2;
        }
        .confirmation__btn.outline:hover { background: rgba(0,216,178,0.1); }

        /* Status badge */
        .status-badge {
            display: inline-block;
            background: rgba(0,216,178,0.15);
            border: 1px solid rgba(0,216,178,0.4);
            color: #00d8b2;
            font-size: 1rem;
            padding: 0.4rem 1.2rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2rem;
        }
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
                <li><a href="guest/dashboard.php">My Bookings</a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="confirmation">

            <!-- Animated checkmark -->
            <div class="confirmation__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24"
                     fill="none" stroke="#00d8b2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <div class="status-badge"><i class="fa fa-check"></i> Confirmed</div>
            <h1>Booking Confirmed!</h1>
            <p class="confirmation__text">
                Your reservation is all set. A confirmation has been sent to<br>
                <strong style="color:#00d8b2;"><?php echo htmlspecialchars($reservation['Email']); ?></strong>
            </p>

            <div class="confirmation__details">
                <h2><i class="fa fa-building"></i> Reservation Details</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="lbl">Reservation ID</div>
                        <div class="val highlight">#<?php echo $reservation['ReservationID']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Transaction ID</div>
                        <div class="val"><?php echo htmlspecialchars($transactionId); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="lbl">Hotel</div>
                        <div class="val">
                            <?php echo htmlspecialchars($reservation['HotelName']); ?>
                            <span class="stars">
                                <?php for ($i = 0; $i < $reservation['StarRating']; $i++) echo '★'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Location</div>
                        <div class="val"><?php echo htmlspecialchars($reservation['City']); ?>, <?php echo htmlspecialchars($reservation['Country']); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="lbl">Room</div>
                        <div class="val"><?php echo ucfirst($reservation['RoomType']); ?> — Room <?php echo htmlspecialchars($reservation['RoomNumber']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Guests</div>
                        <div class="val"><?php echo $reservation['NumGuests']; ?> guest<?php echo $reservation['NumGuests'] > 1 ? 's' : ''; ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="lbl">Check-In</div>
                        <div class="val"><?php echo date('D, d M Y', strtotime($reservation['CheckInDate'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Check-Out</div>
                        <div class="val"><?php echo date('D, d M Y', strtotime($reservation['CheckOutDate'])); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="lbl">Duration</div>
                        <div class="val"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Payment Method</div>
                        <div class="val"><?php echo ucwords(str_replace('_', ' ', $paymentMethod)); ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="lbl">Payment Date</div>
                        <div class="val"><?php echo date('d M Y, H:i', strtotime($paymentDate)); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="lbl">Guest Name</div>
                        <div class="val"><?php echo htmlspecialchars($reservation['FullName']); ?></div>
                    </div>

                    <div class="total-box">
                        <span class="total-lbl">Total Paid</span>
                        <span class="total-val">€<?php echo number_format((float)$paymentAmount, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="confirmation__actions">
                <a href="guest/dashboard.php" class="confirmation__btn">
                    <i class="fa fa-tachometer"></i> View My Bookings
                </a>
                <a href="rooms.php" class="confirmation__btn outline">
                    <i class="fa fa-search"></i> Browse More Rooms
                </a>
            </div>

        </div>
    </div>
</body>
</html>