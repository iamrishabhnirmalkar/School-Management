<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get all classes with their teachers and student counts
$classes = [];
$query = "SELECT c.*, u.full_name as teacher_name, 
          (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count
          FROM classes c 
          LEFT JOIN users u ON c.class_teacher_id = u.id 
          ORDER BY c.class_name, c.section";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - School ERP</title>
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
                        <p class="text-blue-200">Class Management</p>
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
                            <span>Class List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-plus-circle w-5"></i>
                            <span>Add New Class</span>
                        </a>
                    </li>
                    <li>
                        <a href="import.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-file-import w-5"></i>
                            <span>Bulk Import</span>
                        </a>
                    </li>
                    <li>
                        <a href="promote.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-graduation-cap w-5"></i>
                            <span>Promote Students</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">Class Management</h2>
                <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Class
                </a>
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

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Teacher</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No classes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                            <?= htmlspecialchars($class['section'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                            <?= $class['teacher_name'] ? htmlspecialchars($class['teacher_name']) : '<span class="text-red-500">Not Assigned</span>' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                            <?= $class['student_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="view.php?id=<?= $class['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="edit.php?id=<?= $class['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <a href="delete.php?id=<?= $class['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this class?');">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </a>
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
</body>

</html>