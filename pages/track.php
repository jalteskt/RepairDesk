<?php
require_once __DIR__ . '/../includes/auth.php';

$ticket = trim($_GET['ticket'] ?? '');
$job = null;
$logs = [];
$error = '';

if (!empty($ticket)) {
    if (preg_match('/^[0-9]{6}$/', $ticket)) {
        $stmt = $pdo->prepare("
            SELECT j.*, c.name as customer_name, u.full_name as tech_name
            FROM jobs j
            JOIN customers c ON j.customer_id = c.id
            LEFT JOIN users u ON j.technician_id = u.id
            WHERE j.ticket_number = ?
        ");
        $stmt->execute([$ticket]);
        $job = $stmt->fetch();

        if ($job) {
            $stmtLogs = $pdo->prepare("SELECT jl.*, u.full_name FROM job_logs jl JOIN users u ON jl.user_id = u.id WHERE jl.job_id = ? ORDER BY jl.created_at DESC");
            $stmtLogs->execute([$job['id']]);
            $logs = $stmtLogs->fetchAll();
        } else {
            $error = 'No repair job found matching ticket number ' . htmlspecialchars($ticket);
        }
    } else {
        $error = 'Ticket number must be exactly 6 digits.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Repair Status - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <nav class="bg-white border-b border-gray-200 py-4 mb-8">
        <div class="max-w-5xl mx-auto px-4 flex justify-between items-center">
            <a href="../index.php" class="text-2xl font-bold text-blue-600"><i class="fa-solid fa-wrench mr-2"></i>RepairDesk</a>
            <a href="login.php" class="text-sm font-semibold text-gray-600 hover:text-blue-600">Staff Portal</a>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 text-center">
            <h1 class="text-2xl font-bold mb-2">Track Repair Progress</h1>
            <p class="text-gray-500 mb-6 text-sm">Enter your 6-digit repair ticket number to check real-time status updates.</p>

            <form method="GET" action="track.php" class="flex gap-2 max-w-md mx-auto">
                <input type="text" name="ticket" value="<?php echo htmlspecialchars($ticket); ?>" maxlength="6" pattern="[0-9]{6}" required placeholder="6-Digit Code (e.g. 849201)" class="flex-1 px-4 py-2 border rounded-lg text-center font-mono text-lg tracking-wider focus:ring-2 focus:ring-blue-500 outline-none">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Search</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-center mb-8">
                <i class="fa-solid fa-circle-exclamation mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($job): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <span class="text-xs uppercase tracking-wide text-gray-500 font-bold">Ticket Number</span>
                        <h2 class="text-2xl font-extrabold text-blue-600">#<?php echo htmlspecialchars($job['ticket_number']); ?></h2>
                    </div>
                    <span class="px-3 py-1 text-xs font-bold rounded-full uppercase <?php
                        echo match($job['status']) {
                            'received' => 'bg-gray-100 text-gray-800',
                            'diagnosing' => 'bg-yellow-100 text-yellow-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'ready' => 'bg-green-100 text-green-800',
                            'completed' => 'bg-emerald-100 text-emerald-800',
                            default => 'bg-red-100 text-red-800'
                        };
                    ?>"><?php echo str_replace('_', ' ', $job['status']); ?></span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-100 text-sm">
                    <div><span class="text-gray-500 block">Customer Name</span><strong><?php echo htmlspecialchars($job['customer_name']); ?></strong></div>
                    <div><span class="text-gray-500 block">Device / Model</span><strong><?php echo htmlspecialchars($job['device_category'] . ' - ' . $job['device_model']); ?></strong></div>
                    <div><span class="text-gray-500 block">Assigned Technician</span><strong><?php echo htmlspecialchars($job['tech_name'] ?? 'Pending Assignment'); ?></strong></div>
                    <div><span class="text-gray-500 block">Estimated Cost</span><strong>₱<?php echo number_format($job['estimated_cost'], 2); ?></strong></div>
                    <div class="md:col-span-2"><span class="text-gray-500 block">Reported Issue</span><p class="mt-1 bg-gray-50 p-3 rounded-lg text-gray-700"><?php echo nl2br(htmlspecialchars($job['issue_description'])); ?></p></div>
                </div>

                <!-- Progress History -->
                <div class="p-6">
                    <h3 class="font-bold text-gray-800 mb-4"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Status History</h3>
                    <?php if (empty($logs)): ?>
                        <p class="text-xs text-gray-500 italic">No specific logs uploaded yet. Check back soon.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($logs as $log): ?>
                                <div class="border-l-2 border-blue-500 pl-4 py-1">
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span class="font-bold text-gray-700"><?php echo htmlspecialchars(str_replace('_', ' ', $log['new_status'])); ?></span>
                                        <span><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></span>
                                    </div>
                                    <?php if ($log['note']): ?>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($log['note']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
