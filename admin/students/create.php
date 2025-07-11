<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Generate admission number
$last_admission = $conn->query("SELECT admission_number FROM users WHERE role='student' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$last_number = $last_admission ? intval(substr($last_admission['admission_number'] ?? '', 3)) : 0;
$new_admission_number = 'ADM' . str_pad($last_number + 1, 5, '0', STR_PAD_LEFT);

// Get classes for dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_number = $_POST['admission_number'] ?? $new_admission_number; // Use form value if provided
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'];
    $roll_number = $_POST['roll_number'];
    $admission_date = $_POST['admission_date'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (admission_number, login_id, role, full_name, email, phone) VALUES (?, ?, 'student', ?, ?, ?)");
        $stmt->bind_param("sssss", $admission_number, $admission_number, $full_name, $email, $phone);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // Insert into students table
        $stmt = $conn->prepare("INSERT INTO students (user_id, class_id, roll_number, admission_date, gender, blood_group, parent_name, parent_phone, address, dob) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssss", $user_id, $class_id, $roll_number, $admission_date, $gender, $blood_group, $parent_name, $parent_phone, $address, $dob);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Student added successfully!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header (same as dashboard) -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Add New Student</p>
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
        <!-- Sidebar Navigation (same as dashboard) -->
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
                            <span>Student List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-user-plus w-5"></i>
                            <span>Add New Student</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Add New Student</h2>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Student Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number</label>
                            <input type="text" name="admission_number" value="<?= isset($_POST['admission_number']) ? htmlspecialchars($_POST['admission_number']) : $new_admission_number ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="dob" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                            <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select</option>
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
                    </div>

                    <!-- Academic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Academic Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                            <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>">
                                        <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                            <input type="text" name="roll_number" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                            <input type="date" name="admission_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Parent/Guardian Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Name</label>
                                <input type="text" name="parent_name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Phone</label>
                                <input type="tel" name="parent_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end space-x-4">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Student</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>