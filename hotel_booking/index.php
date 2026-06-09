<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

try {
    $featuredHotels = $pdo->query("SELECT * FROM hotel WHERE IsActive = 1 ORDER BY StarRating DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {
    $featuredHotels = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LuxStay Hotels — Find Your Perfect Room</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .search-bar {
            background: rgba(0,0,0,0.75);
            border: 1px solid rgba(0,216,178,0.3);
            border-radius: 12px;
            padding: 28px 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
            max-width: 900px;
            margin: 0 auto;
            backdrop-filter: blur(6px);
        }
        .search-bar .field { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .search-bar label { color: #00d8b2; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .search-bar input,
        .search-bar select {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            color: #fff;
            padding: 10px 14px;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.2s;
        }
        .search-bar input:focus, .search-bar select:focus { border-color: #00d8b2; }
        .search-bar select option { background: #1a1a1a; }
        .search-bar input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        .btn-search {
            background: linear-gradient(135deg, #00d8b2, #009688);
            color: #111;
            border: none;
            border-radius: 6px;
            padding: 11px 28px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s, transform 0.2s;
            align-self: flex-end;
        }
        .btn-search:hover { opacity: 0.88; transform: translateY(-2px); }

        .about-section { padding: 80px 20px; position: relative; }
        .about-section .img-absolute { opacity: 0.12; }
        .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; max-width: 1100px; margin: 0 auto; }
        @media (max-width: 768px) { .about-grid { grid-template-columns: 1fr; } }
        .about-text h2 { color: #00d8b2; font-size: 2.4rem; margin-bottom: 16px; }
        .about-text p  { color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 14px; }
        .about-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-box { background: rgba(0,216,178,0.07); border: 1px solid rgba(0,216,178,0.2); border-radius: 10px; padding: 24px; text-align: center; }
        .stat-box .num { font-size: 2.5rem; font-weight: 700; color: #00d8b2; }
        .stat-box .lbl { font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-top: 4px; }

        .featured-section { padding: 80px 20px; background: rgba(0,0,0,0.5); }
        .featured-section h2 { text-align: center; color: #00d8b2; font-size: 2.2rem; margin-bottom: 10px; }
        .section-sub { text-align: center; color: rgba(255,255,255,0.4); margin-bottom: 50px; }
        .hotel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px; max-width: 1100px; margin: 0 auto; }
        .hotel-card { background: rgba(20,20,20,0.9); border: 1px solid rgba(0,216,178,0.15); border-radius: 12px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
        .hotel-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,216,178,0.15); }
        .hotel-card .card-img { width: 100%; height: 200px; object-fit: cover; display: block; }
        .hotel-card .card-img-placeholder { width: 100%; height: 200px; background: linear-gradient(135deg, #0d2626, #1a3a3a); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: rgba(0,216,178,0.3); }
        .hotel-card .card-body { padding: 22px; }
        .hotel-card .stars { color: #f0b429; font-size: 0.9rem; margin-bottom: 8px; }
        .hotel-card h3 { color: #fff; font-size: 1.2rem; margin-bottom: 6px; }
        .hotel-card .loc { color: rgba(255,255,255,0.45); font-size: 0.85rem; margin-bottom: 12px; }
        .hotel-card .loc i { color: #00d8b2; margin-right: 4px; }
        .hotel-card p.desc { color: rgba(255,255,255,0.6); font-size: 0.88rem; line-height: 1.6; margin-bottom: 18px; }
        .hotel-card .card-foot { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.07); padding-top: 14px; }
        .hotel-card .price { color: #00d8b2; font-size: 0.85rem; }
        .hotel-card .price span { font-size: 1.3rem; font-weight: 700; }

        .how-section { padding: 80px 20px; }
        .how-section h2 { text-align: center; color: #00d8b2; font-size: 2.2rem; margin-bottom: 50px; }
        .steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; max-width: 1000px; margin: 0 auto; }
        .step { text-align: center; padding: 30px 20px; }
        .step .icon { width: 70px; height: 70px; border-radius: 50%; background: rgba(0,216,178,0.08); border: 2px solid rgba(0,216,178,0.25); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.6rem; color: #00d8b2; transition: all 0.3s; }
        .step:hover .icon { background: rgba(0,216,178,0.18); border-color: #00d8b2; transform: scale(1.1); }
        .step h3 { color: #fff; margin-bottom: 10px; }
        .step p  { color: rgba(255,255,255,0.5); font-size: 0.9rem; line-height: 1.6; }

        .cta-section { padding: 80px 20px; text-align: center; background: linear-gradient(135deg, rgba(0,216,178,0.07), rgba(0,150,136,0.04)); border-top: 1px solid rgba(0,216,178,0.1); border-bottom: 1px solid rgba(0,216,178,0.1); }
        .cta-section h2 { color: #fff; font-size: 2.4rem; margin-bottom: 14px; }
        .cta-section p  { color: rgba(255,255,255,0.55); margin-bottom: 30px; font-size: 1.05rem; }
        .cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
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
            <li><a href="#about">About</a></li>
            <li><a href="rooms.php">Rooms</a></li>
            <li><a href="#how">How It Works</a></li>
            <?php if (isLoggedIn()): ?>
                <?php $user = getCurrentUser(); ?>
                <li><a href="<?php echo $user['role'] === 'customer' ? 'guest/dashboard.php' : 'staff/dashboard.php'; ?>" class="auth-nav-item">
                    <i class="fa fa-user"></i> <?php echo htmlspecialchars($user['username']); ?>
                </a></li>
                <li><a href="logout.php" class="auth-nav-item"><i class="fa fa-sign-out"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="guest-login.php" class="auth-nav-item"><i class="fa fa-user"></i> Login</a></li>
                <li><a href="guest-signup.php" class="auth-nav-item"><i class="fa fa-user-plus"></i> Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- HERO -->
<header>
    <img class="img-absolute" src="img/hero-hotel.jpg" alt="Luxury Hotel">
    <div class="wrapper astonish animated fadeInDown">
        <h1>Lux<strong>Stay</strong></h1>
        <h2>Discover exceptional hotels across Cyprus.<br>Book in seconds, stay in style.</h2>
    </div>
    <div style="width:100%; padding: 0 20px; margin-top: 40px; position: relative; z-index: 2;">
        <form class="search-bar" action="rooms.php" method="GET">
            <div class="field">
                <label><i class="fa fa-map-marker"></i> City</label>
                <select name="city">
                    <option value="">All Cities</option>
                    <option value="Limassol">Limassol</option>
                    <option value="Paphos">Paphos</option>
                    <option value="Kyrenia">Kyrenia</option>
                    <option value="Nicosia">Nicosia</option>
                    <option value="Ayia Napa">Ayia Napa</option>
                </select>
            </div>
            <div class="field">
                <label><i class="fa fa-calendar"></i> Check-In</label>
                <input type="date" name="check_in" id="check_in" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="field">
                <label><i class="fa fa-calendar-check-o"></i> Check-Out</label>
                <input type="date" name="check_out" id="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <div class="field" style="max-width:110px;">
                <label><i class="fa fa-users"></i> Guests</label>
                <input type="number" name="guests" min="1" max="10" value="1">
            </div>
            <button type="submit" class="btn-search"><i class="fa fa-search"></i> Search</button>
        </form>
    </div>
</header>

<main>
    <!-- ABOUT -->
    <div class="about-section" id="about">
        <img class="img-absolute" src="img/hotel-interior.jpg" alt="Interior">
        <div class="about-grid">
            <div class="about-text astonish" data-animation="fadeInLeft">
                <h2>Cyprus's Premier Hotel Booking Platform</h2>
                <p>LuxStay connects travelers with the finest hotels across Cyprus — from the historic streets of Kyrenia to the sun-soaked beaches of Ayia Napa.</p>
                <p>Real-time availability, instant confirmation, and seamless payment — all in one place. No more juggling multiple websites or waiting for callbacks.</p>
                <a href="rooms.php" class="btn btn-outline-teal" style="margin-top: 10px;">Browse All Rooms</a>
            </div>
            <div class="about-stats astonish" data-animation="fadeInRight">
                <div class="stat-box"><div class="num">5</div><div class="lbl">Hotels</div></div>
                <div class="stat-box"><div class="num">13+</div><div class="lbl">Room Types</div></div>
                <div class="stat-box"><div class="num">5</div><div class="lbl">Cities</div></div>
                <div class="stat-box"><div class="num">24/7</div><div class="lbl">Support</div></div>
            </div>
        </div>
    </div>

    <!-- FEATURED HOTELS -->
    <div class="featured-section" id="hotels">
        <h2 class="astonish" data-animation="fadeInDown">Featured Hotels</h2>
        <p class="section-sub astonish" data-animation="fadeInDown">Handpicked properties across the island</p>
        <div class="hotel-grid">
            <?php foreach ($featuredHotels as $hotel):
                $priceStmt = $pdo->prepare("SELECT MIN(PricePerNight) FROM room WHERE HotelID = ? AND IsActive = 1");
                $priceStmt->execute([$hotel['HotelID']]);
                $minPrice = $priceStmt->fetchColumn();
            ?>
            <div class="hotel-card astonish" data-animation="fadeInUp">
                <?php if (!empty($hotel['ImagePath'])): ?>
                    <img class="card-img" src="<?php echo htmlspecialchars($hotel['ImagePath']); ?>"
                         alt="<?php echo htmlspecialchars($hotel['HotelName']); ?>"
                         onerror="this.parentNode.innerHTML='<div class=\'card-img-placeholder\'><i class=\'fa fa-building\'></i></div>'">
                <?php else: ?>
                    <div class="card-img-placeholder"><i class="fa fa-building"></i></div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="stars">
                        <?php
                        for ($i = 0; $i < $hotel['StarRating']; $i++) echo '★';
                        for ($i = $hotel['StarRating']; $i < 5; $i++) echo '<span style="opacity:0.2">★</span>';
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($hotel['HotelName']); ?></h3>
                    <p class="loc"><i class="fa fa-map-marker"></i><?php echo htmlspecialchars($hotel['City']); ?>, <?php echo htmlspecialchars($hotel['Country']); ?></p>
                    <p class="desc"><?php echo htmlspecialchars(substr($hotel['Description'], 0, 100)) . '…'; ?></p>
                    <div class="card-foot">
                        <div class="price">From <span>€<?php echo number_format($minPrice ?? 0, 0); ?></span><small>/night</small></div>
                        <a href="rooms.php?hotel_id=<?php echo $hotel['HotelID']; ?>" class="btn btn-primary" style="padding:8px 18px;font-size:0.9rem;">View Rooms</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($featuredHotels)): ?>
                <p style="color:rgba(255,255,255,0.4);text-align:center;grid-column:1/-1;">No hotels yet — make sure you ran the SQL schema.</p>
            <?php endif; ?>
        </div>
        <div style="text-align:center;margin-top:40px;">
            <a href="rooms.php" class="btn btn-outline-teal">View All Hotels & Rooms →</a>
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <div class="how-section" id="how">
        <h2 class="astonish" data-animation="fadeInDown">How It Works</h2>
        <div class="steps-grid">
            <div class="step astonish" data-animation="fadeInUp" data-delay="0.1">
                <div class="icon"><i class="fa fa-search"></i></div>
                <h3>Search</h3>
                <p>Browse hotels and filter by city, dates, room type, and price range.</p>
            </div>
            <div class="step astonish" data-animation="fadeInUp" data-delay="0.2">
                <div class="icon"><i class="fa fa-bed"></i></div>
                <h3>Choose a Room</h3>
                <p>View room details, amenities, and real-time availability.</p>
            </div>
            <div class="step astonish" data-animation="fadeInUp" data-delay="0.3">
                <div class="icon"><i class="fa fa-calendar-check-o"></i></div>
                <h3>Reserve</h3>
                <p>Fill in your details, pick your dates, and confirm your booking.</p>
            </div>
            <div class="step astonish" data-animation="fadeInUp" data-delay="0.4">
                <div class="icon"><i class="fa fa-credit-card"></i></div>
                <h3>Pay Securely</h3>
                <p>Pay with credit or debit card. Instant confirmation sent.</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="cta-section astonish" data-animation="zoomIn" id="reserve">
        <h2>Ready to Book Your Stay?</h2>
        <p>Join thousands of travelers who book with LuxStay every month.</p>
        <div class="cta-btns">
            <a href="rooms.php" class="btn btn-primary">Browse Rooms</a>
            <?php if (!isLoggedIn()): ?>
                <a href="guest-signup.php" class="btn btn-outline-teal">Create Free Account</a>
            <?php else: ?>
                <a href="guest/dashboard.php" class="btn btn-outline-teal">My Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONTACT -->
    <div class="content-wrapper" id="contact">
        <img class="img-absolute" src="img/hotel-reception.jpg" alt="Reception">
        <form class="contact-form astonish" action="#" method="post" data-animation="fadeInRight">
            <h2 class="section-title">Contact Us</h2>
            <div class="grid">
                <div class="grid-col-sm-12 grid-col-md-6">
                    <div class="form-group"><input type="text" name="firstName" required><label>First Name</label></div>
                </div>
                <div class="grid-col-sm-12 grid-col-md-6">
                    <div class="form-group"><input type="text" name="lastName" required><label>Last Name</label></div>
                </div>
                <div class="grid-col-sm-12">
                    <div class="form-group"><input type="email" name="email" required><label>Email</label></div>
                </div>
                <div class="grid-col-sm-12">
                    <div class="form-group"><textarea name="message" required></textarea><label>Message</label></div>
                </div>
            </div>
            <input class="btn btn-outline-teal" type="submit" value="Send Message">
        </form>
    </div>
</main>

<footer>
    <div class="content-wrapper-sm footer-container">
        <div class="footer-grid">
            <div class="footer-section">
                <h3 class="footer-title">LuxStay</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fa fa-home"></i> Home</a></li>
                    <li><a href="#about"><i class="fa fa-info-circle"></i> About</a></li>
                    <li><a href="rooms.php"><i class="fa fa-bed"></i> All Rooms</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Guests</h3>
                <ul class="footer-links">
                    <li><a href="guest-login.php"><i class="fa fa-sign-in"></i> Guest Login</a></li>
                    <li><a href="guest-signup.php"><i class="fa fa-user-plus"></i> Create Account</a></li>
                    <li><a href="#contact"><i class="fa fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Follow Us</h3>
                <div class="social-links">
                    <a href="#" class="social-icon"><i class="fa fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fa fa-instagram"></i></a>
                </div>
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
<script src="js/scroll.js"></script>
<script>
    document.getElementById('check_in')?.addEventListener('change', function () {
        const next = new Date(this.value);
        next.setDate(next.getDate() + 1);
        const out = document.getElementById('check_out');
        out.min = next.toISOString().split('T')[0];
        if (out.value && out.value <= this.value) out.value = '';
    });
</script>
</body>
</html>