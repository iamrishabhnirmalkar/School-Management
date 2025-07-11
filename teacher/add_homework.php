<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];

// Fetch classes and subjects assigned to this teacher
$classes = $conn->query("SELECT DISTINCT c.id, c.class_name, c.section FROM classes c JOIN subjects s ON c.id = s.class_id WHERE s.teacher_id = $teacher_id")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, subject_name, class_id FROM subjects WHERE teacher_id = $teacher_id")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $class_id = intval($_POST['class_id']);
    $subject_id = intval($_POST['subject_id']);
    $due_date = $_POST['due_date'];
    $file_path = null;

    // Handle file upload (optional)
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = '../uploads/homework/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = 'uploads/homework/' . $filename;
        } else {
            $error = 'File upload failed.';
        }
    }

    if (!$error && $title && $description && $class_id && $subject_id && $due_date) {
        $stmt = $conn->prepare("INSERT INTO homework (teacher_id, class_id, subject_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissss", $teacher_id, $class_id, $subject_id, $title, $description, $due_date, $file_path);
        if ($stmt->execute()) {
            $success = 'Homework assigned successfully!';
            header("Location: homework.php");
            exit;
        } else {
            $error = 'Error assigning homework.';
        }
    } elseif (!$error) {
        $error = 'All fields except file are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign New Homework - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Assign Homework</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-xl mx-auto">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Assign New Homework</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>">
                                <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" data-class="<?= $subject['class_id'] ?>">
                                <?= htmlspecialchars($subject['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (optional)</label>
                    <input type="file" name="file" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end space-x-2">
                    <a href="homework.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Assign Homework</button>
                </div>
            </form>
        </div>
    </main>
    <script>
    // Optional: Filter subjects by selected class
    document.querySelector('select[name="class_id"]').addEventListener('change', function() {
        var classId = this.value;
        var subjectSelect = document.querySelector('select[name="subject_id"]');
        Array.from(subjectSelect.options).forEach(function(opt) {
            if (!opt.value) return;
            opt.style.display = (opt.getAttribute('data-class') === classId) ? '' : 'none';
        });
        subjectSelect.value = '';
    });
    </script>
</body>
</html> 