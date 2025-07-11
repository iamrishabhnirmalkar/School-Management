<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Fetch all students with their information
$students = $conn->query("
    SELECT s.*, u.full_name, u.email, u.phone, c.class_name, c.section 
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, c.section, u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Fetch all teachers with their information
$teachers = $conn->query("
    SELECT t.*, u.full_name, u.email, u.phone 
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identity Cards - School ERP</title>
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
                        <p class="text-blue-200">Identity Card Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../../admin/dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded hover:bg-blue-50">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
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
    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-6 text-gray-800">Identity Card Management</h2>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <ul class="flex flex-wrap -mb-px">
                    <li class="mr-2">
                        <button onclick="showTab('student')" id="student-tab" class="inline-block p-4 border-b-2 border-blue-600 rounded-t-lg text-blue-600">
                            <i class="fas fa-user-graduate mr-2"></i> Student ID Cards
                        </button>
                    </li>
                    <li class="mr-2">
                        <button onclick="showTab('teacher')" id="teacher-tab" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                            <i class="fas fa-chalkboard-teacher mr-2"></i> Teacher ID Cards
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Student ID Cards Section -->
            <div id="student-section">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-user-graduate mr-2 text-blue-600"></i> Student ID Cards
                    </h3>
                    <div>
                        <a href="student/generate.php" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded mr-2">
                            <i class="fas fa-id-card mr-2"></i> Generate All
                        </a>
                        <input type="text" id="student-search" placeholder="Search students..." class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200" id="student-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b">Photo</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Class</th>
                                <th class="py-2 px-4 border-b">Roll No</th>
                                <th class="py-2 px-4 border-b">Parent Phone</th>
                                <th class="py-2 px-4 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php
                                // Check if student has bus service
                                $has_bus = $conn->query("SELECT COUNT(*) FROM bus_allocations WHERE student_id = {$student['user_id']}")->fetch_row()[0] > 0;
                                ?>
                                <tr class="hover:bg-gray-50 student-row" data-name="<?= strtolower(htmlspecialchars($student['full_name'] ?? '')) ?>">
                                    <td class="py-2 px-4 border-b text-center">
                                        <img src="<?= !empty($student['photo']) ? '../../' . $student['photo'] : '../../assets/img/default-student.jpg' ?>" 
                                             alt="<?= htmlspecialchars($student['full_name'] ?? '') ?>" 
                                             class="w-10 h-10 rounded-full mx-auto object-cover">
                                    </td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($student['full_name'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($student['class_name'] ?? '') . ' ' . htmlspecialchars($student['section'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($student['roll_number'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($student['parent_phone'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <div class="flex space-x-2">
                                            <a href="student/student_template.php?id=<?= $student['user_id'] ?>" 
                                               target="_blank"
                                               class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded"
                                               title="View ID Card">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="student/student_template.php?id=<?= $student['user_id'] ?>&download=1" 
                                               class="bg-green-600 hover:bg-green-700 text-white p-2 rounded"
                                               title="Download ID Card">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php if ($has_bus): ?>
                                                <a href="student/bus_id_template.php?id=<?= $student['user_id'] ?>" 
                                                   target="_blank"
                                                   class="bg-yellow-600 hover:bg-yellow-700 text-white p-2 rounded"
                                                   title="View Bus ID Card">
                                                    <i class="fas fa-bus"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Teacher ID Cards Section -->
            <div id="teacher-section" class="hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-chalkboard-teacher mr-2 text-green-600"></i> Teacher ID Cards
                    </h3>
                    <div>
                        <a href="teacher/generate.php" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded mr-2">
                            <i class="fas fa-id-card mr-2"></i> Generate All
                        </a>
                        <input type="text" id="teacher-search" placeholder="Search teachers..." class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200" id="teacher-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b">Photo</th>
                                <th class="py-2 px-4 border-b">Name</th>
                                <th class="py-2 px-4 border-b">Qualification</th>
                                <th class="py-2 px-4 border-b">Phone</th>
                                <th class="py-2 px-4 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr class="hover:bg-gray-50 teacher-row" data-name="<?= strtolower(htmlspecialchars($teacher['full_name'])) ?>">
                                    <td class="py-2 px-4 border-b text-center">
                                        <img src="<?= !empty($teacher['photo']) ? '../../' . $teacher['photo'] : '../../assets/img/default-teacher.jpg' ?>" 
                                             alt="<?= htmlspecialchars($teacher['full_name']) ?>" 
                                             class="w-10 h-10 rounded-full mx-auto object-cover">
                                    </td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($teacher['full_name']) ?></td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($teacher['qualification'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($teacher['phone'] ?? '') ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <div class="flex space-x-2">
                                            <a href="teacher/teacher_template.php?id=<?= $teacher['user_id'] ?>" 
                                               target="_blank"
                                               class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded"
                                               title="View ID Card">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="teacher/teacher_template.php?id=<?= $teacher['user_id'] ?>&download=1" 
                                               class="bg-green-600 hover:bg-green-700 text-white p-2 rounded"
                                               title="Download ID Card">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            document.getElementById('student-section').classList.toggle('hidden', tabName !== 'student');
            document.getElementById('teacher-section').classList.toggle('hidden', tabName !== 'teacher');
            
            document.getElementById('student-tab').classList.toggle('border-blue-600', tabName === 'student');
            document.getElementById('student-tab').classList.toggle('text-blue-600', tabName === 'student');
            document.getElementById('student-tab').classList.toggle('border-transparent', tabName !== 'student');
            
            document.getElementById('teacher-tab').classList.toggle('border-blue-600', tabName === 'teacher');
            document.getElementById('teacher-tab').classList.toggle('text-blue-600', tabName === 'teacher');
            document.getElementById('teacher-tab').classList.toggle('border-transparent', tabName !== 'teacher');
        }

        // Search functionality for students
        document.getElementById('student-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#student-table tbody tr.student-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });

        // Search functionality for teachers
        document.getElementById('teacher-search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#teacher-table tbody tr.teacher-row');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>