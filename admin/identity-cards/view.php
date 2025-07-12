<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
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
        SELECT u.full_name, u.admission_number, u.phone, s.parent_name, s.dob, s.photo, s.address, c.class_name, c.section
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE u.id = $student_id AND u.role = 'student'
    ")->fetch_assoc();
    
    if (!$student) {
        die("Student not found");
    }
    
    $ename = $student['full_name'];
    $faname = $student['parent_name'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $dob = $student['dob'] ? date('d/m/Y', strtotime($student['dob'])) : '';
    $nid = $student['admission_number'];
    $sign = $_POST['si'] ?? '';
    $phone = $student['phone'] ?? '';
    $address = $student['address'] ?? '';
    $class_name = ($student['class_name'] ?? '') . ' ' . ($student['section'] ?? '');
    
    // Handle photo
    $targetPath = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // New photo uploaded
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
            die("Failed to upload image.");
        }
    } elseif (!empty($student['photo'])) {
        // Use existing student photo
        $targetPath = "../../" . $student['photo'];
    } else {
        die("No photo available for student.");
    }
} else {
    // Handle manual entry mode
    $ename = $_POST['ename'] ?? '';
    $faname = $_POST['faname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $dob = $_POST['dname'] ? date('d/m/Y', strtotime($_POST['dname'])) : '';
    $nid = $_POST['nid'] ?? '';
    $sign = $_POST['si'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $class_name = $_POST['class_name'] ?? '';

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
        die("Failed to upload image.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Student ID Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>

<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen px-4 py-10 space-y-6">

    <!-- ID Card Wrapper -->
    <div id="idCard" class="w-[300px] bg-white rounded-xl shadow-xl border border-gray-300 p-4 text-sm font-sans relative">

        <!-- Header -->
        <div class="text-center">
            <img src="../../assets/images/logo.png" alt="School Logo" class="mx-auto mb-2 w-16 h-16 object-contain" />
            <h1 class="text-pink-700 font-extrabold text-lg leading-5">MOTHER'S PRIDE SR. SEC. SCHOOL</h1>
            <p class="text-[11px] text-gray-700 font-medium">Affiliated to C.B.S.E. Board (CBSE-3300112)</p>
            <p class="text-[11px] text-gray-600">Khamhariya Tah-Patan, Durg (C.G.) 491111</p>
            <p class="text-[10px] text-gray-500">Tel: 0771-2241907 | Email: mpskhamhariya@gmail.com</p>
            <p class="text-red-600 font-semibold text-xs mt-1">Identity Card 2024 - 25</p>
            <p class="text-blue-700 font-semibold text-xs mt-1">Session: <?= htmlspecialchars($academic_year ?? '2024-25') ?></p>
        </div>

        <!-- Photo -->
        <div class="mt-4 flex justify-center">
            <div class="w-[100px] h-[120px] border-4 border-red-500 p-1 rounded-md shadow-md">
                <img src="<?= $targetPath ?>" alt="Student" class="w-full h-full object-cover rounded-sm" />
            </div>
        </div>

        <!-- Info -->
        <div class="mt-3 text-center">
            <p class="text-red-700 font-bold text-sm uppercase"><?= htmlspecialchars($ename) ?></p>
            <p class="text-black text-sm font-semibold">Class: <?= htmlspecialchars($class_name) ?></p>
            <p class="text-black text-sm font-semibold">DOB: <?= htmlspecialchars($dob) ?></p>
        </div>

        <div class="mt-3 text-[12px] leading-5 px-2">
            <p><span class="font-bold text-red-600">Student ID:</span> <?= htmlspecialchars($nid) ?></p>
            <p><span class="font-bold text-red-600">Father's Name:</span> <?= htmlspecialchars($faname) ?></p>
            <p><span class="font-bold text-red-600">Mother's Name:</span> <?= htmlspecialchars($mname) ?></p>
            <p><span class="font-bold text-red-600">Phone:</span> <?= htmlspecialchars($phone) ?></p>
            <p><span class="font-bold text-red-600">Address:</span> <?= htmlspecialchars($address) ?></p>
        </div>

        <!-- Signature -->
        <div class="text-right mt-4 px-2">
            <p class="text-blue-600 font-semibold text-sm"><?= htmlspecialchars($sign) ?></p>
            <div class="w-16 h-[1px] border-t border-blue-600 mt-[-2px]"></div>
            <p class="text-xs">Signature</p>
        </div>
    </div>

    <!-- Download Button -->
    <button onclick="downloadIDCard()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
        Download ID Card
    </button>

    <script>
        function downloadIDCard() {
            const card = document.getElementById("idCard");
            html2canvas(card).then(canvas => {
                const link = document.createElement("a");
                link.download = "Student-ID-Card.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        }
    </script>

</body>

</html>