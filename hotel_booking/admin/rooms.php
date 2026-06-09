<?php
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header("Location: ../staff-login.php");
    exit();
}
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== CSRF_TOKEN) {
        $error = 'Invalid CSRF token.';
    } else {
        try {
            if (isset($_POST['add_room'])) {
                $pdo->prepare("INSERT INTO room (HotelID,RoomNumber,RoomType,Description,PricePerNight,MaxGuests,AvailableRooms,IsActive) VALUES (?,?,?,?,?,?,?,1)")
                    ->execute([$_POST['hotel_id'],$_POST['room_number'],$_POST['room_type'],$_POST['description'],$_POST['price'],$_POST['max_guests'],$_POST['available_rooms']]);
                $success = 'Room added!';
            } elseif (isset($_POST['update_room'])) {
                $pdo->prepare("UPDATE room SET HotelID=?,RoomNumber=?,RoomType=?,Description=?,PricePerNight=?,MaxGuests=?,AvailableRooms=?,IsActive=? WHERE RoomID=?")
                    ->execute([$_POST['hotel_id'],$_POST['room_number'],$_POST['room_type'],$_POST['description'],$_POST['price'],$_POST['max_guests'],$_POST['available_rooms'],isset($_POST['is_active'])?1:0,$_POST['room_id']]);
                $success = 'Room updated!';
            } elseif (isset($_POST['delete_room'])) {
                $pdo->prepare("UPDATE room SET IsActive=0 WHERE RoomID=?")->execute([$_POST['room_id']]);
                $success = 'Room deactivated.';
            }
        } catch (PDOException $e) { $error = 'DB error: '.$e->getMessage(); }
    }
}

$hotels = $pdo->query("SELECT HotelID, HotelName, City FROM hotel WHERE IsActive=1 ORDER BY HotelName")->fetchAll();
$filterHotel = (int)($_GET['hotel_id'] ?? 0);
$roomsStmt = $filterHotel
    ? $pdo->prepare("SELECT r.*, h.HotelName, h.City FROM room r JOIN hotel h ON r.HotelID=h.HotelID WHERE r.HotelID=? ORDER BY h.HotelName, r.RoomNumber")
    : $pdo->prepare("SELECT r.*, h.HotelName, h.City FROM room r JOIN hotel h ON r.HotelID=h.HotelID ORDER BY h.HotelName, r.RoomNumber");
