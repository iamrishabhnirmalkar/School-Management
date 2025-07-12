<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get parameters
$classId = $_GET['class_id'] ?? null;
$feeType = $_GET['fee_type'] ?? null;

if (!$classId || !$feeType) {
    $_SESSION['error'] = "Class ID and Fee Type are required";
    header("Location: structure.php");
    exit;
}

// Fetch class details
$class = $conn->query("SELECT * FROM classes WHERE id = $classId")->fetch_assoc();
if (!$class) {
    $_SESSION['error'] = "Class not found";
    header("Location: structure.php");
    exit;
}

// Fetch fee details for this class and fee type
$fees = $conn->query("
    SELECT f.*, u.full_name as student_name, s.roll_number,
           DATEDIFF(f.due_date, CURDATE()) as days_remaining
    FROM fees f
    JOIN users u ON f.student_id = u.id
    JOIN students s ON f.student_id = s.user_id
    WHERE f.class_id = $classId AND f.fee_type = '" . $conn->real_escape_string($feeType) . "'
    ORDER BY s.roll_number, u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$totalStudents = count($fees);
$paidCount = 0;
$unpaidCount = 0;
$overdueCount = 0;
$totalAmount = 0;
$collectedAmount = 0;
$pendingAmount = 0;

foreach ($fees as $fee) {
    $totalAmount += $fee['amount'];
    if ($fee['status'] === 'paid') {
        $paidCount++;
        $collectedAmount += $fee['amount'];
    } else {
        $unpaidCount++;
        $pendingAmount += $fee['amount'];
        if ($fee['days_remaining'] < 0) {
            $overdueCount++;
        }
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['action'];
    $selectedFees = $_POST['selected_fees'] ?? [];

    if (empty($selectedFees)) {
        $_SESSION['error'] = "Please select at least one fee";
    } else {
        $conn->begin_transaction();
        try {
            foreach ($selectedFees as $feeId) {
                if ($action === 'mark_paid') {
                    $stmt = $conn->prepare("UPDATE fees SET status = 'paid', paid_date = CURDATE() WHERE id = ?");
                    $stmt->bind_param("i", $feeId);
                    $stmt->execute();
                } elseif ($action === 'mark_unpaid') {
                    $stmt = $conn->prepare("UPDATE fees SET status = 'unpaid', paid_date = NULL WHERE id = ?");
                    $stmt->bind_param("i", $feeId);
                    $stmt->execute();
                }
            }
            $conn->commit();
            $_SESSION['success'] = "Bulk action completed successfully";
            header("Location: details.php?class_id=$classId&fee_type=" . urlencode($feeType));
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error performing bulk action: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Details - School ERP</title>
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
                        <p class="text-blue-200">Fee Details</p>
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
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
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
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <?= htmlspecialchars($feeType) ?> - <?= htmlspecialchars($class['class_name']) ?>
                        <?= $class['section'] ? '(' . htmlspecialchars($class['section']) . ')' : '' ?>
                    </h2>
                    <p class="text-gray-600">Fee Details and Payment Status</p>
                </div>
                <div class="flex space-x-3">
                    <a href="structure.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Structure
                    </a>
                    <button onclick="printDetails()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $totalStudents ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Paid</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $paidCount ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Unpaid</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $unpaidCount ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Overdue</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $overdueCount ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Total Amount</p>
                        <p class="text-3xl font-bold text-blue-600">₹<?= number_format($totalAmount, 2) ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Collected Amount</p>
                        <p class="text-3xl font-bold text-green-600">₹<?= number_format($collectedAmount, 2) ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-600">Pending Amount</p>
                        <p class="text-3xl font-bold text-red-600">₹<?= number_format($pendingAmount, 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Fee Details Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Student Fee Details</h3>
                    <div class="flex space-x-2">
                        <button onclick="selectAll()" class="text-blue-600 hover:text-blue-900 text-sm">
                            <i class="fas fa-check-square mr-1"></i> Select All
                        </button>
                        <button onclick="deselectAll()" class="text-gray-600 hover:text-gray-900 text-sm">
                            <i class="fas fa-square mr-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <form method="POST" id="bulkForm">
                    <input type="hidden" name="bulk_action" value="1">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <select name="action" class="px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select Action</option>
                                <option value="mark_paid">Mark as Paid</option>
                                <option value="mark_unpaid">Mark as Unpaid</option>
                            </select>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                Apply to Selected
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($fees)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No fee records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($fees as $fee): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="selected_fees[]" value="<?= $fee['id'] ?>" 
                                                       class="fee-checkbox rounded border-gray-300">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['student_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($fee['roll_number']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($fee['amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= date('d M Y', strtotime($fee['due_date'])) ?></div>
                                                <?php if ($fee['days_remaining'] < 0): ?>
                                                    <div class="text-sm text-red-600"><?= abs($fee['days_remaining']) ?> days overdue</div>
                                                <?php elseif ($fee['days_remaining'] <= 7): ?>
                                                    <div class="text-sm text-orange-600"><?= $fee['days_remaining'] ?> days left</div>
                                                <?php else: ?>
                                                    <div class="text-sm text-gray-500"><?= $fee['days_remaining'] ?> days left</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($fee['status'] === 'paid'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Paid
                                                    </span>
                                                <?php elseif ($fee['days_remaining'] < 0): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Overdue
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Unpaid
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="collect.php?student_id=<?= $fee['student_id'] ?>&fee_id=<?= $fee['id'] ?>"
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-cash-register mr-1"></i> Collect
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function selectAll() {
            document.querySelectorAll('.fee-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            document.getElementById('selectAll').checked = true;
        }

        function deselectAll() {
            document.querySelectorAll('.fee-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
        }

        // Handle select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.fee-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Handle individual checkboxes
        document.querySelectorAll('.fee-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.fee-checkbox:checked').length === document.querySelectorAll('.fee-checkbox').length;
                document.getElementById('selectAll').checked = allChecked;
            });
        });

        function printDetails() {
            window.print();
        }
    </script>
</body>

</html> 