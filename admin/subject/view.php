<?php
session_start();
require_once '../../config.php';

// Authentication check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get subject ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$subject_id = $_GET['id'];

// Fetch subject details with class and teacher info
$query = "SELECT s.*, c.class_name, c.section, 
          u.full_name as teacher_name, u.id as teacher_id
          FROM subjects s
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN users u ON s.teacher_id = u.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    $_SESSION['error'] = "Subject not found!";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Details - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header (Same as index.php) -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Subject Details</p>
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
        <!-- Sidebar (Same as index.php) -->
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
                <!-- Header with action buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Subject Details</h2>
                    <div class="flex space-x-2">
                        <a href="edit.php?id=<?= $subject['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </a>
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Subject Information Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Basic Information Card -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Basic Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Subject Name</p>
                                <p class="text-gray-800 font-semibold"><?= htmlspecialchars($subject['subject_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Subject Code</p>
                                <p class="text-gray-800 font-semibold"><?= htmlspecialchars($subject['subject_code'] ?? 'Not set') ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Class</p>
                                <p class="text-gray-800 font-semibold">
                                    <?= htmlspecialchars($subject['class_name'] ?? 'Not assigned') ?>
                                    <?= $subject['section'] ? ' - ' . htmlspecialchars($subject['section']) : '' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Information Card -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i>
                            Teacher Information
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Assigned Teacher</p>
                                <p class="text-gray-800 font-semibold">
                                    <?= $subject['teacher_name'] ? htmlspecialchars($subject['teacher_name']) : '<span class="text-red-500">Not assigned</span>' ?>
                                </p>
                            </div>
                            <div>
                                <a href="assign.php?subject_id=<?= $subject['id'] ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-user-edit mr-1"></i> Assign/Change Teacher
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Information Section -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-link text-green-500 mr-2"></i>
                        Related Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="../timetable/?subject=<?= $subject['id'] ?>" class="bg-white p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-3 rounded-full mr-3">
                                    <i class="fas fa-calendar-alt text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Timetable</p>
                                    <p class="text-sm text-gray-500">View schedule</p>
                                </div>
                            </div>
                        </a>
                        <a href="../exams/?subject=<?= $subject['id'] ?>" class="bg-white p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-3 rounded-full mr-3">
                                    <i class="fas fa-clipboard-list text-green-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Exams</p>
                                    <p class="text-sm text-gray-500">View exam results</p>
                                </div>
                            </div>
                        </a>
                        <a href="../study-materials/?subject=<?= $subject['id'] ?>" class="bg-white p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all">
                            <div class="flex items-center">
                                <div class="bg-purple-100 p-3 rounded-full mr-3">
                                    <i class="fas fa-book-open text-purple-500"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Study Materials</p>
                                    <p class="text-sm text-gray-500">View resources</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>