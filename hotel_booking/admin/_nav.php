<?php
// admin/_nav.php - for admin users only
require_once '../includes/auth_functions.php';
$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    header("Location: ../staff-login.php");
    exit();
}
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="main-nav">
    <div class="content-wrapper-sm">
        <a href="../index.php" class="navbar-brand">LuxStay Admin</a>
        <div id="menu-button">
            <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="reservations.php">Reservations</a></li>
            <li><a href="hotels.php">Hotels</a></li>
            <li><a href="rooms.php">Rooms</a></li>
            <li><a href="guests.php">Guests</a></li>
            <li><a href="../admin_reports.php">Reports</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
    </div>
</nav>
<style>
    .main-nav { background: rgba(0,0,0,0.9); border-bottom: 1px solid rgba(0,216,178,0.2); }
    .nav-links a.active { color: #00d8b2; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('#menu-button').click(function(){
        $('.nav-links').toggleClass('active');
    });
});
</script>