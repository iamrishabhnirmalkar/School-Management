<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get student ID from URL
$student_id = $_GET['id'] ?? 0;

// Fetch complete student data
$student = $conn->query("
    SELECT 
        u.id, u.admission_number, u.full_name, u.email, u.phone, u.created_at,
        s.roll_number, s.admission_date, s.gender, s.blood_group, 
        s.parent_name, s.parent_phone, s.address, s.dob, s.status, s.photo,
        c.class_name, c.section,
        ba.id as bus_allocation_id, b.bus_number, b.route_name, ba.stop_name,
        (SELECT COUNT(*) FROM attendance WHERE student_id = u.id AND status = 'present') as attendance_present,
        (SELECT COUNT(*) FROM attendance WHERE student_id = u.id) as attendance_total,
        (SELECT COUNT(*) FROM book_issues WHERE student_id = u.id AND status = 'issued') as books_issued,
        (SELECT GROUP_CONCAT(fee_type SEPARATOR ', ') FROM fees WHERE student_id = u.id AND status = 'unpaid') as unpaid_fees
    FROM users u
    JOIN students s ON u.id = s.user_id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN bus_allocations ba ON s.bus_allocation_id = ba.id
    LEFT JOIN buses b ON ba.bus_id = b.id
    WHERE u.id = $student_id AND u.role = 'student'
")->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: index.php");
    exit;
}

// Calculate attendance percentage
$attendance_percentage = $student['attendance_total'] > 0
    ? round(($student['attendance_present'] / $student['attendance_total']) * 100, 2)
    : 0;