$filterHotel ? $roomsStmt->execute([$filterHotel]) : $roomsStmt->execute();
$rooms = $roomsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Manage Rooms - LuxStay</title><link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/styles.css"><style>
body{background:#0f0f0f;padding-top:62px;}
.mgmt-wrap{max-width:1300px;margin:0 auto;padding:30px 24px;}
.panel{background:rgba(0,0,0,0.6);border:1px solid rgba(0,216,178,0.12);border-radius:12px;padding:20px;margin-bottom:30px;}
.form-group{margin-bottom:15px;}
.form-group label{color:#00d8b2;display:block;margin-bottom:5px;}
.form-group input, .form-group select, .form-group textarea{width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;padding:8px 12px;}
table{width:100%;border-collapse:collapse;background:rgba(0,0,0,0.5);}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.1);}
th{color:#00d8b2;}
.btn{background:#00d8b2;color:#111;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;}
.btn-outline{background:transparent;border:1px solid #00d8b2;color:#00d8b2;padding:4px 12px;border-radius:6px;cursor:pointer;}
.filter-bar{display:flex;gap:10px;margin-bottom:20px;align-items:flex-end;}
</style></head>
<body>
    <?php include '_nav.php'; ?>
    <div class="mgmt-wrap">
        <h2>Manage Rooms</h2>
        <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
        <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>

        <div class="panel">
            <h3>Add New Room</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                <div class="form-group"><label>Hotel</label><select name="hotel_id" required><?php foreach($hotels as $h):?><option value="<?php echo $h['HotelID'];?>"><?php echo htmlspecialchars($h['HotelName'])." ({$h['City']})";?></option><?php endforeach;?></select></div>
                <div class="form-group"><label>Room Number</label><input type="text" name="room_number" required></div>
                <div class="form-group"><label>Room Type</label><select name="room_type"><?php foreach(['single','double','suite','deluxe','family'] as $t):?><option value="<?php echo $t;?>"><?php echo ucfirst($t);?></option><?php endforeach;?></select></div>
                <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                <div class="form-group"><label>Price Per Night (€)</label><input type="number" name="price" step="0.01" required></div>
                <div class="form-group"><label>Max Guests</label><input type="number" name="max_guests" value="2" required></div>
                <div class="form-group"><label>Available Rooms</label><input type="number" name="available_rooms" value="1" required></div>
                <button type="submit" name="add_room" class="btn">Add Room</button>
            </form>
        </div>

        <div class="panel">
            <h3>Existing Rooms</h3>
            <div class="filter-bar">
                <form method="GET" style="display:flex; gap:6px;">
                    <select name="hotel_id"><option value="">All Hotels</option><?php foreach($hotels as $h):?><option value="<?php echo $h['HotelID'];?>" <?php echo $filterHotel==$h['HotelID']?'selected':'';?>><?php echo htmlspecialchars($h['HotelName']);?></option><?php endforeach;?></select>
                    <button type="submit" class="btn-outline">Filter</button>
                    <a href="rooms.php" class="btn-outline">Reset</a>
                </form>
            </div>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>ID</th><th>Hotel</th><th>Room#</th><th>Type</th><th>Price</th><th>Max Guests</th><th>Available</th><th>Active</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($rooms as $r):?>
                <tr>
                    <td><?php echo $r['RoomID'];?></td>
                    <td><?php echo htmlspecialchars($r['HotelName']);?><br><small><?php echo $r['City'];?></small></td>
                    <td><?php echo htmlspecialchars($r['RoomNumber']);?></td>
                    <td><?php echo ucfirst($r['RoomType']);?></td>
                    <td>€<?php echo number_format($r['PricePerNight'],2);?></td>
                    <td><?php echo $r['MaxGuests'];?></td>
                    <td><?php echo $r['AvailableRooms'];?></td>
                    <td><?php echo $r['IsActive']?'Yes':'No';?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:4px; flex-wrap:wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                            <input type="hidden" name="room_id" value="<?php echo $r['RoomID'];?>">
                            <select name="hotel_id"><?php foreach($hotels as $h):?><option value="<?php echo $h['HotelID'];?>" <?php echo $h['HotelID']==$r['HotelID']?'selected':'';?>><?php echo htmlspecialchars($h['HotelName']);?></option><?php endforeach;?></select>
                            <input type="text" name="room_number" value="<?php echo htmlspecialchars($r['RoomNumber']);?>" required style="width:70px;">
                            <select name="room_type"><?php foreach(['single','double','suite','deluxe','family'] as $t):?><option value="<?php echo $t;?>" <?php echo $r['RoomType']==$t?'selected':'';?>><?php echo ucfirst($t);?></option><?php endforeach;?></select>
                            <input type="text" name="description" value="<?php echo htmlspecialchars($r['Description']);?>" placeholder="desc">
                            <input type="number" name="price" step="0.01" value="<?php echo $r['PricePerNight'];?>" style="width:80px;">
                            <input type="number" name="max_guests" value="<?php echo $r['MaxGuests'];?>" style="width:60px;">
                            <input type="number" name="available_rooms" value="<?php echo $r['AvailableRooms'];?>" style="width:60px;">
                            <label><input type="checkbox" name="is_active" <?php echo $r['IsActive']?'checked':'';?>> Active</label>
                            <button type="submit" name="update_room" class="btn-outline">Update</button>
                            <button type="submit" name="delete_room" class="btn-outline" onclick="return confirm('Deactivate this room?')">Deactivate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>