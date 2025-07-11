<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class'] ?? '';
$teacher_filter = $_GET['teacher'] ?? '';
$sort = $_GET['sort'] ?? 'subject_name';
$order = $_GET['order'] ?? 'ASC';

// Base query
$query = "SELECT s.id, s.subject_name, s.subject_code, 
          c.class_name, c.section, 
          u.full_name as teacher_name
          FROM subjects s
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN users u ON s.teacher_id = u.id
          WHERE 1=1";

// Add filters
if (!empty($search)) {
    $query .= " AND (s.subject_name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";
}
if (!empty($class_filter)) {
    $query .= " AND c.id = $class_filter";
}
if (!empty($teacher_filter)) {
    $query .= " AND u.id = $teacher_filter";
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute query
$result = $conn->query($query);
$subjects = $result->fetch_all(MYSQLI_ASSOC);

// Get classes for filter dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);

// Get teachers for filter dropdown
$teachers = $conn->query("SELECT u.id, u.full_name FROM users u WHERE u.role = 'teacher' ORDER BY u.full_name")->fetch_all(MYSQLI_ASSOC);

// Export to Excel
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="subjects_' . date('Y-m-d') . '.xls"');

    echo "Subject Name\tSubject Code\tClass\tTeacher\n";
    foreach ($subjects as $subject) {
        echo $subject['subject_name'] . "\t";
        echo $subject['subject_code'] . "\t";
        echo ($subject['class_name'] ?? 'Not Assigned') . ($subject['section'] ? ' ' . $subject['section'] : '') . "\t";
        echo ($subject['teacher_name'] ?? 'Not Assigned') . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - School ERP</title>
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
                        <p class="text-blue-200">Subject Management</p>
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
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-list w-5"></i>
                            <span>Subject List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-plus w-5"></i>
                            <span>Add New Subject</span>
                        </a>
                    </li>
                    <li>
                        <a href="assign.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-user-tie w-5"></i>
                            <span>Assign Teachers</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Filter and Search Bar -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                    <h2 class="text-xl font-bold text-gray-800">Subject Records</h2>

                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full md:w-auto">
                        <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i> Add Subject
                        </a>
                        <a href="assign.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-tie mr-2"></i> Assign Teachers
                        </a>
                        <a href="?export=1&<?= http_build_query($_GET) ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-excel mr-2"></i> Export
                        </a>
                    </div>
                </div>

                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Subject Name or Code"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                        <select name="teacher" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Teachers</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= $teacher_filter == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <!-- Subject Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'subject_name', 'order' => $sort == 'subject_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Subject Name
                                        <?php if ($sort == 'subject_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'subject_code', 'order' => $sort == 'subject_code' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Subject Code
                                        <?php if ($sort == 'subject_code'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'class_name', 'order' => $sort == 'class_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Class
                                        <?php if ($sort == 'class_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'teacher_name', 'order' => $sort == 'teacher_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Teacher
                                        <?php if ($sort == 'teacher_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No subjects found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($subject['subject_name']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-blue-600 font-medium"><?= htmlspecialchars($subject['subject_code'] ?? 'Not Set') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($subject['class_name'] ?? 'Not Assigned') ?>
                                                <?= $subject['section'] ? ' ' . htmlspecialchars($subject['section']) : '' ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned') ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="view.php?id=<?= $subject['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?= $subject['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $subject['id'] ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this subject?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Statistics -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-book text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-600">Total Subjects</p>
                                <p class="text-lg font-semibold text-blue-900"><?= count($subjects) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-tie text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-600">Assigned Teachers</p>
                                <p class="text-lg font-semibold text-green-900">
                                    <?= count(array_filter($subjects, function ($s) {
                                        return !empty($s['teacher_name']);
                                    })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-600">Unassigned</p>
                                <p class="text-lg font-semibold text-yellow-900">
                                    <?= count(array_filter($subjects, function ($s) {
                                        return empty($s['teacher_name']);
                                    })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>