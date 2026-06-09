<?php
require_once 'config.php';

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function logAudit($userId, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO auditlog (UserID, Action, Details) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

function sendNotification($userId, $message) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notification (UserID, Message) VALUES (?, ?)");
        $stmt->execute([$userId, $message]);
    } catch (PDOException $e) {
        error_log("Notification failed: " . $e->getMessage());
    }
}

function registerUser($username, $password, $email, $fullName, $role = 'customer', $position = null) {
    global $pdo;
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Username = ? OR Email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            error_log("Registration failed: username or email already exists — $username / $email");
            return false;
        }

        $pdo->beginTransaction();
        $hashedPassword = hashPassword($password);

        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users (Username, Password, Email, UserType, IsActive, CreatedAt, LastLogin)
                               VALUES (?, ?, ?, ?, TRUE, NOW(), NOW())");
        $userType = ($role === 'customer') ? 'customer' : 'employee';
        $stmt->execute([$username, $hashedPassword, $email, $userType]);
        $userId = $pdo->lastInsertId();

        // Assign role
        $roleStmt = $pdo->prepare("SELECT RoleID FROM roles WHERE RoleName = ?");
        $roleStmt->execute([$role]);
        $roleId = $roleStmt->fetchColumn();
        if (!$roleId) $roleId = 3; // Default to customer RoleID

        $stmt = $pdo->prepare("INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)");
        $stmt->execute([$userId, $roleId]);

        // Insert profile record
        if ($role === 'customer') {
            $stmt = $pdo->prepare("INSERT INTO guest (UserID, FullName, Email) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $fullName, $email]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO staff (UserID, FullName, Position, HireDate) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $fullName, $position ?? 'staff']);
        }

        $pdo->commit();
        logAudit($userId, 'register', "New user registered with role: $role");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// Login — sets session variables
// ------------------------------------------------------------
function loginUser($username, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ? AND IsActive = TRUE");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("Login failed: user '$username' not found or inactive");
            return false;
        }

        if (!verifyPassword($password, $user['Password'])) {
            error_log("Login failed: wrong password for '$username'");
            return false;
        }

        // Update last login
        $pdo->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?")
            ->execute([$user['UserID']]);

        // Fetch role
        $roleStmt = $pdo->prepare("SELECT r.RoleName FROM roles r
                                   JOIN userroles ur ON r.RoleID = ur.RoleID
                                   WHERE ur.UserID = ? LIMIT 1");
        $roleStmt->execute([$user['UserID']]);
        $role = $roleStmt->fetchColumn() ?: 'customer';

        // Set session
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id']   = $user['UserID'];
        $_SESSION['username']  = $user['Username'];
        $_SESSION['email']     = $user['Email'];
        $_SESSION['role']      = $role;
        $_SESSION['user_type'] = ($role === 'customer') ? 'customer' : 'employee';

        // Load profile data
        if ($role === 'customer') {
            $stmt = $pdo->prepare("SELECT GuestID, FullName FROM guest WHERE UserID = ?");
            $stmt->execute([$user['UserID']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['full_name'] = $profile['FullName'] ?? '';
            $_SESSION['guest_id']  = $profile['GuestID'] ?? null;
            $_SESSION['position']  = '';
        } else {
            $stmt = $pdo->prepare("SELECT StaffID, FullName, Position FROM staff WHERE UserID = ?");
            $stmt->execute([$user['UserID']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['full_name'] = $profile['FullName'] ?? '';
            $_SESSION['staff_id']  = $profile['StaffID'] ?? null;
            $_SESSION['position']  = $profile['Position'] ?? '';
        }

        logAudit($user['UserID'], 'login', "Logged in as $role");
        return true;

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'   => $_SESSION['user_id']   ?? 0,
        'username'  => $_SESSION['username']  ?? '',
        'email'     => $_SESSION['email']     ?? '',
        'role'      => $_SESSION['role']      ?? '',
        'user_type' => $_SESSION['user_type'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'guest_id'  => $_SESSION['guest_id']  ?? null,
        'staff_id'  => $_SESSION['staff_id']  ?? null,
        'position'  => $_SESSION['position']  ?? '',
    ];
}

// ------------------------------------------------------------
// Logout
// ------------------------------------------------------------
function logoutUser() {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) logAudit($userId, 'logout', 'User logged out');
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>