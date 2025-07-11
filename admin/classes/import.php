<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

$error = '';
$success = '';
$imported_count = 0;

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // Check for upload errors
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error: ' . $_FILES['csv_file']['error'];
    } else {
        // Check file type
        $file_type = $_FILES['csv_file']['type'];
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

        if (!in_array($file_type, $allowed_types)) {
            $error = 'Only CSV files are allowed';
        } else {
            // Open the uploaded file
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

            // Start transaction
            $conn->begin_transaction();

            try {
                $header = fgetcsv($file); // Skip header row

                // Expected CSV columns
                $expected_columns = [
                    'Class Name',
                    'Section',
                    'Class Teacher'
                ];

                // Validate CSV header
                if ($header !== $expected_columns) {
                    throw new Exception("Invalid CSV format. Please use the provided template.");
                }

                // Process each row
                while (($row = fgetcsv($file)) !== false) {
                    // Skip empty rows
                    if (empty(array_filter($row))) continue;

                    // Map CSV data to variables
                    $class_name = trim($row[0]);
                    $section = trim($row[1]);
                    $teacher_name = trim($row[2]);

                    // Validate required fields
                    if (empty($class_name)) {
                        throw new Exception("Class Name is required for all classes");
                    }

                    // Get teacher ID if specified
                    $teacher_id = null;
                    if (!empty($teacher_name)) {
                        $teacher_stmt = $conn->prepare("SELECT id FROM users WHERE full_name = ? AND role = 'teacher'");
                        $teacher_stmt->bind_param("s", $teacher_name);
                        $teacher_stmt->execute();
                        $teacher_result = $teacher_stmt->get_result();

                        if ($teacher_result->num_rows > 0) {
                            $teacher_id = $teacher_result->fetch_assoc()['id'];
                        } else {
                            throw new Exception("Teacher not found: $teacher_name");
                        }
                    }

                    // Insert class
                    $class_stmt = $conn->prepare("INSERT INTO classes (class_name, section, class_teacher_id) VALUES (?, ?, ?)");
                    $class_stmt->bind_param("ssi", $class_name, $section, $teacher_id);
                    $class_stmt->execute();

                    $imported_count++;
                }

                $conn->commit();
                $success = "Successfully imported $imported_count classes";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Import failed: " . $e->getMessage();
            }

            fclose($file);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import Classes - School ERP</title>
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
                        <p class="text-blue-200">Bulk Import Classes</p>
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
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-plus-circle w-5"></i>
                            <span>Add New Class</span>
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
                <h2 class="text-xl font-bold mb-6 text-gray-800">Bulk Import Classes</h2>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($success) ?>
                        <?php if ($imported_count > 0): ?>
                            <div class="mt-2">
                                <a href="index.php" class="text-green-700 hover:text-green-900 font-medium">
                                    View Imported Classes <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Import Form -->
                    <div>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <i class="fas fa-file-csv text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Upload CSV File</h3>
                                    <p class="text-sm text-gray-500">Select a properly formatted CSV file to import</p>

                                    <div class="mt-4">
                                        <label class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center">
                                            <i class="fas fa-upload mr-2"></i>
                                            Choose File
                                            <input type="file" name="csv_file" accept=".csv" class="hidden" required>
                                        </label>
                                        <p id="file-name" class="text-sm text-gray-500 mt-2">No file selected</p>
                                    </div>

                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-md font-medium">
                                        <i class="fas fa-database mr-2"></i> Import Classes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Important:</strong> The CSV file must follow the exact format.
                                        <a href="#" id="download-template" class="text-yellow-700 underline">Download template</a>
                                        for reference.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Import Instructions</h3>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-medium text-gray-800">CSV Format Requirements:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li>First row must be header with exact column names</li>
                                        <li>Use commas (,) as separators</li>
                                        <li>Enclose text fields in double quotes (") if they contain commas</li>
                                    </ul>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-800">Required Fields:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li><strong>Class Name</strong> - Name of the class (e.g., "Class 1")</li>
                                        <li><strong>Section</strong> - Section of the class (e.g., "A", "B")</li>
                                    </ul>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-800">Optional Fields:</h4>
                                    <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                                        <li><strong>Class Teacher</strong> - Full name of existing teacher</li>
                                        <li>Leave blank if no class teacher assigned</li>
                                    </ul>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-800">Example CSV Content:</h4>
                                    <div class="mt-2 bg-gray-100 p-2 rounded text-xs font-mono overflow-x-auto">
                                        Class Name,Section,Class Teacher<br>
                                        Class 1,A,John Smith<br>
                                        Class 1,B,Jane Doe<br>
                                        Class 2,A,<br>
                                        Class 3,B,Robert Johnson
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show selected file name
        document.querySelector('input[name="csv_file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });

        // Template download
        document.getElementById('download-template').addEventListener('click', function(e) {
            e.preventDefault();

            // Create CSV template content
            const csvContent = [
                ['Class Name', 'Section', 'Class Teacher'],
                ['Class 1', 'A', 'John Smith'],
                ['Class 1', 'B', 'Jane Doe'],
                ['Class 2', 'A', ''],
                ['Class 3', 'B', 'Robert Johnson']
            ].map(row => row.join(',')).join('\n');

            // Create download link
            const blob = new Blob([csvContent], {
                type: 'text/csv'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'class_import_template.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    </script>
</body>

</html>