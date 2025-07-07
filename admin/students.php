<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $where = "WHERE login_id LIKE '%$search_esc%' OR admission_no LIKE '%$search_esc%' OR name LIKE '%$search_esc%' OR class LIKE '%$search_esc%' OR section LIKE '%$search_esc%'";
}
$students = $conn->query("SELECT id, login_id, admission_no, name, class, section, is_active FROM students $where ORDER BY id DESC");
// Quick stats
$total_students = $conn->query('SELECT COUNT(*) FROM students')->fetch_row()[0];
$active_students = $conn->query('SELECT COUNT(*) FROM students WHERE is_active = 1')->fetch_row()[0];
$inactive_students = $conn->query('SELECT COUNT(*) FROM students WHERE is_active = 0')->fetch_row()[0];
$admin = $conn->query("SELECT * FROM admins WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-blue-800 text-white flex flex-col py-8 px-4 fixed h-full">
            <div class="flex flex-col items-center mb-10">
                <?php if (!empty($admin['profile_img'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($admin['profile_img']); ?>" alt="Profile" class="w-16 h-16 rounded-full object-cover mb-2">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                <?php endif; ?>
                <span class="text-lg font-bold"><?php echo htmlspecialchars($admin['name']); ?></span>
                <span class="text-xs text-blue-200">Admin</span>
            </div>
            <nav class="flex-1 space-y-4">
                <a href="dashboard.php" class="block py-2 px-4 rounded hover:bg-blue-700">Dashboard</a>
                <a href="students.php" class="block py-2 px-4 rounded hover:bg-blue-700 font-semibold bg-blue-900">Manage Students</a>
                <a href="attendance.php" class="block py-2 px-4 rounded hover:bg-blue-700">Attendance</a>
                <a href="#" class="block py-2 px-4 rounded hover:bg-blue-700">Settings</a>
                <a href="edit_profile.php" class="block py-2 px-4 rounded hover:bg-blue-700">Edit Profile</a>
            </nav>
            <a href="../logout.php" class="mt-10 block py-2 px-4 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">Logout</a>
        </aside>
        <!-- Main content -->
        <main class="flex-1 ml-64 p-10">
            <h1 class="text-3xl font-bold text-blue-800 mb-6">Student Management</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="bg-blue-100 text-blue-800 rounded-full p-4 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4V6a4 4 0 10-8 0v4m8 0a4 4 0 01-8 0" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold"><?php echo $total_students; ?></div>
                        <div class="text-gray-600">Total Students</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="bg-green-100 text-green-800 rounded-full p-4 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104.896-2 2-2s2 .896 2 2-.896 2-2 2-2-.896-2-2zm-6 8v-1a4 4 0 014-4h4a4 4 0 014 4v1" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold"><?php echo $active_students; ?></div>
                        <div class="text-gray-600">Active Students</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="bg-red-100 text-red-800 rounded-full p-4 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold"><?php echo $inactive_students; ?></div>
                        <div class="text-gray-600">Inactive Students</div>
                    </div>
                </div>
            </div>
            <!-- Placeholder for future graph -->
            <div class="bg-white rounded-xl shadow p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Student Stats (Future Graph)</h2>
                <canvas id="studentChart" height="80"></canvas>
            </div>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-center font-semibold">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                <form method="get" class="flex gap-2 w-full md:w-auto">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, ID, class..." class="border rounded px-3 py-2 w-full md:w-64">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-800">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="students.php" class="ml-2 text-blue-600 hover:underline">Clear</a>
                    <?php endif; ?>
                </form>
                <a href="student_form.php" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-800 transition-colors font-semibold">+ Add Student</a>
            </div>
            <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Login ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Admission No</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Class</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Section</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Active</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['login_id']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['admission_no']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['class']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['section']); ?></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <?php if ($row['is_active']): ?>
                                    <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                <a href="student_form.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline mr-3">Edit</a>
                                <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="dashboard.php" class="mt-8 inline-block text-blue-500 hover:underline">&larr; Back to Dashboard</a>
        </main>
    </div>
    <script>
    // Sample Chart.js graph (future: replace with real data)
    const ctx = document.getElementById('studentChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive'],
            datasets: [{
                data: [<?php echo $active_students; ?>, <?php echo $inactive_students; ?>],
                backgroundColor: ['#22c55e', '#ef4444'],
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            cutout: '70%',
        }
    });
    </script>
</body>
</html> 