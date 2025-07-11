<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get class ID from URL
$classId = $_GET['id'] ?? null;
if (!$classId) {
    $_SESSION['error'] = "Class ID not specified";
    header("Location: index.php");
    exit;
}

// Fetch class details
$class = $conn->query("SELECT * FROM classes WHERE id = $classId")->fetch_assoc();
if (!$class) {
    $_SESSION['error'] = "Class not found";
    header("Location: index.php");
    exit;
}

// Fetch fee structure for this class
$fees = $conn->query("SELECT f.*, 
                     (SELECT COUNT(*) FROM fees WHERE fee_type = f.fee_type AND class_id = $classId AND status = 'unpaid') as unpaid_count,
                     (SELECT COUNT(*) FROM fees WHERE fee_type = f.fee_type AND class_id = $classId AND status = 'paid') as paid_count
                     FROM fees f
                     WHERE f.class_id = $classId
                     GROUP BY f.fee_type
                     ORDER BY f.due_date")->fetch_all(MYSQLI_ASSOC);

// Handle form submission for adding fees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $feeType = trim($_POST['fee_type']);
    $amount = $_POST['amount'];
    $dueDate = $_POST['due_date'];
    $description = trim($_POST['description']);

    // Validate
    if (empty($feeType) || empty($amount) || empty($dueDate)) {
        $_SESSION['error'] = "Fee type, amount and due date are required";
    } else {
        // Get all students in this class
        $students = $conn->query("SELECT user_id FROM students WHERE class_id = $classId")->fetch_all(MYSQLI_ASSOC);

        // Start transaction
        $conn->begin_transaction();

        try {
            foreach ($students as $student) {
                $stmt = $conn->prepare("INSERT INTO fees (student_id, class_id, fee_type, amount, due_date, description, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, 'unpaid')");
                $stmt->bind_param("iisdss", $student['user_id'], $classId, $feeType, $amount, $dueDate, $description);
                $stmt->execute();
            }

            $conn->commit();
            $_SESSION['success'] = "Fee added for all students in class";
            header("Location: fees.php?id=$classId");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error adding fee: " . $e->getMessage();
        }
    }
}

// Handle fee deletion
if (isset($_GET['delete_fee'])) {
    $feeType = urldecode($_GET['delete_fee']);
    $conn->query("DELETE FROM fees WHERE fee_type = '$feeType' AND class_id = $classId");
    $_SESSION['success'] = "Fee structure deleted for all students";
    header("Location: fees.php?id=$classId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Structure - <?= htmlspecialchars($class['class_name']) ?> - School ERP</title>
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
                        <p class="text-blue-200">Class Fee Structure</p>
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
                        <a href="view.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Class</span>
                        </a>
                    </li>
                    <li>
                        <a href="timetable.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-clock w-5"></i>
                            <span>Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="fees.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Structure</span>
                        </a>
                    </li>
                    <li>
                        <a href="subjects.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-book w-5"></i>
                            <span>Subjects</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">
                    Fee Structure for <?= htmlspecialchars($class['class_name']) ?>
                    <?= $class['section'] ? '(' . htmlspecialchars($class['section']) . ')' : '' ?>
                </h2>
                <button onclick="document.getElementById('addFeeModal').classList.remove('hidden')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-plus mr-2"></i> Add Fee
                </button>
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

            <!-- Fee Structure Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($fees)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No fee structure defined for this class</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['fee_type']) ?></div>
                                        <?php if (!empty($fee['description'])): ?>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($fee['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ₹<?= number_format($fee['amount'], 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d M Y', strtotime($fee['due_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Paid: <?= $fee['paid_count'] ?>
                                            </span>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Unpaid: <?= $fee['unpaid_count'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="fee_details.php?class_id=<?= $classId ?>&fee_type=<?= urlencode($fee['fee_type']) ?>"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <a href="fees.php?id=<?= $classId ?>&delete_fee=<?= urlencode($fee['fee_type']) ?>"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this fee structure for all students?')">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add Fee Modal -->
    <div id="addFeeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Fee</h3>
                <button onclick="document.getElementById('addFeeModal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST">
                <input type="hidden" name="add_fee" value="1">

                <div class="mb-4">
                    <label for="fee_type" class="block text-sm font-medium text-gray-700 mb-1">Fee Type *</label>
                    <select id="fee_type" name="fee_type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Fee Type --</option>
                        <option value="Tuition Fee">Tuition Fee</option>
                        <option value="Admission Fee">Admission Fee</option>
                        <option value="Exam Fee">Exam Fee</option>
                        <option value="Transport Fee">Transport Fee</option>
                        <option value="Library Fee">Library Fee</option>
                        <option value="Sports Fee">Sports Fee</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                This fee will be applied to <strong>all current students</strong> in this class.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('addFeeModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i> Add Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>