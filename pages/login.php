<?php
require_once __DIR__ . '/../includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $user = login_user($email, $password);
        if ($user) {
            if ($user['role'] === 'customer') {
                header("Location: track.php");
            } else {
                header("Location: ../dashboard/index.php");
            }
            exit;
        } else {
            $error = 'Invalid email address or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-md border border-gray-200">
        <div class="text-center mb-6">
            <a href="../index.php" class="text-3xl font-bold text-blue-600"><i class="fa-solid fa-wrench mr-2"></i>RepairDesk</a>
            <h2 class="text-xl font-semibold text-gray-700 mt-2">Sign in to your account</h2>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold transition">Log In</button>
        </form>

        <!-- Default Credentials Callout for Testing -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg text-xs text-blue-800 border border-blue-200 space-y-1">
            <p class="font-bold">Default Seed Credentials (Password: Password123!)</p>
            <p><strong>Admin:</strong> admin@repairdesk.local</p>
            <p><strong>Technician:</strong> tech@repairdesk.local</p>
            <p><strong>Customer:</strong> customer@repairdesk.local</p>
        </div>

        <div class="mt-6 text-center text-sm text-gray-600">
            Don't have an account? <a href="signup.php" class="text-blue-600 font-semibold hover:underline">Sign up</a>
        </div>
    </div>
</body>
</html>
