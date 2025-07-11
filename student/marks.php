<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get exam results
$results = [];
$result = $conn->query("SELECT e.exam_name, es.exam_date, s.subject_name, 
                        er.marks_obtained, es.max_marks, er.grade, er.remarks
                        FROM exam_results er
                        JOIN exam_subjects es ON er.exam_subject_id = es.id
                        JOIN examinations e ON es.exam_id = e.id
                        JOIN subjects s ON es.subject_id = s.id
                        WHERE er.student_id = $student_id
                        ORDER BY e.start_date DESC, es.exam_date DESC");
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}

// Get available documents (report cards)
$documents = [];
$result = $conn->query("SELECT id, title, issued_date 
                        FROM student_documents 
                        WHERE student_id = $student_id AND document_type = 'report_card'
                        ORDER BY issued_date DESC");
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Marks - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-green-200">Student Marks</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Panel
                </a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">My Examination Results</h1>
            
            <!-- Results Table -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Subject-wise Marks</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No results available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($result['exam_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($result['subject_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($result['exam_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $result['marks_obtained'] ?> / <?= $result['max_marks'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            <?= htmlspecialchars($result['grade']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($result['remarks'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report Cards -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Report Cards</h2>
                <?php if (empty($documents)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">No report cards available</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($documents as $doc): ?>
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                    <div>
                                        <h3 class="font-medium"><?= htmlspecialchars($doc['title']) ?></h3>
                                        <p class="text-sm text-gray-500">Issued: <?= date('M d, Y', strtotime($doc['issued_date'])) ?></p>
                                    </div>
                                </div>
                                <a href="download.php?id=<?= $doc['id'] ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>