<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($user['role'] === 'staff') {
        header("Location: staff/dashboard.php");
    } else {
        header("Location: guest-login.php?error=This login is for staff only");
    }
    exit();
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        if (loginUser($username, $password)) {
            $user = getCurrentUser();
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] === 'staff') {
                header("Location: staff/dashboard.php");
            } else {
                // This shouldn't happen for staff-login, but fallback
                logoutUser();
                header("Location: staff-login.php?error=Access denied: staff accounts only");
            }
            exit();
        } else {
            header("Location: staff-login.php?error=Invalid username or password");
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
    <title>Staff Login — Hotel Booking</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body { position: relative; min-height: 100vh; overflow-x: hidden; }
        .img-absolute { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: -1; }
        .login-container {
            background: rgba(0,0,0,0.8);
            padding: 40px 35px;
            border-radius: 12px;
            max-width: 460px;
            margin: 80px auto;
            box-shadow: 0 0 30px rgba(0,216,178,0.3), 0 0 60px rgba(0,216,178,0.1);
            border: 1px solid rgba(0,216,178,0.25);
            position: relative;
            z-index: 1;
            width: 90%;
        }
        .staff-badge {
            display: inline-block;
            background: rgba(0,216,178,0.15);
            border: 1px solid rgba(0,216,178,0.4);
            color: #00d8b2;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .login-toggle { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .login-toggle a { color: rgba(255,255,255,0.4); text-decoration: none; font-size: 0.8rem; }
        .login-toggle a:hover { color: #00d8b2; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; font-size: 0.9rem; }
        .alert-danger { background: rgba(233,42,103,0.15); border: 1px solid #e92a67; color: #ff6b9d; }
    </style>
</head>
<body>
    <img class="img-absolute" src="img/hotel-desk.jpg" alt="Hotel Background">
    <div class="overlay"></div>

    <nav class="main-nav" id="main-nav">
        <div class="content-wrapper-sm">
            <a href="index.php" class="navbar-brand">LuxStay Hotels</a>
            <div id="menu-button">
                <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="guest-login.php" class="auth-nav-item"><i class="fa fa-user"></i> Guest Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="login-container astonish animated fadeIn">
        <div class="staff-badge"><i class="fa fa-lock"></i> Staff Portal</div>
        <h2 class="section-title">Staff Login</h2>
        <p style="color: rgba(255,255,255,0.4); margin-bottom: 25px; font-size: 0.85rem;">Authorized personnel only</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="staff-login.php">
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
            <a href="guest-login.php">← Back to Guest Login</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="js/menu.js"></script>
    <script src="js/astonish.js"></script>
    <script src="js/nav.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eyeball   = document.querySelector('.eyeball');
            const beam      = document.querySelector('.beam');
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