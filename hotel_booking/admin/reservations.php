<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

// Allow both admin and staff
if (!isLoggedIn() || !in_array(getCurrentUser()['role'], ['admin', 'staff'])) {
    header("Location: ../staff-login.php");
    exit();
}
$user = getCurrentUser();
$error = $success = '';

// Handle status update (allowed for both)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token.';
    } else {
        try {
            if (isset($_POST['update_status'])) {
                $allowed = ['pending','confirmed','cancelled','completed'];
                $newStatus = in_array($_POST['new_status'], $allowed) ? $_POST['new_status'] : 'pending';
                $pdo->prepare("UPDATE reservation SET Status=? WHERE ReservationID=?")
                    ->execute([$newStatus, (int)$_POST['reservation_id']]);

                // Notify guest
                $res = $pdo->prepare("SELECT res.*, g.UserID, h.HotelName FROM reservation res JOIN guest g ON res.GuestID=g.GuestID JOIN room r ON res.RoomID=r.RoomID JOIN hotel h ON r.HotelID=h.HotelID WHERE res.ReservationID=?");
                $res->execute([(int)$_POST['reservation_id']]);
                $resData = $res->fetch();
                if ($resData) {
                    sendNotification($resData['UserID'],
                        "Your reservation #{$resData['ReservationID']} at {$resData['HotelName']} has been updated to: $newStatus.");
                }
                logAudit($user['user_id'], 'update_reservation_status', "Reservation #{$_POST['reservation_id']} → $newStatus");
                $success = "Reservation #{$_POST['reservation_id']} updated to $newStatus.";
            } 
            // Delete only allowed for admin
            elseif (isset($_POST['delete_reservation']) && $user['role'] === 'admin') {
                $pdo->prepare("DELETE FROM payment WHERE ReservationID=?")->execute([(int)$_POST['reservation_id']]);
                $pdo->prepare("DELETE FROM reservation WHERE ReservationID=?")->execute([(int)$_POST['reservation_id']]);
                $success = "Reservation #{$_POST['reservation_id']} deleted.";
            }
        } catch (PDOException $e) {
            $error = 'DB error: ' . $e->getMessage();
        }
    }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterHotel  = (int)($_GET['hotel_id'] ?? 0);
$search       = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = 'res.Status=?'; $params[] = $filterStatus; }
if ($filterHotel)  { $where[] = 'h.HotelID=?';  $params[] = $filterHotel;  }
if ($search)       { $where[] = '(g.FullName LIKE ? OR g.Email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("
    SELECT res.*, g.FullName, g.Email, r.RoomNumber, r.RoomType,
           h.HotelName, h.City, py.Status AS PaymentStatus, py.TransactionID
    FROM reservation res
    JOIN guest g  ON res.GuestID = g.GuestID
    JOIN room r   ON res.RoomID  = r.RoomID
    JOIN hotel h  ON r.HotelID   = h.HotelID
    LEFT JOIN payment py ON res.ReservationID = py.ReservationID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY res.BookingDate DESC
");
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$hotels = $pdo->query("SELECT HotelID, HotelName FROM hotel WHERE IsActive=1 ORDER BY HotelName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Reservations - LuxStay</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* same styles as before – kept unchanged */
        body { background: #0f0f0f; padding-top: 62px; }
        .mgmt-wrap { max-width: 1300px; margin: 0 auto; padding: 30px 24px; }
        .page-title h1 { color: #fff; margin-bottom: 20px; }
        .filter-bar { background: rgba(0,0,0,0.6); border: 1px solid rgba(0,216,178,0.12); border-radius: 10px; padding: 16px 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 24px; }
        .filter-bar .f-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 140px; }
        .filter-bar label { color: rgba(255,255,255,0.4); font-size: 0.72rem; text-transform: uppercase; }
        .filter-bar input, .filter-bar select { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; padding: 8px 12px; }
        .btn-filter { background: linear-gradient(135deg,#00d8b2,#009688); color: #111; border: none; border-radius: 6px; padding: 9px 20px; font-weight: 700; cursor: pointer; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
        .alert-success { background: rgba(0,216,178,0.1); border: 1px solid rgba(0,216,178,0.3); color: #00d8b2; }
        .alert-danger  { background: rgba(233,42,103,0.1); border: 1px solid #e92a67; color: #ff6b9d; }
        .panel { background: rgba(0,0,0,0.6); border: 1px solid rgba(0,216,178,0.12); border-radius: 12px; overflow: hidden; }
        .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,216,178,0.1); }
        .panel-head h3 { color: #00d8b2; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; }
        .pill.confirmed { background: rgba(0,216,178,0.12); color: #00d8b2; border: 1px solid rgba(0,216,178,0.25); }
        .pill.pending   { background: rgba(240,180,41,0.12); color: #f0b429; }
        .pill.cancelled { background: rgba(233,42,103,0.12); color: #e92a67; }
        .action-form select, .action-form button { padding: 4px 8px; font-size: 0.7rem; margin: 0 2px; }
    </style>
</head>
<body>
    <?php include '_nav.php'; ?>
    <div class="mgmt-wrap">
        <div class="page-title"><h1><i class="fa fa-calendar"></i> Manage Reservations</h1></div>
        <?php if ($error):   ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <form class="filter-bar" method="GET">
            <div class="f-group"><label>Search Guest</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="f-group"><label>Status</label><select name="status"><option value="">All</option><?php foreach(['pending','confirmed','cancelled','completed'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $filterStatus===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
            <div class="f-group"><label>Hotel</label><select name="hotel_id"><option value="">All</option><?php foreach($hotels as $h): ?><option value="<?php echo $h['HotelID']; ?>" <?php echo $filterHotel==$h['HotelID']?'selected':''; ?>><?php echo htmlspecialchars($h['HotelName']); ?></option><?php endforeach; ?></select></div>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="reservations.php" style="align-self:center; color:rgba(255,255,255,0.3);">Reset</a>
        </form>

        <div class="panel">
            <div class="panel-head"><h3>Reservations (<?php echo count($reservations); ?>)</h3></div>
            <div style="overflow-x:auto;">
            <td>
                <thead><tr><th>ID</th><th>Guest</th><th>Hotel</th><th>Room</th><th>Dates</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($reservations as $r): ?>
                <tr>
                    <td><?php echo $r['ReservationID']; ?></td>
                    <td><?php echo htmlspecialchars($r['FullName']); ?><br><small><?php echo htmlspecialchars($r['Email']); ?></small></td>
                    <td><?php echo htmlspecialchars($r['HotelName']); ?><br><small><?php echo $r['City']; ?></small></td>
                    <td><?php echo ucfirst($r['RoomType']); ?> <?php echo $r['RoomNumber']; ?></td>
                    <td><?php echo date('d M Y', strtotime($r['CheckInDate'])); ?> → <?php echo date('d M Y', strtotime($r['CheckOutDate'])); ?></td>
                    <td style="color:#00d8b2;">€<?php echo number_format($r['TotalCost'],2); ?></td>
                    <td><span class="pill <?php echo $r['Status']; ?>"><?php echo $r['Status']; ?></span></td>
                    <td>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN); ?>">
                            <input type="hidden" name="reservation_id" value="<?php echo $r['ReservationID']; ?>">
                            <select name="new_status">
                                <?php foreach(['pending','confirmed','cancelled','completed'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $r['Status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                            <?php if ($user['role'] === 'admin'): ?>
                                <button type="submit" name="delete_reservation" onclick="return confirm('Delete this reservation?')">Delete</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>