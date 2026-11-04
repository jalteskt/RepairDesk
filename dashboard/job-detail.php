<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['admin', 'technician']);

$job_id = (int)($_GET['id'] ?? 0);

// Fetch Job Details
$stmt = $pdo->prepare("
    SELECT j.*, c.name as customer_name, c.phone as customer_phone, u.full_name as tech_name
    FROM jobs j
    JOIN customers c ON j.customer_id = c.id
    LEFT JOIN users u ON j.technician_id = u.id
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    die("Job record not found.");
}

// Handle Status & Log Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $final_cost = (float)$_POST['final_cost'];
    $diag_notes = trim($_POST['diagnostic_notes']);
    $log_note = trim($_POST['log_note']);

    $prev_status = $job['status'];

    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("UPDATE jobs SET status = ?, final_cost = ?, diagnostic_notes = ? WHERE id = ?");
    $stmtUpdate->execute([$new_status, $final_cost, $diag_notes, $job_id]);

    $stmtLog = $pdo->prepare("INSERT INTO job_logs (job_id, user_id, previous_status, new_status, note) VALUES (?, ?, ?, ?, ?)");
    $stmtLog->execute([$job_id, $_SESSION['user_id'], $prev_status, $new_status, $log_note]);

    $pdo->commit();

    header("Location: job-detail.php?id=" . $job_id);
    exit;
}

// Fetch Audit Logs
$stmtLogs = $pdo->prepare("SELECT jl.*, u.full_name FROM job_logs jl JOIN users u ON jl.user_id = u.id WHERE jl.job_id = ? ORDER BY jl.created_at DESC");
$stmtLogs->execute([$job_id]);
$logs = $stmtLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket Details - RepairDesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-4 flex justify-between items-center">
            <a href="jobs.php" class="text-blue-600 hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Jobs</a>
            <span class="text-xs bg-gray-200 px-3 py-1 rounded-full font-mono font-bold">Ticket #<?php echo $job['ticket_number']; ?></span>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($job['device_category'] . ' - ' . $job['device_model']); ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm border-b pb-4 mb-4">
                <div><span class="text-gray-500 block">Customer</span><strong><?php echo htmlspecialchars($job['customer_name']); ?> (<?php echo htmlspecialchars($job['customer_phone']); ?>)</strong></div>
                <div><span class="text-gray-500 block">Assigned Tech</span><strong><?php echo htmlspecialchars($job['tech_name'] ?? 'Unassigned'); ?></strong></div>
                <div><span class="text-gray-500 block">Current Status</span><strong class="uppercase text-blue-600"><?php echo str_replace('_', ' ', $job['status']); ?></strong></div>
            </div>

            <form method="POST" action="job-detail.php?id=<?php echo $job_id; ?>" class="space-y-4">
                <input type="hidden" name="update_status" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Update Status</label>
                        <select name="status" class="w-full border p-2 rounded-lg text-sm">
                            <?php
                            $statuses = ['received', 'diagnosing', 'in_progress', 'waiting_parts', 'ready', 'completed', 'cancelled'];
                            foreach ($statuses as $st):
                            ?>
                                <option value="<?php echo $st; ?>" <?php echo $job['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Final Total Cost (₱)</label>
                        <input type="number" step="0.01" name="final_cost" value="<?php echo $job['final_cost']; ?>" class="w-full border p-2 rounded-lg text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Internal Diagnostic Notes</label>
                    <textarea name="diagnostic_notes" class="w-full border p-2 rounded-lg text-sm" rows="3"><?php echo htmlspecialchars($job['diagnostic_notes'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Public Log Note (Visible on Client Tracker)</label>
                    <input type="text" name="log_note" placeholder="e.g. Replaced display panel. Device undergoing testing." class="w-full border p-2 rounded-lg text-sm">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Update Ticket & Log Progress</button>
            </form>
        </div>

        <!-- History Log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fa-solid fa-list-ol mr-2"></i>Status & Action Logs</h3>
            <?php if (empty($logs)): ?>
                <p class="text-sm text-gray-500">No updates logged yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($logs as $l): ?>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 text-xs">
                            <div class="flex justify-between font-bold text-gray-700 mb-1">
                                <span><?php echo htmlspecialchars($l['full_name']); ?> changed status to <span class="uppercase text-blue-600"><?php echo str_replace('_', ' ', $l['new_status']); ?></span></span>
                                <span class="text-gray-400"><?php echo date('M d, Y g:i A', strtotime($l['created_at'])); ?></span>
                            </div>
                            <?php if ($l['note']): ?>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($l['note']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
