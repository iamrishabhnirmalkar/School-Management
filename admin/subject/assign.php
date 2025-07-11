<?php
session_start();
require_once '../../config.php';

// Authentication check
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

// Handle teacher assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'] ?: NULL;

    $stmt = $conn->prepare("UPDATE subjects SET teacher_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $teacher_id, $subject_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Teacher assignment updated successfully!";
        header("Location: assign.php?" . http_build_query($_GET));
        exit;
    } else {
        $error = "Error updating assignment: " . $conn->error;
    }
}

// Base query for subjects
$query = "SELECT s.id, s.subject_name, s.subject_code, 
          c.class_name, c.section, 
          u.id as teacher_id, u.full_name as teacher_name
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

$subjects = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get classes and teachers for filters
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teachers - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header (Same as index.php) -->
    <header class="bg-blue-700 text-white shadow-md">
        <!-- ... same header as index.php ... -->
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar (Same as index.php) -->
        <aside class="w-64 flex-shrink-0">
            <!-- ... same sidebar as index.php ... -->
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Header with actions -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                    <h2 class="text-2xl font-bold text-gray-800">Teacher Assignments</h2>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full md:w-auto">
                        <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i> Subject List
                        </a>
                    </div>
                </div>

                <!-- Filter and Search Bar -->
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Subject name or code"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Teacher</label>
                        <select name="teacher" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Teachers</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= $teacher_filter == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full flex items-center justify-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <!-- Status Messages -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Subjects Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'subject_name', 'order' => $sort == 'subject_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Subject
                                        <?php if ($sort == 'subject_name'): ?>
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
                                    Current Teacher
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Assign Teacher
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No subjects found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($subject['subject_name']) ?></div>
                                            <div class="text-sm text-blue-600"><?= htmlspecialchars($subject['subject_code'] ?? 'No code') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-gray-900"><?= htmlspecialchars($subject['class_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($subject['section']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($subject['teacher_name']): ?>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($subject['teacher_name']) ?></div>
                                                <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($subject['teacher_id']) ?></div>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <form method="POST" class="flex items-center justify-end space-x-2">
                                                <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                                <select name="teacher_id" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Unassign</option>
                                                    <?php foreach ($teachers as $teacher): ?>
                                                        <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $subject['teacher_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($teacher['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Statistics -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-book text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Subjects</p>
                                <p class="text-xl font-semibold text-gray-900"><?= count($subjects) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Assigned</p>
                                <p class="text-xl font-semibold text-gray-900">
                                    <?= count(array_filter($subjects, function ($s) {
                                        return !empty($s['teacher_name']);
                                    })) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 p-3 rounded-full">
                                <i class="fas fa-user-times text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Unassigned</p>
                                <p class="text-xl font-semibold text-gray-900">
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