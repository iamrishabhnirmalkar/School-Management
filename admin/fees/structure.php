<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle fee structure creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_structure'])) {
    $feeType = trim($_POST['fee_type']);
    $amount = $_POST['amount'];
    $dueDate = $_POST['due_date'];
    $description = trim($_POST['description']);
    $classIds = $_POST['class_ids'] ?? [];

    // Validate
    if (empty($feeType) || empty($amount) || empty($dueDate) || empty($classIds)) {
        $_SESSION['error'] = "Fee type, amount, due date and classes are required";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            foreach ($classIds as $classId) {
                // Get all students in this class
                $students = $conn->query("SELECT user_id FROM students WHERE class_id = $classId")->fetch_all(MYSQLI_ASSOC);

                foreach ($students as $student) {
                    $stmt = $conn->prepare("INSERT INTO fees (student_id, class_id, fee_type, amount, due_date, remarks, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, 'unpaid')");
                    $stmt->bind_param("iisdss", $student['user_id'], $classId, $feeType, $amount, $dueDate, $description);
                    $stmt->execute();
                }
            }

            $conn->commit();
            $_SESSION['success'] = "Fee structure added successfully";
            header("Location: structure.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error adding fee structure: " . $e->getMessage();
        }
    }
}

// Handle fee structure deletion
if (isset($_GET['delete_structure'])) {
    $feeType = urldecode($_GET['delete_structure']);
    $classId = $_GET['class_id'] ?? null;

    if ($classId) {
        $conn->query("DELETE FROM fees WHERE fee_type = '$feeType' AND class_id = $classId");
        $_SESSION['success'] = "Fee structure deleted successfully";
    } else {
        $conn->query("DELETE FROM fees WHERE fee_type = '$feeType'");
        $_SESSION['success'] = "Fee structure deleted for all classes";
    }
    header("Location: structure.php");
    exit;
}

// Fetch fee structures grouped by class
$feeStructures = $conn->query("
    SELECT c.id as class_id, c.class_name, c.section,
           f.fee_type, MAX(f.amount) as amount, MAX(f.due_date) as due_date, MAX(f.remarks) as remarks,
           COUNT(CASE WHEN f.status = 'paid' THEN 1 END) as paid_count,
           COUNT(CASE WHEN f.status = 'unpaid' THEN 1 END) as unpaid_count,
           COUNT(CASE WHEN f.status = 'unpaid' AND f.due_date < CURDATE() THEN 1 END) as overdue_count
    FROM classes c
    LEFT JOIN fees f ON c.id = f.class_id
    WHERE f.fee_type IS NOT NULL
    GROUP BY c.id, f.fee_type
    ORDER BY c.class_name, c.section, due_date
")->fetch_all(MYSQLI_ASSOC);

// Group by class
$groupedStructures = [];
foreach ($feeStructures as $structure) {
    $classKey = $structure['class_id'];
    if (!isset($groupedStructures[$classKey])) {
        $groupedStructures[$classKey] = [
            'class_name' => $structure['class_name'],
            'section' => $structure['section'],
            'fees' => []
        ];
    }
    $groupedStructures[$classKey]['fees'][] = $structure;
}

// Fetch classes for form
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);

// Fetch existing fee types
$feeTypes = $conn->query("SELECT DISTINCT fee_type FROM fees ORDER BY fee_type")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Structure - School ERP</title>
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
                        <p class="text-blue-200">Fee Structure Management</p>
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
                        <a href="structure.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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
                <h2 class="text-2xl font-bold text-gray-800">Fee Structure Management</h2>
                <button onclick="document.getElementById('addStructureModal').classList.remove('hidden')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-plus mr-2"></i> Add Fee Structure
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

            <!-- Fee Structures by Class -->
            <?php if (empty($groupedStructures)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-list-alt text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Fee Structures Defined</h3>
                    <p class="text-gray-500 mb-4">Start by adding fee structures for your classes.</p>
                    <button onclick="document.getElementById('addStructureModal').classList.remove('hidden')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-plus mr-2"></i> Add First Fee Structure
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($groupedStructures as $classId => $classData): ?>
                    <div class="bg-white rounded-lg shadow-md mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                <?= htmlspecialchars($classData['class_name']) ?> 
                                <?= $classData['section'] ? '(' . htmlspecialchars($classData['section']) . ')' : '' ?>
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($classData['fees'] as $fee): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($fee['fee_type']) ?></div>
                                                <?php if (!empty($fee['remarks'])): ?>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($fee['remarks']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($fee['amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d M Y', strtotime($fee['due_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex space-x-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Paid: <?= $fee['paid_count'] ?>
                                                    </span>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Unpaid: <?= $fee['unpaid_count'] ?>
                                                    </span>
                                                    <?php if ($fee['overdue_count'] > 0): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            Overdue: <?= $fee['overdue_count'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="details.php?class_id=<?= $classId ?>&fee_type=<?= urlencode($fee['fee_type']) ?>"
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-eye mr-1"></i> View
                                                </a>
                                                <a href="structure.php?delete_structure=<?= urlencode($fee['fee_type']) ?>&class_id=<?= $classId ?>"
                                                   onclick="return confirm('Are you sure you want to delete this fee structure for all students in this class?')"
                                                   class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash mr-1"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Fee Structure Modal -->
    <div id="addStructureModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Fee Structure</h3>
                <form method="POST" id="addStructureForm">
                    <input type="hidden" name="add_structure" value="1">
                    
                    <div class="mb-4">
                        <label for="fee_type" class="block text-sm font-medium text-gray-700 mb-1">Fee Type *</label>
                        <select id="fee_type" name="fee_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Fee Type --</option>
                            <option value="Tuition Fee">Tuition Fee</option>
                            <option value="Admission Fee">Admission Fee</option>
                            <option value="Exam Fee">Exam Fee</option>
                            <option value="Transport Fee">Transport Fee</option>
                            <option value="Library Fee">Library Fee</option>
                            <option value="Laboratory Fee">Laboratory Fee</option>
                            <option value="Sports Fee">Sports Fee</option>
                            <option value="Computer Fee">Computer Fee</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (₹) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Apply to Classes *</label>
                        <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-md p-2">
                            <?php foreach ($classes as $class): ?>
                                <label class="flex items-center mb-2">
                                    <input type="checkbox" name="class_ids[]" value="<?= $class['id'] ?>"
                                           class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">
                                        <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddStructureModal()"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-plus mr-2"></i> Add Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeAddStructureModal() {
            document.getElementById('addStructureModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('addStructureModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddStructureModal();
            }
        });

        // Set default due date to next month
        document.getElementById('due_date').value = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    </script>
</body>

</html> 