<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get all students for dropdown
$students = $conn->query("
    SELECT u.id, u.admission_number, u.full_name, u.email, u.phone, 
           s.parent_name, s.dob, s.photo, s.address, s.gender, s.blood_group,
           c.class_name, c.section, s.roll_number, s.admission_date
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card Generator - School ERP</title>
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
                        <p class="text-blue-200">Student ID Card Generator</p>
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
    <div class="container mx-auto px-6 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Navigation -->
            <div class="mb-6 flex justify-between items-center">
                <a href="../../admin/dashboard.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <div class="flex space-x-4">
                    <a href="../identity-cards/" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-id-card mr-2"></i>General ID Cards
                    </a>
                    <a href="index.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-user-graduate mr-2"></i>Student ID Cards
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Student ID Card Generator</h2>
                
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
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" name="student_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter full name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number *</label>
                                <input type="text" name="admission_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter admission number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                                <input type="text" name="roll_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter roll number">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                                <input type="text" name="class_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Class 10 A" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name *</label>
                                <input type="text" name="father_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter father's name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name *</label>
                                <input type="text" name="mother_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter mother's name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                                <input type="date" name="dob" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Blood Group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter phone number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter email address">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                                <input type="date" name="admission_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter complete address" required></textarea>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Photo *</label>
                            <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <p class="text-xs text-gray-500 mt-1">Recommended size: 200x250 pixels, JPG or PNG format</p>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Signature Name</label>
                            <input type="text" name="signature" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter signature name">
                        </div>

                        <div class="mt-6 flex justify-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md font-medium flex items-center">
                                <i class="fas fa-id-card mr-2"></i>Generate Student ID Card
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
                            <select name="student_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="loadStudentData()">
                                <option value="">Choose a student...</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>" 
                                            data-name="<?= htmlspecialchars($student['full_name']) ?>"
                                            data-admission="<?= htmlspecialchars($student['admission_number']) ?>"
                                            data-roll="<?= htmlspecialchars($student['roll_number'] ?? '') ?>"
                                            data-parent="<?= htmlspecialchars($student['parent_name'] ?? '') ?>"
                                            data-dob="<?= htmlspecialchars($student['dob'] ?? '') ?>"
                                            data-photo="<?= htmlspecialchars($student['photo'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                                            data-email="<?= htmlspecialchars($student['email'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($student['address'] ?? '') ?>"
                                            data-class="<?= htmlspecialchars($student['class_name'] ?? '') ?>"
                                            data-section="<?= htmlspecialchars($student['section'] ?? '') ?>"
                                            data-gender="<?= htmlspecialchars($student['gender'] ?? '') ?>"
                                            data-blood="<?= htmlspecialchars($student['blood_group'] ?? '') ?>"
                                            data-admission-date="<?= htmlspecialchars($student['admission_date'] ?? '') ?>">
                                        <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['admission_number']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="student-preview" class="hidden bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Student Information Preview</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" name="student_name" id="student_name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number</label>
                                    <input type="text" name="admission_number" id="student_admission" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                                    <input type="text" name="roll_number" id="student_roll" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                    <input type="text" name="class_name" id="student_class" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                                    <input type="text" name="father_name" id="student_parent" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="text" name="dob" id="student_dob" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                    <input type="text" name="gender" id="student_gender" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                    <input type="text" name="blood_group" id="student_blood" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="phone" id="student_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="text" name="email" id="student_email" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                                    <input type="text" name="admission_date" id="student_admission_date" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" id="student_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly></textarea>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name *</label>
                                <input type="text" name="mother_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter mother's name" required>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Signature Name</label>
                                <input type="text" name="signature" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter signature name">
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload New Photo (Optional)</label>
                                <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Leave empty to use existing student photo</p>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md font-medium flex items-center">
                                <i class="fas fa-id-card mr-2"></i>Generate Student ID Card
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Add active class to clicked tab
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('border-blue-500', 'text-blue-600');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab content
                const tabName = button.getAttribute('data-tab');
                document.getElementById(tabName + '-tab').classList.remove('hidden');
            });
        });

        // Load student data when student is selected
        function loadStudentData() {
            const select = document.querySelector('select[name="student_id"]');
            const preview = document.getElementById('student-preview');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                
                // Populate form fields
                document.getElementById('student_name').value = option.dataset.name || '';
                document.getElementById('student_admission').value = option.dataset.admission || '';
                document.getElementById('student_roll').value = option.dataset.roll || '';
                document.getElementById('student_parent').value = option.dataset.parent || '';
                document.getElementById('student_dob').value = option.dataset.dob ? new Date(option.dataset.dob).toLocaleDateString() : '';
                document.getElementById('student_phone').value = option.dataset.phone || '';
                document.getElementById('student_email').value = option.dataset.email || '';
                document.getElementById('student_address').value = option.dataset.address || '';
                document.getElementById('student_class').value = (option.dataset.class || '') + ' ' + (option.dataset.section || '');
                document.getElementById('student_gender').value = option.dataset.gender || '';
                document.getElementById('student_blood').value = option.dataset.blood || '';
                document.getElementById('student_admission_date').value = option.dataset.admissionDate ? new Date(option.dataset.admissionDate).toLocaleDateString() : '';
                
                // Show preview
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>

</html> 