// Get recent attendance (last 5 records)
$recent_attendance = $conn->query("
    SELECT date, status, remarks 
    FROM attendance 
    WHERE student_id = $student_id 
    ORDER BY date DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get current issued books
$issued_books = $conn->query("
    SELECT b.title, b.author, bi.issue_date, bi.due_date
    FROM book_issues bi
    JOIN library_books b ON bi.book_id = b.id
    WHERE bi.student_id = $student_id AND bi.status = 'issued'
    ORDER BY bi.due_date
")->fetch_all(MYSQLI_ASSOC);
?>

<?php
$pageTitle = 'Student Details';
$activePage = 'students';
include '../_layout.php';
?>
        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Student Profile Header -->
                <div class="bg-blue-600 text-white p-6">
                    <div class="flex items-center space-x-6">
                        <?php if (!empty($student['photo'])): ?>
                            <img src="../../<?= htmlspecialchars($student['photo']) ?>" alt="Photo" class="w-24 h-24 rounded-full object-cover border">
                        <?php else: ?>
                            <div class="w-24 h-24 rounded-full bg-blue-500 flex items-center justify-center text-4xl font-bold text-white">
                                <?= substr($student['full_name'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <span class="absolute bottom-0 right-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold 
                                <?= $student['status'] == 'Active' ? 'bg-green-500' : ($student['status'] == 'Inactive' ? 'bg-yellow-500' : 'bg-blue-500') ?>">
                                <?= substr($student['status'], 0, 1) ?>
                            </span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold"><?= htmlspecialchars($student['full_name']) ?></h2>
                            <p class="text-blue-200"><?= htmlspecialchars($student['admission_number']) ?></p>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="flex items-center">
                                    <i class="fas fa-graduation-cap mr-2"></i>
                                    <?= htmlspecialchars($student['class_name'] ?? 'Not assigned') ?> <?= htmlspecialchars($student['section'] ?? '') ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-id-card mr-2"></i>
                                    Roll No: <?= htmlspecialchars($student['roll_number'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Details -->
                <div class="p-6">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Attendance</p>
                                    <p class="text-2xl font-bold"><?= $attendance_percentage ?>%</p>
                                    <p class="text-xs text-gray-500"><?= $student['attendance_present'] ?> present out of <?= $student['attendance_total'] ?></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-calendar-check text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Books Issued</p>
                                    <p class="text-2xl font-bold"><?= $student['books_issued'] ?? 0 ?></p>
                                    <p class="text-xs text-gray-500">Currently borrowed</p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Unpaid Fees</p>
                                    <p class="text-2xl font-bold"><?= $student['unpaid_fees'] ? count(explode(',', $student['unpaid_fees'])) : 0 ?></p>
                                    <p class="text-xs text-gray-500"><?= $student['unpaid_fees'] ?: 'None' ?></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                    <i class="fas fa-money-bill-wave text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Information Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-8">
                            <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600" data-tab="personal">
                                Personal Info
                            </button>
                            <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="academic">
                                Academic Info
                            </button>
                            <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="attendance">
                                Attendance
                            </button>
                            <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="library">
                                Library
                            </button>
                        </nav>
                    </div>

                    <!-- Personal Info Tab -->
                    <div id="personal-tab" class="tab-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['full_name']) ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Admission Number</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['admission_number']) ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Gender</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= $student['dob'] ? date('d M, Y', strtotime($student['dob'])) : 'N/A' ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Blood Group</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['blood_group'] ?? 'N/A') ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                                            <dd class="mt-1 text-sm">
                                                <span class="px-2 py-1 rounded-full <?= $student['status'] == 'Active' ? 'bg-green-100 text-green-800' : ($student['status'] == 'Inactive' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                                                    <?= htmlspecialchars($student['status']) ?>
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Address</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['address'] ?? 'N/A') ?></dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Parent Info -->
                                <h3 class="text-lg font-medium text-gray-900">Parent/Guardian Information</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Parent Name</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['parent_name'] ?? 'N/A') ?></dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500">Parent Phone</dt>
                                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['parent_phone'] ?? 'N/A') ?></dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Info Tab -->
                    <div id="academic-tab" class="tab-content hidden">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Class</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['class_name'] ?? 'Not assigned') ?> <?= htmlspecialchars($student['section'] ?? '') ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Roll Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($student['roll_number'] ?? 'N/A') ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Admission Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= $student['admission_date'] ? date('d M, Y', strtotime($student['admission_date'])) : 'N/A' ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= date('d M, Y', strtotime($student['created_at'])) ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Bus Info</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?php if (!empty($student['bus_allocation_id'])): ?>
                                            <span class="block font-medium">Bus: <?= htmlspecialchars($student['bus_number']) ?></span>
                                            <span class="block text-xs text-gray-500">Route: <?= htmlspecialchars($student['route_name']) ?></span>
                                            <span class="block text-xs text-gray-500">Stop: <?= htmlspecialchars($student['stop_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">No Bus</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Attendance Tab -->
                    <div id="attendance-tab" class="tab-content hidden">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Recent Attendance</h4>
                            <?php if (empty($recent_attendance)): ?>
                                <p class="text-gray-500">No attendance records found</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($recent_attendance as $attendance): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d M, Y', strtotime($attendance['date'])) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 py-1 text-xs rounded-full <?= $attendance['status'] == 'present' ? 'bg-green-100 text-green-800' : ($attendance['status'] == 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                            <?= ucfirst($attendance['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($attendance['remarks'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 text-right">
                                    <a href="../attendance/?student_id=<?= $student_id ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View Full Attendance <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Library Tab -->
                    <div id="library-tab" class="tab-content hidden">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Currently Issued Books</h4>
                            <?php if (empty($issued_books)): ?>
                                <p class="text-gray-500">No books currently issued</p>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($issued_books as $book): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($book['title']) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($book['author']) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M, Y', strtotime($book['issue_date'])) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?= strtotime($book['due_date']) < time() ? 'text-red-600' : 'text-gray-500' ?>">
                                                        <?= date('d M, Y', strtotime($book['due_date'])) ?>
                                                        <?php if (strtotime($book['due_date']) < time()): ?>
                                                            <span class="ml-2 text-xs text-red-600">(Overdue)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 text-right">
                                    <a href="../library/?student_id=<?= $student_id ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        View Full Library History <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active state from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });

                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Activate current tab
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('border-blue-500', 'text-blue-600');

                // Show current tab content
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.remove('hidden');
            });
        });
    </script>
</body>

</html>