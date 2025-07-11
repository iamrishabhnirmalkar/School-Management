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

// Fetch student data
$student = $conn->query("
    SELECT u.*, s.*, c.id as class_id, c.class_name, c.section 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE u.id = $student_id AND u.role = 'student'
")->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: index.php");
    exit;
}

// Get classes for dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $status = $_POST['status'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update users table
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $student_id);
        $stmt->execute();

        // Update students table
        $stmt = $conn->prepare("UPDATE students SET class_id = ?, roll_number = ?, admission_date = ?, gender = ?, blood_group = ?, parent_name = ?, parent_phone = ?, address = ?, dob = ?, status = ? WHERE user_id = ?");
        $stmt->bind_param("isssssssssi", $class_id, $roll_number, $admission_date, $gender, $blood_group, $parent_name, $parent_phone, $address, $dob, $status, $student_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Student updated successfully!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error updating student: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - School ERP</title>
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
                        <p class="text-blue-200">Edit Student</p>
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
                            <span>Student List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
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
                <h2 class="text-xl font-bold mb-6 text-gray-800">Edit Student: <?= htmlspecialchars($student['full_name']) ?></h2>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Student Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number</label>
                            <input type="text" value="<?= htmlspecialchars($student['admission_number']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                            <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select</option>
                                <option value="A+" <?= $student['blood_group'] == 'A+' ? 'selected' : '' ?>>A+</option>
                                <option value="A-" <?= $student['blood_group'] == 'A-' ? 'selected' : '' ?>>A-</option>
                                <option value="B+" <?= $student['blood_group'] == 'B+' ? 'selected' : '' ?>>B+</option>
                                <option value="B-" <?= $student['blood_group'] == 'B-' ? 'selected' : '' ?>>B-</option>
                                <option value="AB+" <?= $student['blood_group'] == 'AB+' ? 'selected' : '' ?>>AB+</option>
                                <option value="AB-" <?= $student['blood_group'] == 'AB-' ? 'selected' : '' ?>>AB-</option>
                                <option value="O+" <?= $student['blood_group'] == 'O+' ? 'selected' : '' ?>>O+</option>
                                <option value="O-" <?= $student['blood_group'] == 'O-' ? 'selected' : '' ?>>O-</option>
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
                                    <option value="<?= $class['id'] ?>" <?= $student['class_id'] == $class['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                            <input type="text" name="roll_number" value="<?= htmlspecialchars($student['roll_number']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                            <input type="date" name="admission_date" value="<?= htmlspecialchars($student['admission_date']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="Active" <?= $student['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $student['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="Alumni" <?= $student['status'] == 'Alumni' ? 'selected' : '' ?>>Alumni</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($student['address']) ?></textarea>
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Parent/Guardian Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Name</label>
                                <input type="text" name="parent_name" value="<?= htmlspecialchars($student['parent_name']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Phone</label>
                                <input type="tel" name="parent_phone" value="<?= htmlspecialchars($student['parent_phone']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end space-x-4">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Student</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>