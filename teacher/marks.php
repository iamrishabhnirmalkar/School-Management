<?php
session_start();
require_once '../config.php';

// Check if teacher is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$message = "";
$exam_id = $_POST['exam_id'] ?? null;

// Fetch all exams
$exams = $conn->query("SELECT id, exam_name FROM examinations ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Marks - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-blue-200">Marks Panel</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto p-6">
        <h2 class="text-xl font-bold mb-4">📊 Enter & Edit Student Marks</h2>

        <?= $message ?>

        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="exam_id" class="block font-medium mb-1">Select Exam</label>
                <select name="exam_id" class="w-full p-2 border rounded" required onchange="this.form.submit()">
                    <option value="">-- Select Exam --</option>
                    <?php while ($exam = $exams->fetch_assoc()): ?>
                        <option value="<?= $exam['id'] ?>" <?= ($exam_id == $exam['id']) ? 'selected' : '' ?>>
                            <?= $exam['exam_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <?php if ($exam_id): ?>
            <?php
            $subject_query = $conn->query("SELECT s.id AS subject_id, s.subject_name, c.id AS class_id, c.class_name, c.section FROM subjects s JOIN classes c ON s.class_id = c.id WHERE s.teacher_id = $teacher_id");
            ?>

            <?php while ($sub = $subject_query->fetch_assoc()): ?>
                <?php
                $subject_id = $sub['subject_id'];
                $class_id = $sub['class_id'];
                $exam_subject_result = $conn->query("SELECT id FROM exam_subjects WHERE exam_id = $exam_id AND subject_id = $subject_id LIMIT 1");
                if ($exam_subject_result->num_rows === 0) continue;
                $exam_subject_id = $exam_subject_result->fetch_assoc()['id'];

                $students = $conn->query("SELECT u.id AS student_id, u.full_name, s.roll_number FROM users u JOIN students s ON u.id = s.user_id WHERE s.class_id = $class_id ORDER BY s.roll_number ASC");
                ?>

                <h4 class="mt-5 font-semibold text-lg">Subject: <?= $sub['subject_name'] ?> | Class: <?= $sub['class_name'] ?> <?= $sub['section'] ?></h4>

                <form method="POST" action="save_marks.php" class="mb-6">
                    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    <input type="hidden" name="exam_subject_id" value="<?= $exam_subject_id ?>">

                    <table class="w-full table-auto bg-white shadow rounded mt-3">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="p-2">Roll No.</th>
                                <th class="p-2">Name</th>
                                <th class="p-2">Marks</th>
                                <th class="p-2">Grade</th>
                                <th class="p-2">Remarks</th>
                                <th class="p-2">Report</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($student = $students->fetch_assoc()):
                            $sid = $student['student_id'];
                            $marks_result = $conn->query("SELECT marks_obtained, grade, remarks FROM exam_results WHERE exam_subject_id = $exam_subject_id AND student_id = $sid");
                            $existing = ($marks_result->num_rows > 0) ? $marks_result->fetch_assoc() : ['marks_obtained' => '', 'grade' => '', 'remarks' => ''];
                        ?>
                            <tr class="border-t">
                                <td class="p-2"><?= $student['roll_number'] ?></td>
                                <td class="p-2"><?= $student['full_name'] ?></td>
                                <td class="p-2"><input type="number" step="0.01" name="marks[<?= $sid ?>][marks]" value="<?= $existing['marks_obtained'] ?>" class="border p-1 rounded w-24"></td>
                                <td class="p-2"><input type="text" name="marks[<?= $sid ?>][grade]" value="<?= $existing['grade'] ?>" class="border p-1 rounded w-20"></td>
                                <td class="p-2"><input type="text" name="marks[<?= $sid ?>][remarks]" value="<?= $existing['remarks'] ?>" class="border p-1 rounded"></td>
                                <td class="p-2">
                                    <a href="generate_report.php?student_id=<?= $sid ?>&exam_id=<?= $exam_id ?>" target="_blank" class="text-blue-600 underline">Download</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Marks</button>
                </form>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</body>
</html>
