<?php
session_start();
require_once '../../config.php';

// Authentication check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$subject_id = $_GET['id'];

// Fetch subject data
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    $_SESSION['error'] = "Subject not found!";
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $class_id = $_POST['class_id'];

    // Update subject
    $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, class_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $subject_name, $subject_code, $class_id, $subject_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Subject updated successfully!";
        header("Location: view.php?id=" . $subject_id);
        exit;
    } else {
        $error = "Error updating subject: " . $conn->error;
    }
}

// Get classes for dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header (Same as index.php) -->
    <header class="bg-blue-700 text-white shadow-md">
        <!-- ... same header as index.php ... -->
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar (Same as index.php) -->
        <aside class="w-64 flex-shrink-0">
            <!-- ... same sidebar as index.php ... -->
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Header with back button -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Edit Subject</h2>
                    <a href="view.php?id=<?= $subject['id'] ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to View
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Subject Information -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                Subject Details
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="subject_name" class="block text-sm font-medium text-gray-700 mb-1">Subject Name*</label>
                                    <input type="text" id="subject_name" name="subject_name" value="<?= htmlspecialchars($subject['subject_name']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="subject_code" class="block text-sm font-medium text-gray-700 mb-1">Subject Code</label>
                                    <input type="text" id="subject_code" name="subject_code" value="<?= htmlspecialchars($subject['subject_code']) ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Class Assignment -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-users-class text-purple-500 mr-2"></i>
                                Class Assignment
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class*</label>
                                    <select id="class_id" name="class_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class['id'] ?>" <?= $class['id'] == $subject['class_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <a href="view.php?id=<?= $subject['id'] ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>