<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get all documents
$documents = [];
$result = $conn->query("SELECT id, title, document_type, issued_date, file_path 
                        FROM student_documents 
                        WHERE student_id = $student_id
                        ORDER BY issued_date DESC");
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

// Group documents by type
$grouped_documents = [
    'report_card' => [],
    'fee_receipt' => [],
    'other' => []
];

foreach ($documents as $doc) {
    $grouped_documents[$doc['document_type']][] = $doc;
}

// Document type labels
$type_labels = [
    'report_card' => 'Report Cards',
    'fee_receipt' => 'Fee Receipts',
    'other' => 'Other Documents'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - School ERP</title>
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
            <h1 class="text-2xl font-bold mb-6 text-gray-800">My Documents</h1>
            
            <?php if (empty($documents)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">No documents available</p>
                </div>
            <?php else: ?>
                <!-- Document Type Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button data-tab="all" class="tab-button border-b-2 border-green-500 text-green-600 px-4 py-2 text-sm font-medium">
                            All Documents
                        </button>
                        <?php foreach ($grouped_documents as $type => $docs): ?>
                            <?php if (!empty($docs)): ?>
                                <button data-tab="<?= $type ?>" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 px-4 py-2 text-sm font-medium">
                                    <?= $type_labels[$type] ?>
                                    <span class="ml-1 bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                        <?= count($docs) ?>
                                    </span>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- All Documents Tab -->
                <div id="all-tab" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($documents as $doc): ?>
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition document-item" data-type="<?= $doc['document_type'] ?>">
                                <div class="flex items-start mb-3">
                                    <?php if ($doc['document_type'] === 'report_card'): ?>
                                        <i class="fas fa-file-pdf text-red-500 text-3xl mr-3"></i>
                                    <?php elseif ($doc['document_type'] === 'fee_receipt'): ?>
                                        <i class="fas fa-file-invoice-dollar text-green-500 text-3xl mr-3"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-alt text-blue-500 text-3xl mr-3"></i>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?= htmlspecialchars($doc['title']) ?></h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">Issued: <?= date('M d, Y', strtotime($doc['issued_date'])) ?></p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <a href="../uploads/<?= htmlspecialchars($doc['file_path']) ?>" 
                                       class="text-blue-600 hover:text-blue-800 text-sm"
                                       download>
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                    <span class="text-xs text-gray-500">
                                        <?= pathinfo($doc['file_path'], PATHINFO_EXTENSION) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Document Type Tabs -->
                <?php foreach ($grouped_documents as $type => $docs): ?>
                    <?php if (!empty($docs)): ?>
                        <div id="<?= $type ?>-tab" class="tab-content hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($docs as $doc): ?>
                                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                        <div class="flex items-start mb-3">
                                            <?php if ($type === 'report_card'): ?>
                                                <i class="fas fa-file-pdf text-red-500 text-3xl mr-3"></i>
                                            <?php elseif ($type === 'fee_receipt'): ?>
                                                <i class="fas fa-file-invoice-dollar text-green-500 text-3xl mr-3"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file-alt text-blue-500 text-3xl mr-3"></i>
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($doc['title']) ?></h3>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?>
                                                </p>
                                                <p class="text-xs text-gray-500">Issued: <?= date('M d, Y', strtotime($doc['issued_date'])) ?></p>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <a href="../uploads/<?= htmlspecialchars($doc['file_path']) ?>" 
                                               class="text-blue-600 hover:text-blue-800 text-sm"
                                               download>
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                            <span class="text-xs text-gray-500">
                                                <?= pathinfo($doc['file_path'], PATHINFO_EXTENSION) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                // Update active tab styling
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-green-500', 'text-green-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                this.classList.add('border-green-500', 'text-green-600');
                this.classList.remove('border-transparent', 'text-gray-500');

                // Show selected tab content
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                if (tabId === 'all') {
                    document.getElementById('all-tab').classList.remove('hidden');
                } else {
                    document.getElementById(`${tabId}-tab`).classList.remove('hidden');
                }
            });
        });

        // Filter documents in "All" tab when clicking type buttons
        document.querySelectorAll('[data-tab]').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('data-tab');
                if (type === 'all') return;
                
                document.querySelectorAll('.document-item').forEach(item => {
                    if (item.getAttribute('data-type') === type) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>