<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$homework_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch homework details
$stmt = $conn->prepare("SELECT h.*, c.class_name, c.section, s.subject_name FROM homework h JOIN classes c ON h.class_id = c.id JOIN subjects s ON h.subject_id = s.id WHERE h.id = ? AND h.teacher_id = ?");
$stmt->bind_param("ii", $homework_id, $teacher_id);
$stmt->execute();
$hw = $stmt->get_result()->fetch_assoc();

if (!$hw) {
    echo '<div class="text-center py-8 text-red-600 font-bold">Homework not found or access denied.</div>';
    exit;
}

// Status logic
$due = strtotime($hw['due_date']);
$now = strtotime(date('Y-m-d'));
$is_overdue = $due < $now;
$submitted = $hw['submitted_count'] ?? rand(0, 30); // Placeholder
$total = $hw['total_students'] ?? 30; // Placeholder
$is_completed = ($submitted >= $total && $total > 0);

// Placeholder submissions list
$submissions = array_fill(0, $submitted, [
    'student_name' => 'Student Name',
    'file' => null
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homework Details - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Homework Details</p>
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
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($hw['title']) ?></h2>
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-print mr-1"></i>Print
                </button>
            </div>
            <div class="mb-4 flex gap-2 flex-wrap">
                <?php if ($is_completed): ?>
                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><i class="fas fa-check-circle mr-1"></i>Completed</span>
                <?php elseif ($is_overdue): ?>
                    <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full"><i class="fas fa-exclamation-circle mr-1"></i>Overdue</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full"><i class="fas fa-hourglass-half mr-1"></i>Pending</span>
                <?php endif; ?>
                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                    <i class="fas fa-users mr-1"></i><?= $submitted ?>/<?= $total ?> Submitted
                </span>
                <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                    <i class="fas fa-calendar mr-1"></i>Due: <?= date('M d, Y', strtotime($hw['due_date'])) ?>
                </span>
            </div>
            <div class="mb-4">
                <div class="text-sm text-gray-600 mb-1">
                    <span class="font-semibold">Class:</span> <?= htmlspecialchars($hw['class_name']) ?> <?= htmlspecialchars($hw['section']) ?>
                    &nbsp;|&nbsp;
                    <span class="font-semibold">Subject:</span> <?= htmlspecialchars($hw['subject_name']) ?>
                </div>
                <div class="text-sm text-gray-600 mb-1">
                    <span class="font-semibold">Assigned by:</span> <?= htmlspecialchars($_SESSION['user']['full_name']) ?>
                </div>
                <?php if ($hw['file_path']): ?>
                    <div class="text-sm text-gray-600 mb-1">
                        <span class="font-semibold">Attachment:</span>
                        <a href="../<?= htmlspecialchars($hw['file_path']) ?>" target="_blank" class="text-blue-600 hover:underline">Download</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">Description</h3>
                <div class="bg-gray-50 rounded p-3 text-gray-800 text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars($hw['description'])) ?></div>
            </div>
            <div class="mb-2">
                <h3 class="font-semibold text-gray-700 mb-2">Submissions <span class="text-xs text-gray-400">(placeholder)</span></h3>
                <?php if (empty($submissions)): ?>
                    <div class="text-gray-400 text-sm">No submissions yet.</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($submissions as $sub): ?>
                            <li class="py-2 flex items-center justify-between">
                                <span><?= htmlspecialchars($sub['student_name']) ?></span>
                                <?php if ($sub['file']): ?>
                                    <a href="<?= htmlspecialchars($sub['file']) ?>" class="text-blue-600 hover:underline text-xs" target="_blank">Download</a>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No file</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="mt-6 flex justify-end">
                <a href="homework.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Back to Homework</a>
            </div>
        </div>
    </main>
</body>
</html> 