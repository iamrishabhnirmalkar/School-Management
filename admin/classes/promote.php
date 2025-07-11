<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Fetch all classes for the dropdowns
$classes = [];
$result = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromClassId = $_POST['from_class_id'];
    $toClassId = $_POST['to_class_id'];

    if (empty($fromClassId) || empty($toClassId)) {
        $_SESSION['error'] = "Both source and destination classes are required!";
    } elseif ($fromClassId == $toClassId) {
        $_SESSION['error'] = "Cannot promote students to the same class!";
    } else {
        // Get class names for messages
        $fromClass = $conn->query("SELECT class_name, section FROM classes WHERE id = $fromClassId")->fetch_assoc();
        $toClass = $conn->query("SELECT class_name, section FROM classes WHERE id = $toClassId")->fetch_assoc();

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update students' class
            $stmt = $conn->prepare("UPDATE students SET class_id = ? WHERE class_id = ?");
            $stmt->bind_param("ii", $toClassId, $fromClassId);
            $stmt->execute();

            $affected_rows = $stmt->affected_rows;

            if ($affected_rows > 0) {
                // Log the promotion activity
                $adminId = $_SESSION['user']['id'];
                $action = "Promoted $affected_rows students from " . $fromClass['class_name'] . " " . $fromClass['section'] .
                    " to " . $toClass['class_name'] . " " . $toClass['section'];

                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
                $log_stmt->bind_param("is", $adminId, $action);
                $log_stmt->execute();

                $conn->commit();
                $_SESSION['success'] = "Successfully promoted $affected_rows students from " .
                    htmlspecialchars($fromClass['class_name']) . " " . htmlspecialchars($fromClass['section']) .
                    " to " . htmlspecialchars($toClass['class_name']) . " " . htmlspecialchars($toClass['section']);
            } else {
                $conn->rollback();
                $_SESSION['error'] = "No students found in the selected class to promote!";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error promoting students: " . $e->getMessage();
        }
    }

    header("Location: promote.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promote Students - School ERP</title>
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
                        <p class="text-blue-200">Student Promotion</p>
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
                        <a href="../../admin/dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-list w-5"></i>
                            <span>Class List</span>
                        </a>
                    </li>
                    <li>
                        <a href="promote.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-graduation-cap w-5"></i>
                            <span>Promote Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="import.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-file-import w-5"></i>
                            <span>Bulk Import</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Student Promotion</h2>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Academic Year:</span>
                        <select class="border border-gray-300 rounded px-2 py-1 text-sm">
                            <option>2023-2024</option>
                            <option>2024-2025</option>
                        </select>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Promotion Form -->
                    <div>
                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="from_class_id" class="block text-sm font-medium text-gray-700 mb-2">From Class *</label>
                                <select id="from_class_id" name="from_class_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Current Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>">
                                            <?= htmlspecialchars($class['class_name']) ?> (<?= htmlspecialchars($class['section']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="to_class_id" class="block text-sm font-medium text-gray-700 mb-2">To Class *</label>
                                <select id="to_class_id" name="to_class_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Next Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>">
                                            <?= htmlspecialchars($class['class_name']) ?> (<?= htmlspecialchars($class['section']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Important:</strong> This action will move ALL students from the selected class to the new class.
                                            This is typically done at the end of an academic year.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                    <i class="fas fa-graduation-cap mr-2"></i> Promote Students
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Promotion Guidelines -->
                    <div>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Promotion Guidelines</h3>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-medium text-gray-800">When to Promote Students:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li>At the end of academic year</li>
                                        <li>When reorganizing class structures</li>
                                        <li>When merging or splitting classes</li>
                                    </ul>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-800">What Happens:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li>All students from source class will be moved to destination class</li>
                                        <li>Student records will be updated with new class information</li>
                                        <li>Class teacher assignments remain unchanged</li>
                                    </ul>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-800">Best Practices:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li>Backup your database before mass promotions</li>
                                        <li>Inform teachers and students about class changes</li>
                                        <li>Review class capacities before promotion</li>
                                        <li>Use the academic year selector to track promotions</li>
                                    </ul>
                                </div>

                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                <strong>Note:</strong> For individual student transfers, use the student edit feature instead.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Promotions -->
                <div class="mt-12">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Promotions</h3>

                    <?php
                    // Fetch recent promotion activities
                    $logs = [];
                    $result = $conn->query("SELECT action, created_at FROM activity_log 
                                          WHERE action LIKE 'Promoted%' 
                                          ORDER BY created_at DESC LIMIT 5");
                    while ($row = $result->fetch_assoc()) {
                        $logs[] = $row;
                    }
                    ?>

                    <?php if (empty($logs)): ?>
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500">No promotion history found.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple confirmation for promotion
        document.querySelector('form').addEventListener('submit', function(e) {
            const fromClass = document.getElementById('from_class_id').options[document.getElementById('from_class_id').selectedIndex].text;
            const toClass = document.getElementById('to_class_id').options[document.getElementById('to_class_id').selectedIndex].text;

            if (!confirm(`Are you sure you want to promote ALL students from ${fromClass} to ${toClass}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>