<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['admin', 'technician']);

$message = '';
// Create New Job Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_job'])) {
    $customer_id = (int)$_POST['customer_id'];
    $device_cat = trim($_POST['device_category']);
    $device_model = trim($_POST['device_model']);
    $issue = trim($_POST['issue_description']);
    $priority = $_POST['priority'] ?? 'medium';
    $est_cost = (float)($_POST['estimated_cost'] ?? 0);

    $ticket = generate_ticket_number();

    $stmt = $pdo->prepare("INSERT INTO jobs (ticket_number, customer_id, technician_id, device_category, device_model, issue_description, priority, estimated_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ticket, $customer_id, $_SESSION['user_id'], $device_cat, $device_model, $issue, $priority, $est_cost]);

    $message = "Job created successfully with 6-Digit Ticket Number: " . $ticket;
}

// Fetch All Jobs
$stmt = $pdo->prepare("
    SELECT j.*, c.name as customer_name, u.full_name as tech_name
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN users u ON j.technician_id = u.id
    ORDER BY j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll();

// Fetch Customer List for Creation Modal
$customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Jobs - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <aside class="w-64 bg-gray-800 min-h-screen text-white p-4">
            <div class="text-xl font-bold mb-8 px-2 flex items-center gap-2"><i class="fa-solid fa-wrench text-blue-400"></i> RepairDesk</div>
            <nav class="space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-chart-pie"></i> Overview</a>
                <a href="jobs.php" class="flex items-center gap-3 px-3 py-2 bg-blue-600 text-white rounded-lg"><i class="fa-solid fa-list-check"></i> Repair Jobs</a>
                <a href="customers.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-users"></i> Customers</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="technicians.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-user-gear"></i> Technicians</a>
                    <a href="reports.php" class="flex items-center gap-3 px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg"><i class="fa-solid fa-file-invoice-dollar"></i> Reports</a>
                <?php endif; ?>
                <a href="../pages/logout.php" class="flex items-center gap-3 px-3 py-2 text-red-400 hover:bg-gray-700 rounded-lg mt-8"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Repair Tickets</h1>
                <button onclick="document.getElementById('jobModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700">+ Create Ticket</button>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-500 uppercase border-b">
                            <th class="p-3">Ticket #</th>
                            <th class="p-3">Customer</th>
                            <th class="p-3">Device Category / Model</th>
                            <th class="p-3">Priority</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php foreach ($jobs as $j): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-mono font-bold text-blue-600">#<?php echo $j['ticket_number']; ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($j['customer_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($j['device_category'] . ' - ' . $j['device_model']); ?></td>
                                <td class="p-3"><span class="uppercase text-xs font-bold px-2 py-1 rounded bg-gray-100"><?php echo $j['priority']; ?></span></td>
                                <td class="p-3"><span class="px-2 py-1 text-xs rounded-full font-semibold bg-blue-100 text-blue-800"><?php echo str_replace('_', ' ', $j['status']); ?></span></td>
                                <td class="p-3"><a href="job-detail.php?id=<?php echo $j['id']; ?>" class="text-blue-600 hover:underline font-bold">Details & Log</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create Ticket Modal -->
    <div id="jobModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-bold mb-4">Create New Repair Ticket</h3>
            <form method="POST" action="jobs.php" class="space-y-4">
                <input type="hidden" name="create_job" value="1">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Customer</label>
                    <select name="customer_id" required class="w-full border p-2 rounded-lg text-sm">
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['phone'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Category</label>
                        <input type="text" name="device_category" placeholder="Laptop, Phone..." required class="w-full border p-2 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Model</label>
                        <input type="text" name="device_model" placeholder="MacBook Pro, iPhone 13..." required class="w-full border p-2 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Issue Description</label>
                    <textarea name="issue_description" required class="w-full border p-2 rounded-lg text-sm" rows="3"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Priority</label>
                        <select name="priority" class="w-full border p-2 rounded-lg text-sm"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Estimated Cost (₱)</label>
                        <input type="number" step="0.01" name="estimated_cost" value="0.00" class="w-full border p-2 rounded-lg text-sm">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="document.getElementById('jobModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 border rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Ticket</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
