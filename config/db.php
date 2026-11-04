<?php
// Database Configuration (Works for both local XAMPP and InfinityFree)
$host = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]; // strip port (e.g. localhost:8080)

if ($host === 'localhost' || $host === '127.0.0.1' || str_contains($host, '192.168.')) {
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'repairdesk_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // InfinityFree Configuration (Replace with your InfinityFree MySQL Details)
    define('DB_HOST', 'sqlxxx.epizy.com');
    define('DB_NAME', 'epiz_xxxx_repairdesk');
    define('DB_USER', 'epiz_xxxx');
    define('DB_PASS', 'YOUR_INFINITYFREE_PASSWORD');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
