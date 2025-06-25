<?php
session_start();
require_once 'auth_check.php';
$auth = verifySession();

// Database connection
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname' => 'college_ipm_system',
    'port' => 3307
];

$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);
if ($conn->connect_error) {
    die("Database connection failed");
}

$fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
if (!$fileId) {
    header("HTTP/1.0 400 Bad Request");
    exit("Invalid file ID");
}

// Get file info
$stmt = $conn->prepare("
    SELECT pf.file_name, pf.file_type, pf.file_data, p.post_id, p.class_id, p.faculty_empid
    FROM classops_post_files pf
    JOIN classops_posts p ON pf.post_id = p.post_id
    WHERE pf.file_id = ?
");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    header("HTTP/1.0 404 Not Found");
    exit("File not found");
}

// Verify user has access to this file (faculty who created post or enrolled student)
$checkSql = "SELECT 1 
             FROM classops_posts p
             LEFT JOIN classops_enrollments e ON p.class_id = e.class_id 
             WHERE p.post_id = ? AND (p.faculty_empid = ? OR e.student_usn = ?)";
$checkStmt = $conn->prepare($checkSql);
$userId = $auth['type'] === 'faculty' ? $auth['id'] : $auth['id'];
$checkStmt->bind_param("iss", $file['post_id'], $auth['id'], $userId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    header("HTTP/1.0 403 Forbidden");
    exit("You don't have permission to access this file");
}
$checkStmt->close();
$conn->close();

// Send file to browser
header("Content-Type: " . $file['file_type']);
header("Content-Disposition: attachment; filename=\"" . $file['file_name'] . "\"");
echo $file['file_data'];
exit;
?>