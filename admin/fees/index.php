<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Fetch fee statistics
$stats = [
    'total_fees' => 0,
    'paid_fees' => 0,
    'unpaid_fees' => 0,
    'overdue_fees' => 0,
    'total_amount' => 0,
    'collected_amount' => 0,
    'pending_amount' => 0
];

// Get total fees count
$result = $conn->query("SELECT COUNT(*) as count FROM fees");
$stats['total_fees'] = $result->fetch_assoc()['count'];

// Get paid fees count
$result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status = 'paid'");
$stats['paid_fees'] = $result->fetch_assoc()['count'];

// Get unpaid fees count
$result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status = 'unpaid'");
$stats['unpaid_fees'] = $result->fetch_assoc()['count'];

// Get overdue fees count
$result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status = 'unpaid' AND due_date < CURDATE()");
$stats['overdue_fees'] = $result->fetch_assoc()['count'];

// Get total amount
$result = $conn->query("SELECT SUM(amount) as total FROM fees");
$stats['total_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Get collected amount
$result = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'paid'");
$stats['collected_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Get pending amount
$result = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'unpaid'");
$stats['pending_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Fetch recent fee transactions
$recent_fees = $conn->query("
    SELECT f.*, u.full_name as student_name, c.class_name, c.section
    FROM fees f
    JOIN users u ON f.student_id = u.id
    JOIN students s ON f.student_id = s.user_id
    JOIN classes c ON s.class_id = c.id
    ORDER BY f.id DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Fetch overdue fees
$overdue_fees = $conn->query("
    SELECT f.*, u.full_name as student_name, c.class_name, c.section
    FROM fees f
    JOIN users u ON f.student_id = u.id
    JOIN students s ON f.student_id = s.user_id
    JOIN classes c ON s.class_id = c.id
    WHERE f.status = 'unpaid' AND f.due_date < CURDATE()
    ORDER BY f.due_date ASC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Fee Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Overview</span>
                        </a>
                    </li>
                    <li>
                        <a href="collect.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cash-register w-5"></i>
                            <span>Collect Fee</span>
                        </a>
                    </li>
                    <li>
                        <a href="structure.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-list-alt w-5"></i>
                            <span>Fee Structure</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cog w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Fee Management Dashboard</h2>
                <div class="flex space-x-3">
                    <a href="collect.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-plus mr-2"></i> Collect Fee
                    </a>
                    <a href="structure.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-cog mr-2"></i> Manage Structure
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Fees -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Fees</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_fees']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Paid Fees -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Paid Fees</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['paid_fees']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Unpaid Fees -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Unpaid Fees</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['unpaid_fees']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Overdue Fees -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Overdue Fees</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['overdue_fees']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Amount -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Total Amount</p>
                        <p class="text-3xl font-bold text-blue-600">₹<?= number_format($stats['total_amount'], 2) ?></p>
                    </div>
                </div>

                <!-- Collected Amount -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Collected Amount</p>
                        <p class="text-3xl font-bold text-green-600">₹<?= number_format($stats['collected_amount'], 2) ?></p>
                    </div>
                </div>

                <!-- Pending Amount -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Pending Amount</p>
                        <p class="text-3xl font-bold text-red-600">₹<?= number_format($stats['pending_amount'], 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions and Overdue Fees -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Transactions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($recent_fees)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent transactions</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_fees as $fee): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['student_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($fee['class_name']) ?> <?= htmlspecialchars($fee['section']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($fee['fee_type']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($fee['amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($fee['status'] === 'paid'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Paid
                                                    </span>
                                                <?php elseif ($fee['status'] === 'unpaid' && $fee['due_date'] < date('Y-m-d')): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Overdue
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Unpaid
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Overdue Fees -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Overdue Fees</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($overdue_fees)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No overdue fees</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($overdue_fees as $fee): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['student_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($fee['class_name']) ?> <?= htmlspecialchars($fee['section']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($fee['fee_type']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                <?= date('d M Y', strtotime($fee['due_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($fee['amount'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add any JavaScript functionality here
        function refreshStats() {
            location.reload();
        }

        // Auto-refresh every 5 minutes
        setInterval(refreshStats, 300000);
    </script>
</body>

</html> 