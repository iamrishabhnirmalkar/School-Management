<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

$error = '';
$success = '';

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error: " . $file['error'];
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $error = "Please upload a CSV file";
    } else {
        // Process CSV file
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // Skip header row

        $conn->begin_transaction();
        try {
            $count = 0;

            while (($data = fgetcsv($handle)) !== false) {
                // Extract data from CSV
                $full_name = $conn->real_escape_string($data[0]);
                $email = $conn->real_escape_string($data[1]);
                $phone = $conn->real_escape_string($data[2]);
                $qualification_type = $conn->real_escape_string($data[3]);
                $specialization = $conn->real_escape_string($data[4]);
                $joining_date = $conn->real_escape_string($data[5]);
                $is_class_teacher = !empty($data[6]) ? 1 : 0;
                $class_id = !empty($data[7]) ? (int)$data[7] : null;

                // Generate teacher ID
                $last_teacher = $conn->query("SELECT login_id FROM users WHERE role='teacher' ORDER BY id DESC LIMIT 1")->fetch_assoc();
                $last_number = $last_teacher ? intval(substr($last_teacher['login_id'], 3)) : 0;
                $new_teacher_id = 'TCH' . str_pad($last_number + 1 + $count, 4, '0', STR_PAD_LEFT);

                // Insert into users table
                $conn->query("INSERT INTO users (login_id, role, full_name, email, phone) VALUES ('$new_teacher_id', 'teacher', '$full_name', '$email', '$phone')");
                $user_id = $conn->insert_id;

                // Insert into teachers table
                $conn->query("INSERT INTO teachers (user_id, qualification_type, specialization, joining_date) VALUES ($user_id, '$qualification_type', '$specialization', '$joining_date')");

                // Handle class teacher assignment
                if ($is_class_teacher && $class_id) {
                    $conn->query("UPDATE classes SET class_teacher_id = $user_id WHERE id = $class_id");
                }

                $count++;
            }

            $conn->commit();
            $success = "Successfully imported $count teachers!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error importing teachers: " . $e->getMessage();
        }

        fclose($handle);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Teachers - School ERP</title>
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
                        <p class="text-blue-200">Bulk Import Teachers</p>
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
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-user-plus w-5"></i>
                            <span>Add New Teacher</span>
                        </a>
                    </li>
                    <li>
                        <a href="import.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-file-import w-5"></i>
                            <span>Bulk Import</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Bulk Import Teachers</h2>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Import Form -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Upload CSV File</h3>

                        <form method="post" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                                <input type="file" name="csv_file" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <p class="text-xs text-gray-500 mt-1">Only .csv files are accepted</p>
                            </div>

                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <i class="fas fa-upload mr-2"></i> Upload and Import
                            </button>
                        </form>
                    </div>

                    <!-- CSV Format Instructions -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">CSV Format Instructions</h3>

                        <div class="bg-gray-50 p-4 rounded-md">
                            <p class="text-sm text-gray-700 mb-3">
                                Your CSV file should have the following columns in order:
                            </p>

                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Column</th>
                                        <th class="px-3 py-2 text-left">Description</th>
                                        <th class="px-3 py-2 text-left">Example</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-3 py-2">Full Name</td>
                                        <td class="px-3 py-2">Teacher's full name</td>
                                        <td class="px-3 py-2">John Smith</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Email</td>
                                        <td class="px-3 py-2">Email address</td>
                                        <td class="px-3 py-2">john@school.com</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Phone</td>
                                        <td class="px-3 py-2">Phone number</td>
                                        <td class="px-3 py-2">1234567890</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Qualification</td>
                                        <td class="px-3 py-2">Highest qualification</td>
                                        <td class="px-3 py-2">Master</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Specialization</td>
                                        <td class="px-3 py-2">Subject specialization</td>
                                        <td class="px-3 py-2">Mathematics</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Joining Date</td>
                                        <td class="px-3 py-2">YYYY-MM-DD format</td>
                                        <td class="px-3 py-2">2023-01-15</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Is Class Teacher</td>
                                        <td class="px-3 py-2">1 for yes, 0 for no</td>
                                        <td class="px-3 py-2">1</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2">Class ID</td>
                                        <td class="px-3 py-2">Only if class teacher</td>
                                        <td class="px-3 py-2">5</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-4">
                                <a href="sample_teachers.csv" download class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                                    <i class="fas fa-download mr-2"></i> Download Sample CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>