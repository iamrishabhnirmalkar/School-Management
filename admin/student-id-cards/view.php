<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

$mode = $_POST['mode'] ?? 'manual';

if ($mode === 'student') {
    // Handle student selection mode
    $student_id = $_POST['student_id'] ?? 0;
    
    // Fetch student data from database
    $student = $conn->query("
        SELECT u.full_name, u.admission_number, u.phone, u.email, 
               s.parent_name, s.dob, s.photo, s.address, s.gender, s.blood_group, 
               s.roll_number, s.admission_date, c.class_name, c.section
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE u.id = $student_id AND u.role = 'student'
    ")->fetch_assoc();
    
    if (!$student) {
        die("Student not found");
    }
    
    $student_name = $student['full_name'];
    $admission_number = $student['admission_number'];
    $roll_number = $student['roll_number'] ?? '';
    $father_name = $student['parent_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $dob = $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : '';
    $gender = $student['gender'] ?? '';
    $blood_group = $student['blood_group'] ?? '';
    $phone = $student['phone'] ?? '';
    $email = $student['email'] ?? '';
    $address = $student['address'] ?? '';
    $class_name = ($student['class_name'] ?? '') . ' ' . ($student['section'] ?? '');
    $admission_date = $student['admission_date'] ? date('d/m/Y', strtotime($student['admission_date'])) : '';
    $signature = $_POST['signature'] ?? '';
    
    // Handle photo
    $targetPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // New photo uploaded
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . "_" . basename($_FILES["photo"]["name"]);
        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetPath)) {
            die("Failed to upload image.");
        }
    } elseif (!empty($student['photo'])) {
        // Use existing student photo
        $targetPath = "../../" . $student['photo'];
         } else {
         // Use default placeholder - create a simple SVG placeholder
         $targetPath = "data:image/svg+xml;base64," . base64_encode('
             <svg width="120" height="150" xmlns="http://www.w3.org/2000/svg">
                 <rect width="120" height="150" fill="#f3f4f6"/>
                 <circle cx="60" cy="60" r="25" fill="#d1d5db"/>
                 <rect x="35" y="95" width="50" height="40" fill="#d1d5db" rx="5"/>
                 <text x="60" y="140" text-anchor="middle" font-family="Arial" font-size="10" fill="#6b7280">No Photo</text>
             </svg>
         ');
     }
} else {
    // Handle manual entry mode
    $student_name = $_POST['student_name'] ?? '';
    $admission_number = $_POST['admission_number'] ?? '';
    $roll_number = $_POST['roll_number'] ?? '';
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $dob = $_POST['dob'] ? date('d/m/Y', strtotime($_POST['dob'])) : '';
    $gender = $_POST['gender'] ?? '';
    $blood_group = $_POST['blood_group'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $class_name = $_POST['class_name'] ?? '';
    $admission_date = $_POST['admission_date'] ? date('d/m/Y', strtotime($_POST['admission_date'])) : '';
    $signature = $_POST['signature'] ?? '';

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["photo"]["name"]);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetPath)) {
        die("Failed to upload image.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen px-4 py-10 space-y-6">

    <!-- ID Card Wrapper -->
    <div id="idCard" class="w-[350px] bg-white rounded-xl shadow-xl border border-gray-300 p-4 text-sm font-sans relative">

        <!-- Header -->
        <div class="text-center border-b-2 border-blue-600 pb-2 mb-4">
            <img src="../../assets/images/logo.png" alt="School Logo" class="mx-auto mb-2 w-16 h-16 object-contain" />
            <h1 class="text-blue-700 font-extrabold text-lg leading-5">MOTHER'S PRIDE SR. SEC. SCHOOL</h1>
            <p class="text-[11px] text-gray-700 font-medium">Affiliated to C.B.S.E. Board (CBSE-3300112)</p>
            <p class="text-[11px] text-gray-600">Khamhariya Tah-Patan, Durg (C.G.) 491111</p>
            <p class="text-[10px] text-gray-500">Tel: 0771-2241907 | Email: mpskhamhariya@gmail.com</p>
            <p class="text-blue-600 font-semibold text-xs mt-1">Student Identity Card 2024 - 25</p>
        </div>

        <!-- Photo and Basic Info -->
        <div class="flex mb-4">
            <div class="w-[120px] h-[150px] border-4 border-blue-500 p-1 rounded-md shadow-md mr-4">
                <img src="<?= $targetPath ?>" alt="Student" class="w-full h-full object-cover rounded-sm" />
            </div>
            <div class="flex-1">
                <div class="text-center mb-2">
                    <p class="text-blue-700 font-bold text-lg uppercase"><?= htmlspecialchars($student_name) ?></p>
                    <p class="text-black text-sm font-semibold">Class: <?= htmlspecialchars($class_name) ?></p>
                    <?php if (!empty($roll_number)): ?>
                        <p class="text-black text-xs">Roll No: <?= htmlspecialchars($roll_number) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-[11px] leading-4">
                    <p><span class="font-bold text-blue-600">ID:</span> <?= htmlspecialchars($admission_number) ?></p>
                    <p><span class="font-bold text-blue-600">DOB:</span> <?= htmlspecialchars($dob) ?></p>
                    <?php if (!empty($gender)): ?>
                        <p><span class="font-bold text-blue-600">Gender:</span> <?= htmlspecialchars($gender) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($blood_group)): ?>
                        <p><span class="font-bold text-blue-600">Blood:</span> <?= htmlspecialchars($blood_group) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="mb-3 text-[11px] leading-4">
            <p><span class="font-bold text-blue-600">Father's Name:</span> <?= htmlspecialchars($father_name) ?></p>
            <p><span class="font-bold text-blue-600">Mother's Name:</span> <?= htmlspecialchars($mother_name) ?></p>
            <?php if (!empty($admission_date)): ?>
                <p><span class="font-bold text-blue-600">Admission Date:</span> <?= htmlspecialchars($admission_date) ?></p>
            <?php endif; ?>
        </div>

        <!-- Contact Information -->
        <div class="mb-3 text-[11px] leading-4">
            <p><span class="font-bold text-blue-600">Phone:</span> <?= htmlspecialchars($phone) ?></p>
            <?php if (!empty($email)): ?>
                <p><span class="font-bold text-blue-600">Email:</span> <?= htmlspecialchars($email) ?></p>
            <?php endif; ?>
            <p><span class="font-bold text-blue-600">Address:</span> <?= htmlspecialchars($address) ?></p>
        </div>

        <!-- School Information -->
        <div class="mb-3 text-[11px] leading-4 bg-gray-50 p-2 rounded">
            <p class="font-bold text-blue-600 mb-1">School Information:</p>
            <p>• Valid for academic year 2024-2025</p>
            <p>• Must be carried daily to school</p>
            <p>• Report loss immediately to office</p>
        </div>

        <!-- Signature and Authority -->
        <div class="flex justify-between items-end mt-4 pt-2 border-t border-gray-300">
            <div>
                <p class="text-blue-600 font-semibold text-sm"><?= htmlspecialchars($signature) ?></p>
                <div class="w-16 h-[1px] border-t border-blue-600 mt-[-2px]"></div>
                <p class="text-xs text-gray-600">Student Signature</p>
            </div>
            <div class="text-right">
                <p class="text-blue-600 font-semibold text-sm">Principal</p>
                <div class="w-16 h-[1px] border-t border-blue-600 mt-[-2px]"></div>
                <p class="text-xs text-gray-600">School Authority</p>
            </div>
        </div>

        <!-- QR Code Placeholder -->
        <div class="absolute top-2 right-2 w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
            <i class="fas fa-qrcode text-gray-500 text-xs"></i>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex space-x-4">
        <button onclick="downloadIDCard()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow flex items-center">
            <i class="fas fa-download mr-2"></i>Download ID Card
        </button>
        <button onclick="printIDCard()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow flex items-center">
            <i class="fas fa-print mr-2"></i>Print ID Card
        </button>
        <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg shadow flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Back to Generator
        </a>
    </div>

    <script>
        function downloadIDCard() {
            const card = document.getElementById("idCard");
            html2canvas(card, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                const link = document.createElement("a");
                link.download = "Student-ID-Card-<?= htmlspecialchars($admission_number) ?>.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        }

        function printIDCard() {
            const card = document.getElementById("idCard");
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Student ID Card</title>
                        <script src="https://cdn.tailwindcss.com"></script>
                        <style>
                            @media print {
                                body { margin: 0; }
                                .print-card { 
                                    width: 350px; 
                                    margin: 20px auto;
                                    page-break-inside: avoid;
                                }
                            }
                        </style>
                    </head>
                    <body class="bg-white">
                        <div class="print-card">
                            ${card.outerHTML}
                        </div>
                        <script>
                            window.onload = function() {
                                window.print();
                                window.close();
                            }
                        </script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>

</html> 