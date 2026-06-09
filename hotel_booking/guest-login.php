<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// Already logged in → redirect
if (isLoggedIn()) {
    $user = getCurrentUser();
    header("Location: " . ($user['role'] === 'customer' ? 'guest/dashboard.php' : 'staff/dashboard.php'));
    exit();
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        if (loginUser($username, $password)) {
            $user = getCurrentUser();
            header("Location: " . ($user['role'] === 'customer' ? 'guest/dashboard.php' : 'staff/dashboard.php'));
            exit();
        } else {
            header("Location: guest-login.php?error=Invalid username or password");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Guest Login — Hotel Booking</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body { position: relative; min-height: 100vh; overflow-x: hidden; }
        .img-absolute { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); z-index: -1; }
        .login-container {
            background: rgba(0,0,0,0.75);
            padding: 40px 35px;
            border-radius: 12px;
            max-width: 480px;
            margin: 60px auto;
            box-shadow: 0 0 30px rgba(0,216,178,0.4);
            border: 1px solid rgba(0,216,178,0.2);
            position: relative;
            z-index: 1;
        }
        .login-toggle { text-align: center; margin-top: 20px; font-size: 0.95rem; }
        .login-toggle a { color: #00d8b2; text-decoration: none; }
        .login-toggle a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; font-size: 0.9rem; }
        .alert-danger  { background: rgba(233,42,103,0.15); border: 1px solid #e92a67; color: #ff6b9d; }
        .divider { text-align: center; margin: 20px 0; color: rgba(255,255,255,0.3); font-size: 0.85rem; }
        .staff-link { text-align: center; margin-top: 10px; }
        .staff-link a { color: rgba(255,255,255,0.4); font-size: 0.8rem; text-decoration: none; }
        .staff-link a:hover { color: #00d8b2; }
    </style>
</head>
<body>
    <img class="img-absolute" src="img/hotel-lobby.jpg" alt="Hotel Background">
    <div class="overlay"></div>

    <nav class="main-nav" id="main-nav">
        <div class="content-wrapper-sm">
            <a href="index.php" class="navbar-brand">LuxStay Hotels</a>
            <div id="menu-button">
                <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
            </div>
            <ul class="nav-links">
                <li><a href="index.php#about">About</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="index.php">Home</a></li>
                <li><a href="guest-login.php" class="auth-nav-item"><i class="fa fa-user"></i> Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="login-container astonish animated fadeIn">
        <h2 class="section-title">Welcome Back!</h2>
        <p style="color: rgba(255,255,255,0.5); margin-bottom: 25px; font-size: 0.9rem;">Sign in to manage your reservations</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="guest-login.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN); ?>">
            <div class="form-group">
                <input type="text" id="username" name="username" required autocomplete="username">
                <label for="username">Username</label>
            </div>
            <div class="form-group password-wrapper">
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <label for="password">Password</label>
                <button type="button" class="eyeball"><div class="eye"></div></button>
                <div class="beam"></div>
            </div>
            <button type="submit" class="btn btn-outline-teal" style="width:100%; margin-top: 10px;">Sign In</button>
        </form>

        <div class="login-toggle">
            <p>Don't have an account? <a href="guest-signup.php">Create one →</a></p>
        </div>
        <div class="divider">——————————</div>
        <div class="staff-link">
            <a href="staff-login.php"><i class="fa fa-lock"></i> Staff / Admin Login</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="js/menu.js"></script>
    <script src="js/astonish.js"></script>
    <script src="js/nav.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eyeball = document.querySelector('.eyeball');
            const beam    = document.querySelector('.beam');
            const passInput = document.getElementById('password');

            document.addEventListener('mousemove', (e) => {
                const rect   = beam.getBoundingClientRect();
                const mouseX = rect.right  + (rect.width  / 2);
                const mouseY = rect.top    + (rect.height / 2);
                const rad    = Math.atan2(mouseX - e.pageX, mouseY - e.pageY);
                const deg    = (rad * (20 / Math.PI) * -1) - 350;
                document.documentElement.style.setProperty('--beam-degrees', `${deg}deg`);
            });

            eyeball.addEventListener('click', (e) => {
                e.preventDefault();
                const wrapper = eyeball.closest('.password-wrapper');
                passInput.type = passInput.type === 'password' ? 'text' : 'password';
                wrapper.classList.toggle('show-password');
                passInput.focus();
            });
        });
    </script>
</body>
</html>