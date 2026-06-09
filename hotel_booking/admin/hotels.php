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
            if (isset($_POST['add_hotel'])) {
                $pdo->prepare("INSERT INTO hotel (HotelName,Location,City,Country,Description,StarRating,IsActive) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$_POST['hotel_name'],$_POST['location'],$_POST['city'],$_POST['country'],$_POST['description'],$_POST['star_rating'],isset($_POST['is_active'])?1:0]);
                $success = 'Hotel added!';
            } elseif (isset($_POST['update_hotel'])) {
                $pdo->prepare("UPDATE hotel SET HotelName=?,Location=?,City=?,Country=?,Description=?,StarRating=?,IsActive=? WHERE HotelID=?")
                    ->execute([$_POST['hotel_name'],$_POST['location'],$_POST['city'],$_POST['country'],$_POST['description'],$_POST['star_rating'],isset($_POST['is_active'])?1:0,$_POST['hotel_id']]);
                $success = 'Hotel updated!';
            } elseif (isset($_POST['delete_hotel'])) {
                $pdo->prepare("UPDATE hotel SET IsActive=0 WHERE HotelID=?")->execute([$_POST['hotel_id']]);
                $success = 'Hotel deactivated.';
            }
        } catch (PDOException $e) { $error = 'DB error: '.$e->getMessage(); }
    }
}

$hotels = $pdo->query("SELECT h.*, COUNT(r.RoomID) AS room_count FROM hotel h LEFT JOIN room r ON h.HotelID=r.HotelID AND r.IsActive=1 GROUP BY h.HotelID ORDER BY h.HotelName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Manage Hotels - LuxStay</title><link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/styles.css"><style>
body{background:#0f0f0f;padding-top:62px;}
.mgmt-wrap{max-width:1200px;margin:0 auto;padding:30px 24px;}
.panel{background:rgba(0,0,0,0.6);border:1px solid rgba(0,216,178,0.12);border-radius:12px;padding:20px;margin-bottom:30px;}
.form-group{margin-bottom:15px;}
.form-group label{color:#00d8b2;display:block;margin-bottom:5px;}
.form-group input, .form-group select, .form-group textarea{width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;padding:8px 12px;}
table{width:100%;border-collapse:collapse;background:rgba(0,0,0,0.5);}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.1);}
th{color:#00d8b2;}
.btn{background:#00d8b2;color:#111;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;}
.btn-outline{background:transparent;border:1px solid #00d8b2;color:#00d8b2;padding:4px 12px;border-radius:6px;cursor:pointer;}
</style></head>
<body>
    <?php include '_nav.php'; ?>
    <div class="mgmt-wrap">
        <h2>Manage Hotels</h2>
        <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
        <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>

        <div class="panel">
            <h3>Add New Hotel</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                <div class="form-group"><label>Hotel Name</label><input type="text" name="hotel_name" required></div>
                <div class="form-group"><label>Location/Address</label><input type="text" name="location" required></div>
                <div class="form-group"><label>City</label><select name="city"><?php foreach(['Limassol','Paphos','Kyrenia','Nicosia','Ayia Napa'] as $c):?><option><?php echo $c;?></option><?php endforeach;?></select></div>
                <div class="form-group"><label>Country</label><input type="text" name="country" value="Cyprus"></div>
                <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                <div class="form-group"><label>Star Rating</label><select name="star_rating"><?php for($i=1;$i<=5;$i++):?><option value="<?php echo $i;?>"><?php echo $i;?> Stars</option><?php endfor;?></select></div>
                <div class="form-group"><label><input type="checkbox" name="is_active" checked> Active</label></div>
                <button type="submit" name="add_hotel" class="btn">Add Hotel</button>
            </form>
        </div>

        <div class="panel">
            <h3>Existing Hotels</h3>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>City</th><th>Stars</th><th>Rooms</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($hotels as $h):?>
                <tr>
                    <td><?php echo $h['HotelID'];?></td>
                    <td><?php echo htmlspecialchars($h['HotelName']);?></td>
                    <td><?php echo htmlspecialchars($h['City']);?></td>
                    <td><?php echo str_repeat('★', $h['StarRating']);?></td>
                    <td><?php echo $h['room_count'];?></td>
                    <td><?php echo $h['IsActive']?'Active':'Inactive';?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:4px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                            <input type="hidden" name="hotel_id" value="<?php echo $h['HotelID'];?>">
                            <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($h['HotelName']);?>" required>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($h['Location']);?>" required>
                            <select name="city"><?php foreach(['Limassol','Paphos','Kyrenia','Nicosia','Ayia Napa'] as $c):?><option <?php echo $h['City']==$c?'selected':'';?>><?php echo $c;?></option><?php endforeach;?></select>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($h['Country']);?>">
                            <textarea name="description"><?php echo htmlspecialchars($h['Description']);?></textarea>
                            <select name="star_rating"><?php for($i=1;$i<=5;$i++):?><option value="<?php echo $i;?>" <?php echo $h['StarRating']==$i?'selected':'';?>><?php echo $i;?></option><?php endfor;?></select>
                            <label><input type="checkbox" name="is_active" <?php echo $h['IsActive']?'checked':'';?>> Active</label>
                            <button type="submit" name="update_hotel" class="btn-outline">Update</button>
                            <button type="submit" name="delete_hotel" class="btn-outline" onclick="return confirm('Deactivate this hotel?')">Deactivate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>