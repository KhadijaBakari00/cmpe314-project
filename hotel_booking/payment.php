<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// Must be logged in as a guest
if (!isLoggedIn() || getCurrentUser()['role'] !== 'customer') {
    header("Location: guest-login.php");
    exit();
}

// Must have an active reservation in session
if (!isset($_SESSION['current_reservation'])) {
    header("Location: rooms.php");
    exit();
}

$user          = getCurrentUser();
$reservationId = $_SESSION['current_reservation']['id'];
$totalCost     = $_SESSION['current_reservation']['total'];

// Fetch full reservation details
try {
    $stmt = $pdo->prepare("
        SELECT res.*, r.RoomNumber, r.RoomType, r.PricePerNight,
               h.HotelName, h.City, h.StarRating,
               g.FullName, g.Email
        FROM reservation res
        JOIN room r   ON res.RoomID  = r.RoomID
        JOIN hotel h  ON r.HotelID   = h.HotelID
        JOIN guest g  ON res.GuestID = g.GuestID
        WHERE res.ReservationID = ?
    ");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();
    if (!$reservation) {
        header("Location: rooms.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error loading reservation: " . $e->getMessage());
}

$nights = (new DateTime($reservation['CheckInDate']))->diff(new DateTime($reservation['CheckOutDate']))->days;

// Handle payment form submission
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $cardName      = trim($_POST['card_name']);
        $cardNumber    = str_replace(' ', '', trim($_POST['card_number']));
        $expiry        = trim($_POST['expiry']);
        $cvv           = trim($_POST['cvv']);
        $paymentMethod = trim($_POST['payment_method']);

        if (empty($cardName)) {
            $error = "Cardholder name is required.";
        } elseif (!preg_match('/^\d{16}$/', $cardNumber)) {
            $error = "Card number must be exactly 16 digits.";
        } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            $error = "Expiry date must be in MM/YY format.";
        } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
            $error = "CVV must be 3 or 4 digits.";
        } elseif (!in_array($paymentMethod, ['credit_card', 'debit_card'])) {
            $error = "Invalid payment method selected.";
        }

        if (!$error) {
            try {
                $transactionId = 'TXN-' . strtoupper(uniqid());

                // Record payment
                $stmt = $pdo->prepare("INSERT INTO payment
                    (ReservationID, Amount, PaymentMethod, TransactionID, Status)
                    VALUES (?, ?, ?, ?, 'completed')");
                $stmt->execute([$reservationId, $totalCost, $paymentMethod, $transactionId]);

                // Confirm reservation
                $pdo->prepare("UPDATE reservation SET Status = 'confirmed' WHERE ReservationID = ?")
                    ->execute([$reservationId]);

                // Audit log
                logAudit($user['user_id'], 'payment_completed',
                    "Reservation #$reservationId — $paymentMethod — €" . number_format($totalCost, 2));

                // Notify guest
                sendNotification($user['user_id'],
                    "Payment confirmed! Your stay at {$reservation['HotelName']} is booked. " .
                    "Check-in: {$reservation['CheckInDate']}. Reservation #$reservationId.");

                $_SESSION['payment_success'] = true;
                header("Location: booking_confirmation.php?id=$reservationId");
                exit();

            } catch (PDOException $e) {
                $error = "Payment processing failed: " . $e->getMessage();
            }
        }
    }
}

