<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$homework_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch homework details
$stmt = $conn->prepare("SELECT * FROM homework WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $homework_id, $teacher_id);
$stmt->execute();
$hw = $stmt->get_result()->fetch_assoc();
if (!$hw) {
    echo '<div class="text-center py-8 text-red-600 font-bold">Homework not found or access denied.</div>';
    exit;
}

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
    $file_path = $hw['file_path'];

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
        $stmt = $conn->prepare("UPDATE homework SET class_id=?, subject_id=?, title=?, description=?, due_date=?, file_path=? WHERE id=? AND teacher_id=?");
        $stmt->bind_param("iissssii", $class_id, $subject_id, $title, $description, $due_date, $file_path, $homework_id, $teacher_id);
        if ($stmt->execute()) {
            $success = 'Homework updated successfully!';
            header("Location: homework.php");
            exit;
        } else {
            $error = 'Error updating homework.';
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
    <title>Edit Homework - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Edit Homework</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="homework.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Homework
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-lg rounded-2xl p-6 max-w-2xl mx-auto">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Homework</h2>
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
                    <input type="text" name="title" value="<?= htmlspecialchars($hw['title']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                    <textarea name="description" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($hw['description']) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= $hw['class_id'] == $class['id'] ? 'selected' : '' ?>>
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
                            <option value="<?= $subject['id'] ?>" data-class="<?= $subject['class_id'] ?>" <?= $hw['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" value="<?= htmlspecialchars($hw['due_date']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (optional)</label>
                    <?php if ($hw['file_path']): ?>
                        <div class="mb-1 text-xs text-blue-700">
                            Current: <a href="../<?= htmlspecialchars($hw['file_path']) ?>" target="_blank" class="underline">Download</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="file" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end space-x-2">
                    <a href="homework.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Homework</button>
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