<?php
// admin/classes/edit.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get class ID from URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$classId = $_GET['id'];

// Fetch class details
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    $_SESSION['error'] = "Class not found!";
    header("Location: index.php");
    exit;
}

// Fetch all teachers for the dropdown
$teachers = [];
$result = $conn->query("SELECT id, full_name FROM users WHERE role='teacher' ORDER BY full_name");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = trim($_POST['class_name']);
    $section = trim($_POST['section']);
    $classTeacherId = $_POST['class_teacher_id'] ?: null;

    // Validate
    if (empty($className)) {
        $_SESSION['error'] = "Class name is required!";
    } else {
        // Update database
        $stmt = $conn->prepare("UPDATE classes SET class_name = ?, section = ?, class_teacher_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $className, $section, $classTeacherId, $classId);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Class updated successfully!";
            header("Location: view.php?id=$classId");
            exit;
        } else {
            $_SESSION['error'] = "Error updating class: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <?php include '../header.php'; ?>

    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <?php include '../sidebar.php'; ?>

            <main class="flex-1">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Class</h1>
                    <a href="view.php?id=<?= $classId ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Class
                    </a>
                </div>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Class Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <form action="edit.php?id=<?= $classId ?>" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Class Name -->
                            <div>
                                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                                <input type="text" id="class_name" name="class_name" required
                                    value="<?= htmlspecialchars($class['class_name']) ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Section -->
                            <div>
                                <label for="section" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                <input type="text" id="section" name="section"
                                    value="<?= htmlspecialchars($class['section']) ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Class Teacher -->
                            <div>
                                <label for="class_teacher_id" class="block text-sm font-medium text-gray-700 mb-1">Class Teacher</label>
                                <select id="class_teacher_id" name="class_teacher_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= $teacher['id'] ?>" <?= ($teacher['id'] == $class['class_teacher_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($teacher['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-save mr-2"></i> Update Class
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <?php include '../footer.php'; ?>
</body>

</html>