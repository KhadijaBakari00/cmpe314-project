<?php
require_once 'includes/config.php';
require_once 'includes/auth_functions.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token.';
    } else {
        $firstName       = sanitizeInput($_POST['firstName']);
        $lastName        = sanitizeInput($_POST['lastName']);
        $email           = sanitizeInput($_POST['email']);
        $password        = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        $fullName        = $firstName . ' ' . $lastName;

        if ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } else {
            // Generate unique username: firstname.lastname (+ number if taken)
            $username = strtolower($firstName . $lastName);
            $baseUsername = $username;
            $counter      = 1;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ?");
            $stmt->execute([$username]);
            while ($stmt->fetchColumn() > 0) {
                $username = $baseUsername . $counter++;
                $stmt->execute([$username]);
            }

            if (registerUser($username, $password, $email, $fullName, 'customer')) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $success = "Account created! Your username is <strong>$username</strong>. <a href='guest-login.php'>Sign in →</a>";
            } else {
                $error = "Sign up failed. Email may already be registered.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Create Account — Hotel Booking</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body { position: relative; min-height: 100vh; overflow-x: hidden; }
        .img-absolute { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: -1; }
        .signup-container {
            background: rgba(0,0,0,0.75);
            padding: 40px 35px;
            border-radius: 12px;
            max-width: 520px;
            margin: 50px auto 60px;
            box-shadow: 0 0 30px rgba(0,216,178,0.35);
            border: 1px solid rgba(0,216,178,0.2);
            position: relative;
            z-index: 1;
            width: 90%;
        }
        .signup-toggle { text-align: center; margin-top: 20px; font-size: 0.95rem; }
        .signup-toggle a { color: #00d8b2; text-decoration: none; }
        .signup-toggle a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; font-size: 0.9rem; }
        .alert-danger  { background: rgba(233,42,103,0.15); border: 1px solid #e92a67; color: #ff6b9d; }
        .alert-success { background: rgba(0,216,178,0.15); border: 1px solid #00d8b2; color: #00d8b2; }
        .name-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 480px) { .name-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <img class="img-absolute" src="img/hotel-pool.jpg" alt="Hotel Background">
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

    <div class="signup-container astonish animated fadeIn">
        <h2 class="section-title">Create Account</h2>
        <p style="color: rgba(255,255,255,0.5); margin-bottom: 25px; font-size: 0.9rem;">Join LuxStay to book and manage your stays</p>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <form class="auth-form" method="POST" action="guest-signup.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN); ?>">

            <div class="name-row">
                <div class="form-group">
                    <input type="text" id="firstName" name="firstName" required
                           value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                    <label for="firstName">First Name</label>
                </div>
                <div class="form-group">
                    <input type="text" id="lastName" name="lastName" required
                           value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                    <label for="lastName">Last Name</label>
                </div>
            </div>

            <div class="form-group">
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <label for="email">Email Address</label>
            </div>

            <div class="form-group password-wrapper">
                <input type="password" id="password" name="password" required>
                <label for="password">Password (min 8 characters)</label>
                <button type="button" class="eyeball"><div class="eye"></div></button>
                <div class="beam"></div>
            </div>

            <div class="form-group password-wrapper">
                <input type="password" id="confirmPassword" name="confirmPassword" required>
                <label for="confirmPassword">Confirm Password</label>
                <button type="button" class="eyeball"><div class="eye"></div></button>
                <div class="beam"></div>
            </div>

            <button type="submit" class="btn btn-outline-teal" style="width:100%; margin-top: 10px;">Create Account</button>
        </form>

        <div class="signup-toggle">
            <p>Already have an account? <a href="guest-login.php">Sign in →</a></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="js/menu.js"></script>
    <script src="js/astonish.js"></script>
    <script src="js/nav.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eyeballs  = document.querySelectorAll('.eyeball');
            const beams     = document.querySelectorAll('.beam');

            document.addEventListener('mousemove', (e) => {
                beams.forEach(beam => {
                    const rect   = beam.getBoundingClientRect();
                    const mouseX = rect.right  + (rect.width  / 2);
                    const mouseY = rect.top    + (rect.height / 2);
                    const rad    = Math.atan2(mouseX - e.pageX, mouseY - e.pageY);
                    const deg    = (rad * (20 / Math.PI) * -1) - 350;
                    beam.style.setProperty('--beam-degrees', `${deg}deg`);
                });
            });

            eyeballs.forEach(eyeball => {
                eyeball.addEventListener('click', (e) => {
                    e.preventDefault();
                    const wrapper   = eyeball.closest('.password-wrapper');
                    const passInput = wrapper.querySelector('input[type="password"], input[type="text"]');
                    passInput.type  = passInput.type === 'password' ? 'text' : 'password';
                    wrapper.classList.toggle('show-password');
                    passInput.focus();
                });
            });
        });
    </script>
</body>
</html>