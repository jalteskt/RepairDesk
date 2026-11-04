<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['admin', 'technician']);

// Fetch Analytics
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$activeJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('received', 'diagnosing', 'in_progress', 'waiting_parts')")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'completed'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(final_cost) FROM jobs WHERE status = 'completed'")->fetchColumn() ?? 0.00;

// Fetch Recent Jobs
$stmt = $pdo->prepare("
    SELECT j.*, c.name as customer_name
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    ORDER BY j.created_at DESC LIMIT 5
");
$stmt->execute();
$recentJobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 min-h-screen text-white p-4">
            <div class="text-xl font-bold mb-8 px-2 flex items-center gap-2">
                <i class="fa-solid fa-wrench text-blue-400"></i> RepairDesk
            </div>
            <nav class="space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-3 py-2 bg-blue-600 rounded-lg text-white"><i class="fa-solid fa-chart-pie"></i> Overview</a>
                <a href="jobs.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-list-check"></i> Repair Jobs</a>
                <a href="customers.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-users"></i> Customers</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="technicians.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-user-gear"></i> Technicians</a>
                    <a href="reports.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a>
                    <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-sliders"></i> Settings</a>
                <?php endif; ?>
                <a href="../pages/logout.php" class="flex items-center gap-3 px-3 py-2 text-red-400 hover:bg-gray-700 rounded-lg mt-8"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                    <p class="text-sm text-gray-500">Role: <span class="capitalize font-semibold text-blue-600"><?php echo $_SESSION['user_role']; ?></span></p>
                </div>
                <a href="jobs.php?action=new" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700">+ New Repair Ticket</a>
            </div>

            <!-- Key Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs text-gray-500 font-bold uppercase">Total Tickets</span>
                    <p class="text-3xl font-extrabold text-gray-800 mt-2"><?php echo $totalJobs; ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs text-yellow-600 font-bold uppercase">Active In-Shop</span>
                    <p class="text-3xl font-extrabold text-yellow-600 mt-2"><?php echo $activeJobs; ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs text-green-600 font-bold uppercase">Completed</span>
                    <p class="text-3xl font-extrabold text-green-600 mt-2"><?php echo $completedJobs; ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <span class="text-xs text-blue-600 font-bold uppercase">Total Revenue</span>
                    <p class="text-3xl font-extrabold text-blue-600 mt-2">₱<?php echo number_format($totalRevenue, 2); ?></p>
                </div>
            </div>

            <!-- Recent Jobs Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 font-bold text-gray-700">Recent Repair Jobs</div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 uppercase border-b">
                            <th class="p-3">Ticket</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Device</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php foreach ($recentJobs as $j): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-mono font-bold text-blue-600">#<?php echo $j['ticket_number']; ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($j['customer_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($j['device_category'] . ' - ' . $j['device_model']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-1 text-xs rounded-full font-semibold <?php
                                        echo match($j['status']) {
                                            'received' => 'bg-gray-100 text-gray-800',
                                            'diagnosing' => 'bg-yellow-100 text-yellow-800',
                                            'in_progress' => 'bg-blue-100 text-blue-800',
                                            'ready' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-emerald-100 text-emerald-800',
                                            default => 'bg-red-100 text-red-800'
                                        };
                                    ?>"><?php echo str_replace('_', ' ', $j['status']); ?></span>
                                </td>
                                <td class="p-3">
                                    <a href="job-detail.php?id=<?php echo $j['id']; ?>" class="text-blue-600 hover:underline font-medium">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
