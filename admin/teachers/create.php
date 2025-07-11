<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Generate teacher ID
$last_teacher = $conn->query("SELECT login_id FROM users WHERE role='teacher' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$last_number = $last_teacher ? intval(substr($last_teacher['login_id'] ?? '', 3)) : 0;
$new_teacher_id = 'TCH' . str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? $new_teacher_id; // Use form value if provided
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $qualification_type = $_POST['qualification_type'];
    $specialization = $_POST['specialization'];
    $joining_date = $_POST['joining_date'];
    $is_class_teacher = isset($_POST['is_class_teacher']) ? 1 : 0;
    $class_id = $_POST['class_id'] ?? null;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (login_id, role, full_name, email, phone) VALUES (?, 'teacher', ?, ?, ?)");
        $stmt->bind_param("ssss", $login_id, $full_name, $email, $phone);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // Insert into teachers table
        $stmt = $conn->prepare("INSERT INTO teachers (user_id, qualification_type, specialization, joining_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $qualification_type, $specialization, $joining_date);
        $stmt->execute();

        // Update class teacher if selected
        if ($is_class_teacher && $class_id) {
            $conn->query("UPDATE classes SET class_teacher_id = $user_id WHERE id = $class_id");
        }

        $conn->commit();
        $_SESSION['success'] = "Teacher added successfully!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding teacher: " . $e->getMessage();
    }
}

// Get classes for class teacher dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher - School ERP</title>
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
                        <p class="text-blue-200">Add New Teacher</p>
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
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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
                <h2 class="text-xl font-bold mb-6 text-gray-800">Add New Teacher</h2>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Teacher Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Teacher Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teacher ID</label>
                            <input type="text" name="login_id" value="<?= isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : $new_teacher_id ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Professional Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Highest Qualification *</label>
                            <select name="qualification_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select Qualification</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Bachelor">Bachelor's Degree</option>
                                <option value="Master">Master's Degree</option>
                                <option value="PhD">PhD</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                            <input type="text" name="specialization" placeholder="e.g., Mathematics, Physics" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Joining Date *</label>
                            <input type="date" name="joining_date" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div class="mt-6">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="is_class_teacher" name="is_class_teacher" type="checkbox" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_class_teacher" class="font-medium text-gray-700">Is Class Teacher</label>
                                    <p class="text-gray-500">Assign this teacher as a class teacher</p>
                                </div>
                            </div>

                            <div id="class_selection" class="mt-2 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Class</label>
                                <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>">
                                            <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end space-x-4">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Teacher</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Show/hide class selection based on checkbox
        document.getElementById('is_class_teacher').addEventListener('change', function(e) {
            document.getElementById('class_selection').classList.toggle('hidden', !e.target.checked);
        });
    </script>
</body>

</html>