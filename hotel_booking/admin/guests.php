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
        $error = 'Invalid CSRF token';
    } else {
        try {
            if (isset($_POST['add_guest'])) {
                $pdo->prepare("INSERT INTO guest (FullName, Email, Phone, Nationality, PassportNumber) VALUES (?,?,?,?,?)")
                    ->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['nationality'], $_POST['passport_number']]);
                $success = 'Guest added!';
            } elseif (isset($_POST['update_guest'])) {
                $pdo->prepare("UPDATE guest SET FullName=?, Email=?, Phone=?, Nationality=?, PassportNumber=? WHERE GuestID=?")
                    ->execute([$_POST['full_name'], $_POST['email'], $_POST['phone'], $_POST['nationality'], $_POST['passport_number'], $_POST['guest_id']]);
                $success = 'Guest updated!';
            } elseif (isset($_POST['delete_guest'])) {
                $pdo->prepare("DELETE FROM guest WHERE GuestID=?")->execute([$_POST['guest_id']]);
                $success = 'Guest deleted!';
            }
        } catch (PDOException $e) { $error = 'DB error: '.$e->getMessage(); }
    }
}
$guests = $pdo->query("SELECT * FROM guest ORDER BY FullName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Manage Guests - LuxStay</title><link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"><link rel="stylesheet" href="../css/styles.css"><style>
body{background:#0f0f0f;padding-top:62px;}
.mgmt-wrap{max-width:1200px;margin:0 auto;padding:30px 24px;}
.panel{background:rgba(0,0,0,0.6);border:1px solid rgba(0,216,178,0.12);border-radius:12px;padding:20px;margin-bottom:30px;}
.form-group{margin-bottom:15px;}
.form-group label{color:#00d8b2;display:block;margin-bottom:5px;}
.form-group input{width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;padding:8px 12px;}
table{width:100%;border-collapse:collapse;background:rgba(0,0,0,0.5);}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.1);}
th{color:#00d8b2;}
.btn{background:#00d8b2;color:#111;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;}
.btn-outline{background:transparent;border:1px solid #00d8b2;color:#00d8b2;padding:4px 12px;border-radius:6px;cursor:pointer;}
</style></head>
<body>
    <?php include '_nav.php'; ?>
    <div class="mgmt-wrap">
        <h2>Guest Management</h2>
        <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
        <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>
        <div class="panel">
            <h3>Add New Guest</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Phone</label><input type="tel" name="phone"></div>
                <div class="form-group"><label>Nationality</label><input type="text" name="nationality"></div>
                <div class="form-group"><label>Passport Number</label><input type="text" name="passport_number"></div>
                <button type="submit" name="add_guest" class="btn">Add Guest</button>
            </form>
        </div>
        <div class="panel">
            <h3>Existing Guests</h3>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Nationality</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($guests as $g):?>
                <tr>
                    <td><?php echo $g['GuestID'];?></td>
                    <td><?php echo htmlspecialchars($g['FullName']);?></td>
                    <td><?php echo htmlspecialchars($g['Email']);?></td>
                    <td><?php echo htmlspecialchars($g['Phone']??'N/A');?></td>
                    <td><?php echo htmlspecialchars($g['Nationality']??'N/A');?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:4px; flex-wrap:wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(CSRF_TOKEN);?>">
                            <input type="hidden" name="guest_id" value="<?php echo $g['GuestID'];?>">
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($g['FullName']);?>" required>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($g['Email']);?>" required>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($g['Phone']??'');?>">
                            <input type="text" name="nationality" value="<?php echo htmlspecialchars($g['Nationality']??'');?>">
                            <input type="text" name="passport_number" value="<?php echo htmlspecialchars($g['PassportNumber']??'');?>">
                            <button type="submit" name="update_guest" class="btn-outline">Update</button>
                            <button type="submit" name="delete_guest" class="btn-outline" onclick="return confirm('Delete this guest?')">Delete</button>
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