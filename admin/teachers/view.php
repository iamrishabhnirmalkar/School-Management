<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get teacher ID from URL
$teacher_id = $_GET['id'] ?? 0;

// Fetch teacher data with class information
$teacher = $conn->query("
    SELECT u.*, t.*, c.id as class_id, c.class_name, c.section 
    FROM users u 
    JOIN teachers t ON u.id = t.user_id 
    LEFT JOIN classes c ON c.class_teacher_id = u.id
    WHERE u.id = $teacher_id AND u.role = 'teacher'
")->fetch_assoc();

if (!$teacher) {
    $_SESSION['error'] = "Teacher not found";
    header("Location: index.php");
    exit;
}

// Get subjects taught by this teacher
$subjects = $conn->query("
    SELECT s.subject_name, c.class_name, c.section 
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    WHERE s.teacher_id = $teacher_id
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Teacher - School ERP</title>
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
                        <p class="text-blue-200">View Teacher</p>
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
                            <span>Teacher List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-user-plus w-5"></i>
                            <span>Add New Teacher</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Teacher Details: <?= htmlspecialchars($teacher['full_name']) ?></h2>
                    <div class="space-x-2">
                        <a href="edit.php?id=<?= $teacher_id ?>" class="px-3 py-1 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <a href="delete.php?id=<?= $teacher_id ?>" class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this teacher?')">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Teacher Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Personal Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Login ID</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['login_id']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Full Name</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['full_name']) ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Email</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['email']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Phone</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['phone']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Professional Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Qualification</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['qualification_type']) ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Specialization</p>
                                <p class="text-gray-900"><?= htmlspecialchars($teacher['specialization']) ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Joining Date</p>
                                <p class="text-gray-900"><?= date('d M, Y', strtotime($teacher['joining_date'])) ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Class Teacher</p>
                                <p class="text-gray-900">
                                    <?php if ($teacher['class_id']): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <?= htmlspecialchars($teacher['class_name']) ?> - <?= htmlspecialchars($teacher['section']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">No</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subjects Taught -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Subjects Taught</h3>
                    <?php if (empty($subjects)): ?>
                        <p class="text-gray-500">No subjects assigned yet</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($subject['class_name']) ?> - <?= htmlspecialchars($subject['section']) ?></td>
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
</body>

</html>