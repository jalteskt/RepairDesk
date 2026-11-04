<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RepairDesk - Smart Repair Shop Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600"><i class="fa-solid fa-wrench mr-2"></i>RepairDesk</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="pages/track.php" class="text-gray-600 hover:text-blue-600 font-medium">Track Repair</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard/index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Dashboard</a>
                        <a href="pages/logout.php" class="text-gray-500 hover:text-red-600 ml-2">Logout</a>
                    <?php else: ?>
                        <a href="pages/login.php" class="text-gray-600 hover:text-blue-600 font-medium">Log In</a>
                        <a href="pages/signup.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Streamlined Repair & Ticket Tracking System</h1>
            <p class="text-xl mb-8 text-blue-100">Manage device repairs, technicians, diagnostic progress, and client status lookups with ease.</p>

            <!-- Quick Track Box -->
            <div class="max-w-xl mx-auto bg-white rounded-xl p-4 shadow-lg text-gray-800">
                <form action="pages/track.php" method="GET" class="flex gap-2">
                    <input type="text" name="ticket" maxlength="6" pattern="[0-9]{6}" required placeholder="Enter 6-digit Ticket Number (e.g. 849201)" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Track Job</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Instant Tracking</h3>
                <p class="text-gray-600">Customers can track repair statuses in real-time using their assigned 6-digit ticket number.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Technician Workflow</h3>
                <p class="text-gray-600">Technicians can log diagnostic updates, adjust costs, and advance ticket statuses seamlessly.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Admin Oversight</h3>
                <p class="text-gray-600">Comprehensive overview of revenue, active jobs, customer management, and technician assignments.</p>
            </div>
        </div>
    </section>

    <footer class="bg-gray-800 text-gray-400 py-8 border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> RepairDesk System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
