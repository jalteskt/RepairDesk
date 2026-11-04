<?php
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($full_name) && !empty($email) && !empty($phone) && !empty($password)) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, 'customer', ?)");
                $stmt->execute([$full_name, $email, $hash, $phone]);
                $user_id = $pdo->lastInsertId();

                $stmtCust = $pdo->prepare("INSERT INTO customers (user_id, name, email, phone) VALUES (?, ?, ?, ?)");
                $stmtCust->execute([$user_id, $full_name, $email, $phone]);

                $pdo->commit();
                $success = 'Account created successfully! You can now login.';
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($e->getCode() == 23000) {
                    $error = 'Email address is already registered.';
                } else {
                    $error = 'Error creating account: ' . $e->getMessage();
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Sign Up - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-10">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-md border border-gray-200">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Create Customer Account</h2>
            <p class="text-sm text-gray-500">Track and view all your repair requests easily</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm"><?php echo htmlspecialchars($success); ?> <a href="login.php" class="underline font-bold">Log in here</a></div>
        <?php endif; ?>

        <form method="POST" action="signup.php" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Full Name</label>
                <input type="text" name="full_name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Phone Number</label>
                <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold transition">Register Account</button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            Already have an account? <a href="login.php" class="text-blue-600 font-semibold hover:underline">Log in</a>
        </div>
    </div>
</body>
</html>
