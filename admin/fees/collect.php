<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle fee collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_fee'])) {
    $studentId = $_POST['student_id'];
    $feeId = $_POST['fee_id'];
    $amountPaid = $_POST['amount_paid'];
    $paymentMethod = $_POST['payment_method'];
    $remarks = trim($_POST['remarks']);
    $paymentDate = $_POST['payment_date'];

    // Validate
    if (empty($studentId) || empty($feeId) || empty($amountPaid) || empty($paymentMethod)) {
        $_SESSION['error'] = "All required fields must be filled";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update fee status
            $stmt = $conn->prepare("UPDATE fees SET status = 'paid', paid_date = ? WHERE id = ?");
            $stmt->bind_param("si", $paymentDate, $feeId);
            $stmt->execute();

            // Log the payment (only if fee_payments table exists)
            try {
                $stmt = $conn->prepare("INSERT INTO fee_payments (fee_id, student_id, amount_paid, payment_method, payment_date, collected_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $adminId = $_SESSION['user']['id'];
                $stmt->bind_param("iidssss", $feeId, $studentId, $amountPaid, $paymentMethod, $paymentDate, $adminId, $remarks);
                $stmt->execute();
            } catch (Exception $e) {
                // If fee_payments table doesn't exist, just continue without logging
                // This allows the system to work even without the payment history table
            }

            $conn->commit();
            $_SESSION['success'] = "Fee collected successfully";
            header("Location: collect.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error collecting fee: " . $e->getMessage();
        }
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$classFilter = $_GET['class'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$whereConditions = ["f.status != 'paid'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(u.full_name LIKE ? OR s.roll_number LIKE ? OR f.fee_type LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($classFilter)) {
    $whereConditions[] = "s.class_id = ?";
    $params[] = $classFilter;
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'overdue') {
        $whereConditions[] = "f.due_date < CURDATE()";
    } else {
        $whereConditions[] = "f.status = ?";
        $params[] = $statusFilter;
    }
}

$whereClause = implode(" AND ", $whereConditions);

// Fetch unpaid fees
$query = "
    SELECT f.*, u.full_name as student_name, s.roll_number, c.class_name, c.section,
           DATEDIFF(f.due_date, CURDATE()) as days_remaining
    FROM fees f
    JOIN users u ON f.student_id = u.id
    JOIN students s ON f.student_id = s.user_id
    JOIN classes c ON s.class_id = c.id
    WHERE $whereClause
    ORDER BY f.due_date ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$unpaidFees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch classes for filter
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Fees - School ERP</title>
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
                        <p class="text-blue-200">Collect Fees</p>
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
                        <a href="collect.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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
                <h2 class="text-2xl font-bold text-gray-800">Collect Fees</h2>
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

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Student name, roll number, or fee type"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="class" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select id="class" name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $classFilter == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                            <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <a href="collect.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Unpaid Fees Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Unpaid Fees (<?= count($unpaidFees) ?>)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($unpaidFees)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No unpaid fees found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($unpaidFees as $fee): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['student_name']) ?></div>
                                            <div class="text-sm text-gray-500">Roll: <?= htmlspecialchars($fee['roll_number']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($fee['class_name']) ?> <?= htmlspecialchars($fee['section']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($fee['fee_type']) ?>
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
                                            <?php if ($fee['days_remaining'] < 0): ?>
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
                                            <button onclick="openCollectModal(<?= $fee['id'] ?>, '<?= htmlspecialchars($fee['student_name']) ?>', '<?= htmlspecialchars($fee['fee_type']) ?>', <?= $fee['amount'] ?>)"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">
                                                <i class="fas fa-cash-register mr-1"></i> Collect
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Collect Fee Modal -->
    <div id="collectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Collect Fee</h3>
                <form method="POST" id="collectForm">
                    <input type="hidden" name="collect_fee" value="1">
                    <input type="hidden" name="student_id" id="modalStudentId">
                    <input type="hidden" name="fee_id" id="modalFeeId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                        <input type="text" id="modalStudentName" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fee Type</label>
                        <input type="text" id="modalFeeType" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>
                    
                    <div class="mb-4">
                        <label for="amount_paid" class="block text-sm font-medium text-gray-700 mb-1">Amount to Pay *</label>
                        <input type="number" id="amount_paid" name="amount_paid" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online Transfer">Online Transfer</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
                        <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea id="remarks" name="remarks" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCollectModal()"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-check mr-2"></i> Collect Fee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCollectModal(feeId, studentName, feeType, amount) {
            document.getElementById('modalFeeId').value = feeId;
            document.getElementById('modalStudentName').value = studentName;
            document.getElementById('modalFeeType').value = feeType;
            document.getElementById('amount_paid').value = amount;
            document.getElementById('collectModal').classList.remove('hidden');
        }

        function closeCollectModal() {
            document.getElementById('collectModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('collectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCollectModal();
            }
        });
    </script>
</body>

</html> 