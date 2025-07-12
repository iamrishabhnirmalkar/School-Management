<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get all students for dropdown
$students = $conn->query("
    SELECT u.id, u.admission_number, u.full_name, u.email, u.phone, s.parent_name, s.dob, s.photo, s.address, c.class_name, c.section
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE u.role = 'student' 
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Student ID Card Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
                        <p class="text-blue-200">ID Card Generator</p>
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

    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Navigation Links -->
            <div class="mb-6 flex justify-center space-x-4">
                <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-id-card mr-2"></i>General ID Cards
                </a>
                <a href="../student-id-cards/" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                    <i class="fas fa-user-graduate mr-2"></i>Student ID Cards
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">General ID Card Generator</h2>
                
                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button class="tab-button py-2 px-4 border-b-2 font-medium text-sm border-blue-500 text-blue-600" data-tab="manual">
                        <i class="fas fa-edit mr-2"></i>Manual Entry
                    </button>
                    <button class="tab-button py-2 px-4 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="student">
                        <i class="fas fa-user-graduate mr-2"></i>Select Student
                    </button>
                </div>

                <!-- Manual Entry Form -->
                <div id="manual-tab" class="tab-content">
                    <form action="view.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="manual">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" name="ename" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter full name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Student ID Number *</label>
                                <input type="text" name="nid" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter student ID" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                                <input type="text" name="class_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="e.g., Class 10 A" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name *</label>
                                <input type="text" name="faname" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter father's name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name *</label>
                                <input type="text" name="mname" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter mother's name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                                <input type="date" name="dname" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter phone number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Signature *</label>
                                <input type="text" name="si" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter signature name" required>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter complete address" required></textarea>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Photo *</label>
                            <input type="file" name="file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-id-card mr-2"></i>Generate ID Card
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Student Selection Form -->
                <div id="student-tab" class="tab-content hidden">
                    <form action="view.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="student">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Student *</label>
                            <select name="student_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required onchange="loadStudentData()">
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>" 
                                            data-name="<?= htmlspecialchars($student['full_name']) ?>"
                                            data-admission="<?= htmlspecialchars($student['admission_number']) ?>"
                                            data-parent="<?= htmlspecialchars($student['parent_name'] ?? '') ?>"
                                            data-dob="<?= htmlspecialchars($student['dob'] ?? '') ?>"
                                            data-photo="<?= htmlspecialchars($student['photo'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($student['address'] ?? '') ?>"
                                            data-class="<?= htmlspecialchars($student['class_name'] ?? '') ?>"
                                            data-section="<?= htmlspecialchars($student['section'] ?? '') ?>">
                                        <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['admission_number']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="student-preview" class="hidden bg-gray-50 rounded-lg p-4 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Student Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" name="ename" id="student_name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                                    <input type="text" name="nid" id="student_admission" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                    <input type="text" name="class_name" id="student_class" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                                    <input type="text" name="faname" id="student_parent" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="text" name="dname" id="student_dob" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="phone" id="student_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" id="student_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                                <input type="text" name="mname" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter mother's name" required>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Signature</label>
                                <input type="text" name="si" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter signature name" required>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                                <div id="student_photo_preview" class="mt-2"></div>
                                <input type="file" name="file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2">
                                <p class="text-xs text-gray-500 mt-1">Upload new photo or use existing one</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-id-card mr-2"></i>Generate ID Card
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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

        // Load student data when selected
        function loadStudentData() {
            const select = document.querySelector('select[name="student_id"]');
            const preview = document.getElementById('student-preview');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                // Populate form fields
                document.getElementById('student_name').value = selectedOption.getAttribute('data-name');
                document.getElementById('student_admission').value = selectedOption.getAttribute('data-admission');
                document.getElementById('student_parent').value = selectedOption.getAttribute('data-parent');
                document.getElementById('student_dob').value = selectedOption.getAttribute('data-dob');
                document.getElementById('student_phone').value = selectedOption.getAttribute('data-phone');
                document.getElementById('student_address').value = selectedOption.getAttribute('data-address');
                
                // Combine class and section
                const className = selectedOption.getAttribute('data-class');
                const section = selectedOption.getAttribute('data-section');
                const fullClass = className + (section ? ' ' + section : '');
                document.getElementById('student_class').value = fullClass;
                
                // Show photo preview if exists
                const photoPreview = document.getElementById('student_photo_preview');
                const photoPath = selectedOption.getAttribute('data-photo');
                if (photoPath) {
                    photoPreview.innerHTML = `<img src="../../${photoPath}" alt="Student Photo" class="w-20 h-20 object-cover rounded border">`;
                } else {
                    photoPreview.innerHTML = '<p class="text-gray-500 text-sm">No photo available</p>';
                }
                
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>

</html>