<?php
// ... existing PHP logic for fetching $homeworks, $teacher_classes, $teacher_subjects, $selected_class, $selected_subject ...
?>
<!-- Header same as dashboard -->
<main class="container mx-auto px-6 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Homework Assignments</h2>
            <div class="flex gap-2">
                <a href="add_homework.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Assign New Homework
                </a>
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
        <div class="mb-6">
            <form method="get" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Class</label>
                    <select name="class_id" class="border rounded px-3 py-2 w-48">
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
                    <select name="subject_id" class="border rounded px-3 py-2 w-48">
                        <option value="">All Subjects</option>
                        <?php foreach($teacher_subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                            <?php echo $subject['subject_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded h-10">
                    <i class="fas fa-filter mr-1"></i>Filter
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 border">Title</th>
                        <th class="py-2 px-4 border">Class</th>
                        <th class="py-2 px-4 border">Subject</th>
                        <th class="py-2 px-4 border">Due Date</th>
                        <th class="py-2 px-4 border">Status</th>
                        <th class="py-2 px-4 border">Submissions</th>
                        <th class="py-2 px-4 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($homeworks as $hw): ?>
                    <?php
                        $due = strtotime($hw['due_date']);
                        $now = strtotime(date('Y-m-d'));
                        $is_overdue = $due < $now;
                        // Example: fetch submission count (replace with real query if available)
                        $submitted = $hw['submitted_count'] ?? rand(0, 30); // Placeholder
                        $total = $hw['total_students'] ?? 30; // Placeholder
                        $is_completed = ($submitted >= $total && $total > 0);
                    ?>
                    <tr class="<?php echo $is_overdue && !$is_completed ? 'bg-red-50' : ''; ?>">
                        <td class="py-2 px-4 border font-semibold"><?php echo htmlspecialchars($hw['title']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($hw['class_name']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($hw['subject_name']); ?></td>
                        <td class="py-2 px-4 border"><?php echo date('M d, Y', strtotime($hw['due_date'])); ?></td>
                        <td class="py-2 px-4 border">
                            <?php if ($is_completed): ?>
                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><i class="fas fa-check-circle mr-1"></i>Completed</span>
                            <?php elseif ($is_overdue): ?>
                                <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full"><i class="fas fa-exclamation-circle mr-1"></i>Overdue</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full"><i class="fas fa-hourglass-half mr-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border">
                            <span class="font-mono text-xs"><?php echo $submitted; ?>/<?php echo $total; ?></span>
                        </td>
                        <td class="py-2 px-4 border">
                            <div class="flex space-x-2">
                                <a href="view_homework.php?id=<?php echo $hw['id']; ?>" class="text-blue-500 hover:underline">View</a>
                                <a href="edit_homework.php?id=<?php echo $hw['id']; ?>" class="text-yellow-500 hover:underline">Edit</a>
                                <a href="delete_homework.php?id=<?php echo $hw['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>