// Refresh CSRF token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — LuxStay Hotels</title>
    <!-- Reuse the exact same payment.css from the old project -->
    <link rel="stylesheet" href="payment.css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.1.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.1.2/dist/ionicons/ionicons.js"></script>
    <style>
        .booking-summary {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(0,216,178,0.15);
            border-radius: 0.8rem;
            padding: 2rem;
            margin: 2rem 0;
            grid-column: 1 / -1;
        }
        .booking-summary h3 {
            color: #00d8b2;
            margin-bottom: 1.4rem;
            font-size: 1.6rem;
        }
        .booking-summary p { font-size: 1.35rem; margin-bottom: 0.7rem; color: #f0f0f0; }
        .booking-summary strong { color: #00d8b2; }
        .booking-summary .total-line {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            border-top: 1px solid rgba(0,216,178,0.2);
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        .booking-summary .total-line span { color: #00d8b2; }
        .alert-danger-pay {
            background: rgba(233,42,103,0.12);
            border: 1px solid #e92a67;
            color: #ff6b9d;
            padding: 1.2rem 1.6rem;
            border-radius: 0.6rem;
            font-size: 1.3rem;
            margin-bottom: 2rem;
            grid-column: 1/-1;
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
        <div class="payment">
            <div class="payment__shadow-dots"></div>
            <div class="payment__dots">
                <svg width="65" height="115" viewBox="0 0 65 115" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="2.5" cy="2.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="2.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="2.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="2.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="2.5" cy="22.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="22.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="22.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="22.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="2.5" cy="42.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="42.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="42.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="42.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="2.5" cy="62.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="62.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="62.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="62.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="2.5" cy="82.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="82.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="82.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="82.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="2.5" cy="102.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="22.5" cy="102.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="42.5" cy="102.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                    <circle cx="62.5" cy="102.5" r="2.5" fill="#00d8b2" opacity="0.3"/>
                </svg>
            </div>

            <!-- Visual credit card -->
            <div class="card">
                <div class="card__visa">
                    <svg enable-background="new 0 0 291.764 291.764" version="1.1" viewBox="5 70 290 200" xmlns="http://www.w3.org/2000/svg">
                        <path d="m119.26 100.23l-14.643 91.122h23.405l14.634-91.122h-23.396zm70.598 37.118c-8.179-4.039-13.193-6.765-13.193-10.896 0.1-3.756 4.24-7.604 13.485-7.604 7.604-0.191 13.193 1.596 17.433 3.374l2.124 0.948 3.182-19.065c-4.623-1.787-11.953-3.756-21.007-3.756-23.113 0-39.388 12.017-39.489 29.204-0.191 12.683 11.652 19.721 20.515 23.943 9.054 4.331 12.136 7.139 12.136 10.987-0.1 5.908-7.321 8.634-14.059 8.634-9.336 0-14.351-1.404-21.964-4.696l-3.082-1.404-3.273 19.813c5.498 2.444 15.609 4.595 26.104 4.705 24.563 0 40.546-11.835 40.747-30.152 0.08-10.048-6.165-17.744-19.659-24.035zm83.034-36.836h-18.108c-5.58 0-9.82 1.605-12.236 7.331l-34.766 83.509h24.563l6.765-18.08h27.481l3.51 18.153h21.664l-18.873-90.913zm-26.97 54.514l9.428-29.514 7.13 29.514h-16.558zm-160.86-54.796l-22.931 61.909-2.498-12.209c-4.24-14.087-17.533-29.395-32.368-36.999l20.998 78.33h24.764l36.799-91.021h-24.764v-0.01z" fill="#FFFFFF"/>
                        <path d="m51.916 111.98c-1.787-6.948-7.486-11.634-15.226-11.734h-36.316l-0.374 1.686c28.329 6.984 52.107 28.474 59.821 48.688l-7.905-38.64z" fill="#FFFFFF"/>
                    </svg>
                </div>
                <div class="card__number" id="cardNumberDisplay">0000 0000 0000 0000</div>
                <div class="card__name">
                    <h3>Card Holder</h3>
                    <p id="card-name"><?php echo htmlspecialchars($reservation['FullName']); ?></p>
                </div>
                <div class="card__expiry">
                    <h3>Valid Thru</h3>
                    <p><span id="month">MM</span>/<span id="year">YY</span></p>
                </div>
            </div>

            <!-- Payment form -->
            <form class="form" method="POST" action="payment.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <?php if ($error): ?>
                    <div class="alert-danger-pay"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <h2>Payment Details</h2>

                <div class="form__name form__detail">
                    <label for="name">Cardholder Name</label>
                    <div class="input-wrapper">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" placeholder="Full Name" id="name" name="card_name"
                               value="<?php echo htmlspecialchars($reservation['FullName']); ?>" required>
                    </div>
                </div>

                <div class="form__number form__detail">
                    <label for="number">Card Number</label>
                    <div class="input-wrapper">
                        <ion-icon name="card-outline"></ion-icon>
                        <input type="text" placeholder="0000 0000 0000 0000" id="number"
                               name="card_number" required maxlength="19">
                    </div>
                </div>

                <div class="form__method form__detail">
                    <label for="payment_method">Payment Method</label>
                    <div class="input-wrapper">
                        <ion-icon name="card-outline"></ion-icon>
                        <select id="payment_method" name="payment_method" required>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                        </select>
                    </div>
                </div>

                <div class="form__expiry form__detail">
                    <label for="date">Expiry Date</label>
                    <div class="input-wrapper">
                        <ion-icon name="calendar-outline"></ion-icon>
                        <input type="text" placeholder="MM/YY" id="date" name="expiry" required maxlength="5">
                    </div>
                </div>

                <div class="form__cvv form__detail">
                    <label for="cvv">CVV</label>
                    <div class="input-wrapper">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" placeholder="•••" id="cvv" name="cvv" maxlength="4" required>
                    </div>
                </div>

                <!-- Booking Summary inside the form -->
                <div class="booking-summary">
                    <h3>Booking Summary</h3>
                    <p><strong>Hotel:</strong> <?php echo htmlspecialchars($reservation['HotelName']); ?>, <?php echo htmlspecialchars($reservation['City']); ?></p>
                    <p><strong>Room:</strong> <?php echo ucfirst($reservation['RoomType']); ?> — Room <?php echo htmlspecialchars($reservation['RoomNumber']); ?></p>
                    <p><strong>Check-in:</strong> <?php echo date('D, d M Y', strtotime($reservation['CheckInDate'])); ?></p>
                    <p><strong>Check-out:</strong> <?php echo date('D, d M Y', strtotime($reservation['CheckOutDate'])); ?></p>
                    <p><strong>Nights:</strong> <?php echo $nights; ?></p>
                    <p><strong>Guests:</strong> <?php echo $reservation['NumGuests']; ?></p>
                    <p class="total-line">Total: <span>€<?php echo number_format($totalCost, 2); ?></span></p>
                </div>

                <button type="submit" class="form__btn">
                    <i class="fa fa-lock"></i> Confirm Payment — €<?php echo number_format($totalCost, 2); ?>
                </button>
            </form>
        </div>
    </div>

    <script src="payment.js"></script>
    <script>
        // Card number live display
        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.replace(/(.{4})/g, '$1 ').trim();
            input.value = value;
            document.getElementById('cardNumberDisplay').textContent = value || '0000 0000 0000 0000';
        }

        // Expiry live display
        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 2) value = value.substring(0, 2) + '/' + value.substring(2, 4);
            input.value = value;
            document.getElementById('month').textContent = value.split('/')[0] || 'MM';
            document.getElementById('year').textContent  = value.split('/')[1] || 'YY';
        }

        // Name live display
        document.getElementById('name')?.addEventListener('input', function () {
            document.getElementById('card-name').textContent = this.value || '<?php echo htmlspecialchars($reservation['FullName']); ?>';
        });

        document.getElementById('number')?.addEventListener('input', function () { formatCardNumber(this); });
        document.getElementById('date')?.addEventListener('input',   function () { formatExpiry(this); });
    </script>
</body>
</html>