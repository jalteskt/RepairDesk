<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['admin']); // Restrict to Admin only

$techs = $pdo->query("SELECT * FROM users WHERE role = 'technician' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Management - RepairDesk</title>
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
                <a href="customers.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-users"></i> Customers</a>
                <a href="technicians.php" class="flex items-center gap-3 px-3 py-2 bg-blue-600 text-white rounded-lg"><i class="fa-solid fa-user-gear"></i> Technicians</a>
                <a href="reports.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a>
                <a href="../pages/logout.php" class="flex items-center gap-3 px-3 py-2 text-red-400 hover:bg-gray-700 rounded-lg mt-8"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Technicians Roster</h1>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 uppercase border-b">
                            <th class="p-3">Name</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Phone</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php foreach ($techs as $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-semibold"><?php echo htmlspecialchars($t['full_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($t['email']); ?></td>
                                <td class="p-3 font-mono"><?php echo htmlspecialchars($t['phone']); ?></td>
                                <td class="p-3"><span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800 uppercase font-bold"><?php echo $t['status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
