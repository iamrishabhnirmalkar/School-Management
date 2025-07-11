<?php
session_start();
require_once '../config.php';

// Validate GET params
if (!isset($_GET['student_id'], $_GET['exam_id'])) {
    die("Invalid access.");
}

$student_id = intval($_GET['student_id']);
$exam_id = intval($_GET['exam_id']);

// Fetch student details
$student = $conn->query("
    SELECT u.full_name, s.roll_number, c.class_name, c.section
    FROM users u
    JOIN students s ON u.id = s.user_id
    JOIN classes c ON s.class_id = c.id
    WHERE u.id = $student_id
")->fetch_assoc();

// Fetch exam info
$exam = $conn->query("SELECT exam_name FROM examinations WHERE id = $exam_id")->fetch_assoc();

// Fetch marks for all subjects of this exam
$results = $conn->query("
    SELECT sub.subject_name, er.marks_obtained, er.grade, er.remarks
    FROM exam_results er
    JOIN exam_subjects es ON er.exam_subject_id = es.id
    JOIN subjects sub ON es.subject_id = sub.id
    WHERE er.student_id = $student_id AND es.exam_id = $exam_id
");

// Calculate total and count
$total_marks = 0;
$subject_count = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Card - <?= $student['full_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-white text-black p-6 max-w-4xl mx-auto">

    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold mb-2">📄 Report Card</h1>
        <p class="text-gray-700"><?= $exam['exam_name'] ?></p>
    </div>

    <div class="mb-6 border p-4 rounded">
        <p><strong>Student Name:</strong> <?= $student['full_name'] ?></p>
        <p><strong>Roll Number:</strong> <?= $student['roll_number'] ?></p>
        <p><strong>Class:</strong> <?= $student['class_name'] ?> - <?= $student['section'] ?></p>
    </div>

    <table class="w-full border border-gray-400 text-left">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Subject</th>
                <th class="p-2 border">Marks</th>
                <th class="p-2 border">Grade</th>
                <th class="p-2 border">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td class="p-2 border"><?= $row['subject_name'] ?></td>
                    <td class="p-2 border"><?= $row['marks_obtained'] ?></td>
                    <td class="p-2 border"><?= $row['grade'] ?></td>
                    <td class="p-2 border"><?= $row['remarks'] ?></td>
                </tr>
                <?php
                    $total_marks += $row['marks_obtained'];
                    $subject_count++;
                ?>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($subject_count > 0): ?>
        <div class="mt-4">
            <p><strong>Total Marks:</strong> <?= $total_marks ?></p>
            <p><strong>Percentage:</strong> <?= round(($total_marks / ($subject_count * 100)) * 100, 2) ?>%</p>
        </div>
    <?php else: ?>
        <p class="mt-4 text-red-500">No marks available.</p>
    <?php endif; ?>

    <div class="mt-6 text-center no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
            🖨️ Print / Download
        </button>
    </div>

</body>
</html>
