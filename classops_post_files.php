<?php
session_start();
header('Content-Type: application/json');
require_once 'auth_check.php';
$auth = verifySession();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    die(json_encode(["success" => false, "error" => "Database connection failed"]));
}
$response = ["success" => false];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    
    if (!$postId) {
        throw new Exception("Invalid post ID");
    }

    // Verify user has permission (faculty who created post or enrolled student)
    $checkSql = "SELECT p.faculty_empid 
                 FROM classops_posts p
                 LEFT JOIN classops_enrollments e ON p.class_id = e.class_id 
                 WHERE p.post_id = ? AND (p.faculty_empid = ? OR e.student_usn = ?)";
    
    $checkStmt = $conn->prepare($checkSql);
    $userId = $auth['type'] === 'faculty' ? $auth['id'] : $auth['usn'];
    $checkStmt->bind_param("iss", $postId, $auth['id'], $userId);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        throw new Exception("You don't have permission to modify files for this post");
    }
    $checkStmt->close();

    if ($action === 'upload' && isset($_FILES['file'])) {
        // File upload configuration
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain'
        ];

        $file = $_FILES['file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }
        
        if ($file['size'] > $maxFileSize) {
            throw new Exception("File size exceeds maximum limit of 5MB");
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("File type not allowed");
        }
        
        // Check if file was actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Possible file upload attack");
        }

        // Prepare file data
        $fileName = $conn->real_escape_string($file['name']);
        $fileType = $conn->real_escape_string($file['type']);
        $fileData = file_get_contents($file['tmp_name']);
        
        if ($fileData === false) {
            throw new Exception("Failed to read file contents");
        }

        // Start transaction in case we need to roll back
        $conn->begin_transaction();
        
        try {
            $sql = "INSERT INTO classops_post_files 
                    (post_id, file_name, file_type, file_data) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $null = null;
            $stmt->bind_param("issb", $postId, $fileName, $fileType, $null);
            $stmt->send_long_data(3, $fileData);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to upload file: " . $stmt->error);
            }
            
            $response["success"] = true;
            $response["file_id"] = $stmt->insert_id;
            
            $conn->commit();
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } elseif ($action === 'delete') {
        $fileId = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
        if (!$fileId) {
            throw new Exception("Invalid file ID");
        }
        
        // Only faculty can delete files
        if ($auth['type'] !== 'faculty') {
            throw new Exception("Only faculty can delete files");
        }
        
        $sql = "DELETE FROM classops_post_files WHERE file_id = ? AND post_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $fileId, $postId);
        
        if ($stmt->execute()) {
            $response["success"] = true;
        } else {
            throw new Exception("Failed to delete file: " . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>