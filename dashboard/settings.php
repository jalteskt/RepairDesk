<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <aside class="w-64 bg-gray-800 min-h-screen text-white p-4">
            <div class="text-xl font-bold mb-8 px-2 flex items-center gap-2"><i class="fa-solid fa-wrench text-blue-400"></i> RepairDesk</div>
            <nav class="space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-chart-pie"></i> Overview</a>
                <a href="jobs.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-list-check"></i> Repair Jobs</a>
                <a href="settings.php" class="flex items-center gap-3 px-3 py-2 bg-blue-600 text-white rounded-lg"><i class="fa-solid fa-sliders"></i> Settings</a>
                <a href="../pages/logout.php" class="flex items-center gap-3 px-3 py-2 text-red-400 hover:bg-gray-700 rounded-lg mt-8"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Shop Configuration</h1>
            <div class="bg-white p-6 rounded-xl border border-gray-200 max-w-lg space-y-4">
                <div>
                    <label class="block text-sm font-bold mb-1">Shop Name</label>
                    <input type="text" value="RepairDesk Tech Solutions" class="w-full border p-2 rounded-lg text-sm">
                </div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Save Settings</button>
            </div>
        </main>
    </div>
</body>
</html>
