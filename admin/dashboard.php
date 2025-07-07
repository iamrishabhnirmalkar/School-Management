<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
// Quick stats
$total_students = $conn->query('SELECT COUNT(*) FROM students')->fetch_row()[0];
$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT COUNT(DISTINCT student_id) FROM attendance WHERE date = '$today'")->fetch_row()[0];
$admin = $conn->query("SELECT * FROM admins WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
// Fetch students for table
$students = $conn->query("SELECT id, login_id, admission_no, name, class, section, is_active FROM students ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('hidden');
    }
    function openStudentModal(edit = false, student = null) {
        document.getElementById('studentModal').classList.remove('hidden');
        if (!edit) {
            document.getElementById('studentForm').reset();
            document.getElementById('modalTitle').innerText = 'Add Student';
            document.getElementById('student_id').value = '';
            document.getElementById('login_id').value = generateLoginId();
        } else {
            document.getElementById('modalTitle').innerText = 'Edit Student';
            for (const key in student) {
                if (document.getElementById(key)) {
                    document.getElementById(key).value = student[key];
                }
            }
        }
    }
    function closeStudentModal() {
        document.getElementById('studentModal').classList.add('hidden');
    }
    function generateLoginId() {
        // Example: STU + timestamp (for demo, use a better method in production)
        return 'STU' + Date.now().toString().slice(-6);
    }
    function editStudent(row) {
        const student = JSON.parse(row.dataset.student);
        openStudentModal(true, student);
    }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen flex-col md:flex-row">
        <!-- Mobile top bar -->
        <div class="md:hidden flex items-center justify-between bg-blue-800 text-white px-4 py-3">
            <div class="flex items-center gap-2">
                <?php if (!empty($admin['profile_img'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($admin['profile_img']); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                <?php endif; ?>
                <span class="font-bold text-lg"><?php echo htmlspecialchars($admin['name']); ?></span>
            </div>
            <button onclick="toggleSidebar()" class="focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
        </div>
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-blue-800 text-white flex flex-col py-8 px-4 fixed md:static h-full z-20 hidden md:flex">
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
                <a href="dashboard.php" class="block py-2 px-4 rounded hover:bg-blue-700 font-semibold bg-blue-900">Dashboard</a>
                <a href="students.php" class="block py-2 px-4 rounded hover:bg-blue-700">Manage Students</a>
                <a href="attendance.php" class="block py-2 px-4 rounded hover:bg-blue-700">Attendance</a>
                <a href="edit_profile.php" class="block py-2 px-4 rounded hover:bg-blue-700">Edit Profile</a>
            </nav>
            <a href="../logout.php" class="mt-10 block py-2 px-4 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">Logout</a>
        </aside>
        <!-- Main content -->
        <main class="flex-1 md:ml-64 p-4 md:p-10">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-blue-800">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
                <button onclick="history.back()" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm font-semibold focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    Back
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="bg-blue-100 text-blue-800 rounded-full p-4 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4V6a4 4 0 10-8 0v4m8 0a4 4 0 01-8 0" /></svg>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold"><?php echo $total_students; ?></div>
                        <div class="text-gray-600">Total Students</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center">
                    <div class="bg-yellow-100 text-yellow-800 rounded-full p-4 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5V3a1 1 0 00-1-1H9a1 1 0 00-1 1v9m0 0l4 4 4-4" /></svg>
                    </div>
                    <div>
                        <div class="text-xl md:text-2xl font-bold"><?php echo $today_attendance; ?></div>
                        <div class="text-gray-600">Today's Attendance Marked</div>
                    </div>
                </div>
            </div>
            <!-- Student Table & Add Button -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-blue-800">Students</h2>
                <button onclick="openStudentModal()" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-800 font-semibold">+ Add Student</button>
            </div>
            <div class="bg-white rounded-xl shadow-lg overflow-x-auto mb-8">
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
                        <tr data-student='<?php echo json_encode($row); ?>'>
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
                                <button onclick="editStudent(this.parentNode.parentNode)" class="text-blue-600 hover:underline mr-3">Edit</button>
                                <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- Student Modal -->
            <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">
                    <button onclick="closeStudentModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                    <h3 id="modalTitle" class="text-xl font-bold mb-4">Add Student</h3>
                    <form id="studentForm" method="post" action="student_form.php" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="id" id="student_id">
                        <div>
                            <label class="block font-medium mb-1">Login ID</label>
                            <input type="text" name="login_id" id="login_id" readonly class="w-full border rounded px-3 py-2 bg-gray-100">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Admission No</label>
                            <input type="text" name="admission_no" id="admission_no" required class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Name</label>
                            <input type="text" name="name" id="name" required class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Class</label>
                            <input type="text" name="class" id="class" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Section</label>
                            <input type="text" name="section" id="section" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Active</label>
                            <select name="is_active" id="is_active" class="w-full border rounded px-3 py-2">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-4">
                            <button type="button" onclick="closeStudentModal()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">Cancel</button>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-800 font-semibold">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
