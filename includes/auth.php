<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function login_user($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        return $user;
    }
    return false;
}

function require_auth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/login.php");
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p><a href='../dashboard/index.php'>Return to Dashboard</a></div>";
        exit;
    }
}

function logout_user() {
    session_unset();
    session_destroy();
    header("Location: ../pages/login.php");
    exit;
}

function generate_ticket_number() {
    global $pdo;
    do {
        $ticket = str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM jobs WHERE ticket_number = ?");
        $stmt->execute([$ticket]);
    } while ($stmt->fetch());
    return $ticket;
}
?>
