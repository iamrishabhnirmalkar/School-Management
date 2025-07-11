<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

// Validate POST data
if (!isset($_POST['exam_id'], $_POST['subject_id'], $_POST['exam_subject_id'], $_POST['marks'])) {
    die("Invalid form submission.");
}

$exam_id = intval($_POST['exam_id']);
$subject_id = intval($_POST['subject_id']);
$exam_subject_id = intval($_POST['exam_subject_id']);
$marks_data = $_POST['marks'];

$success = 0;
$fail = 0;

foreach ($marks_data as $student_id => $mark_info) {
    $student_id = intval($student_id);
    $marks = isset($mark_info['marks']) ? intval($mark_info['marks']) : null;
    $grade = $conn->real_escape_string(trim($mark_info['grade']));
    $remarks = $conn->real_escape_string(trim($mark_info['remarks']));

    // Check if entry already exists
    $check = $conn->query("
        SELECT id FROM exam_results 
        WHERE student_id = $student_id AND exam_subject_id = $exam_subject_id
        LIMIT 1
    ");

    if ($check->num_rows > 0) {
        // Update
        $update = $conn->query("
            UPDATE exam_results
            SET marks_obtained = $marks, grade = '$grade', remarks = '$remarks'
            WHERE student_id = $student_id AND exam_subject_id = $exam_subject_id
        ");
        if ($update) $success++; else $fail++;
    } else {
        // Insert
        $insert = $conn->query("
            INSERT INTO exam_results (student_id, exam_subject_id, marks_obtained, grade, remarks)
            VALUES ($student_id, $exam_subject_id, $marks, '$grade', '$remarks')
        ");
        if ($insert) $success++; else $fail++;
    }
}

$_SESSION['mark_upload_message'] = "$success marks saved successfully. $fail failed.";

header("Location: marks.php");
exit;
?>
