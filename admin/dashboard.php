<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

// Get stats
$stats = [
    'students' => 0,
    'teachers' => 0,
    'classes' => 0,
    'attendance' => 0,
    'fees_due' => 0,
    'books' => 0,
    'buses' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'");
$stats['students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'");
$stats['teachers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];

$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='present'");
$stats['attendance'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status='unpaid'");
$stats['fees_due'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM library_books");
$stats['books'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM buses");
$stats['buses'] = $result->fetch_assoc()['count'];

// Get recent activities
$recent_activities = [];
$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = [
        'title' => 'New Notice: ' . $row['title'],
        'description' => substr($row['content'], 0, 50) . '...',
        'time' => $row['created_at'],
        'color' => 'blue',
        'icon' => 'fas fa-bullhorn'
    ];
}

// Get recent notices
$recent_notices = [];
$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 3");
while ($row = $result->fetch_assoc()) {
    $recent_notices[] = $row;
}

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
include '_layout.php';
?>
        <!-- Main Dashboard Content -->
        <main class="flex-1">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Student Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Students</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['students'] ?></p>
                            <a href="students/" class="text-blue-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Teacher Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-chalkboard-teacher text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Teachers</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['teachers'] ?></p>
                            <a href="teachers/" class="text-green-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Class Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-school text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Classes</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['classes'] ?></p>
                            <a href="classes/" class="text-purple-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Today's Attendance</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['attendance'] ?></p>
                            <a href="attendance/" class="text-orange-600 text-sm hover:underline">View Report</a>
                        </div>
                    </div>
                </div>

                <!-- Fees Due Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Pending Fees</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['fees_due'] ?></p>
                            <a href="fees/" class="text-red-600 text-sm hover:underline">Manage Fees</a>
                        </div>
                    </div>
                </div>

                <!-- Library Books Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <i class="fas fa-book text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Library Books</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['books'] ?></p>
                            <a href="library/" class="text-indigo-600 text-sm hover:underline">Library</a>
                        </div>
                    </div>
                </div>

                <!-- Buses Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                            <i class="fas fa-bus text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Transport Buses</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['buses'] ?></p>
                            <a href="transport/" class="text-teal-600 text-sm hover:underline">Transport</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <a href="students/create.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Student</span>
                    </a>
                    <a href="teachers/create.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Teacher</span>
                    </a>
                    <a href="classes/create.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-school text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Class</span>
                    </a>
                    <a href="notices/create.php" class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-bullhorn text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Post Notice</span>
                    </a>
                    <a href="examinations/create.php" class="flex flex-col items-center justify-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-clipboard-list text-red-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Exam</span>
                    </a>
                    <a href="fees/collect.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Collect Fee</span>
                    </a>
                    <a href="library/issue.php" class="flex flex-col items-center justify-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-book-open text-indigo-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Issue Book</span>
                    </a>
                    <a href="transport/add.php" class="flex flex-col items-center justify-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                        <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-bus-alt text-teal-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Bus</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activities and Notices -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Recent Activities</h2>
                    <div class="space-y-4">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-activity text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">No recent activities.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="flex items-start space-x-3 p-3 border-l-4 border-blue-500 bg-blue-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <i class="<?= $activity['icon'] ?> text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 mb-1"><?= htmlspecialchars($activity['title']) ?></p>
                                        <p class="text-gray-500 text-xs mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                        <p class="text-gray-400 text-xs"><i class="fas fa-clock mr-1"></i><?= htmlspecialchars($activity['time']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Recent Notices</h2>
                    <div class="space-y-4">
                        <?php if (empty($recent_notices)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-bullhorn text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">No recent notices.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_notices as $notice): ?>
                                <div class="flex items-start space-x-3 p-3 border-l-4 border-orange-500 bg-orange-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-bullhorn text-orange-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 mb-1"><?= htmlspecialchars($notice['title']) ?></p>
                                        <p class="text-gray-500 text-xs mb-1"><?= htmlspecialchars($notice['content']) ?></p>
                                        <p class="text-gray-400 text-xs"><i class="fas fa-clock mr-1"></i><?= htmlspecialchars($notice['created_at']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>