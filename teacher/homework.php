<?php
// ... existing PHP logic for fetching $homeworks, $teacher_classes, $teacher_subjects, $selected_class, $selected_subject ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Homework Assignments</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<main class="min-h-screen bg-gray-50 py-8 px-2 sm:px-4">
    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
            <h2 class="text-2xl font-bold text-gray-800">Homework Assignments</h2>
            <div class="flex gap-2 flex-wrap">
                <a href="add_homework.php" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Assign New Homework
                </a>
                <button onclick="window.print()" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow text-sm font-medium">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
        <div class="mb-6">
            <form method="get" class="flex flex-col sm:flex-row flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Class</label>
                    <select name="class_id" class="border rounded-lg px-3 py-2 w-48 focus:ring focus:ring-blue-200">
                        <option value="">All Classes</option>
                        <?php foreach($teacher_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo $class['class_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Subject</label>
                    <select name="subject_id" class="border rounded-lg px-3 py-2 w-48 focus:ring focus:ring-blue-200">
                        <option value="">All Subjects</option>
                        <?php foreach($teacher_subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                            <?php echo $subject['subject_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow text-sm font-medium h-10 flex items-center">
                    <i class="fas fa-filter mr-1"></i>Filter
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-xl shadow border custom-scrollbar">
                <thead class="bg-gray-100 sticky top-0 z-10">
                    <tr>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Title</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Class</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Subject</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Due Date</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Status</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Submissions</th>
                        <th class="py-3 px-4 border-b text-left text-xs font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($homeworks as $hw): ?>
                    <?php
                        $due = strtotime($hw['due_date']);
                        $now = strtotime(date('Y-m-d'));
                        $is_overdue = $due < $now;
                        $submitted = $hw['submitted_count'] ?? rand(0, 30); // Placeholder
                        $total = $hw['total_students'] ?? 30; // Placeholder
                        $is_completed = ($submitted >= $total && $total > 0);
                    ?>
                    <tr class="<?php echo $is_overdue && !$is_completed ? 'bg-red-50' : 'hover:bg-gray-50'; ?> transition">
                        <td class="py-2 px-4 border-b font-semibold text-gray-800 max-w-xs truncate">
                            <?php echo htmlspecialchars($hw['title']); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-gray-700"><?php echo htmlspecialchars($hw['class_name']); ?></td>
                        <td class="py-2 px-4 border-b text-gray-700"><?php echo htmlspecialchars($hw['subject_name']); ?></td>
                        <td class="py-2 px-4 border-b text-gray-700"><?php echo date('M d, Y', strtotime($hw['due_date'])); ?></td>
                        <td class="py-2 px-4 border-b">
                            <?php if ($is_completed): ?>
                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><i class="fas fa-check-circle mr-1"></i>Completed</span>
                            <?php elseif ($is_overdue): ?>
                                <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full"><i class="fas fa-exclamation-circle mr-1"></i>Overdue</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full"><i class="fas fa-hourglass-half mr-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b">
                            <span class="font-mono text-xs text-gray-700"><?php echo $submitted; ?>/<?php echo $total; ?></span>
                        </td>
                        <td class="py-2 px-4 border-b">
                            <div class="flex space-x-2">
                                <a href="view_homework.php?id=<?php echo $hw['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 hover:bg-blue-100 text-blue-600" title="View"><i class="fas fa-eye"></i></a>
                                <a href="edit_homework.php?id=<?php echo $hw['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-50 hover:bg-yellow-100 text-yellow-600" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="delete_homework.php?id=<?php echo $hw['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-600" title="Delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>