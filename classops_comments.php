<?php
session_start();
header('Content-Type: application/json');
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
    die(json_encode(["success" => false, "error" => "Database connection failed"]));
}

$response = ["success" => false];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $action = $_POST['action'] ?? '';
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $commentText = $_POST['comment_text'] ?? '';
    
    if (!$postId || empty($commentText)) {
        throw new Exception("Invalid parameters");
    }

    // Verify user has permission to comment (either enrolled student or faculty)
    $checkSql = "SELECT 1 FROM classops_posts p
                 LEFT JOIN classops_enrollments e ON p.class_id = e.class_id
                 WHERE p.post_id = ? AND 
                 (e.student_usn = ? OR p.faculty_empid = ?)";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iss", $postId, $auth['id'], $auth['id']);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        throw new Exception("You don't have permission to comment on this post");
    }
    $checkStmt->close();

    if ($action === 'create') {
        // Create new comment
        $sql = "INSERT INTO classops_comments 
                (post_id, commenter_type, commenter_id, comment_text) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $commenterType = $auth['type'] === 'faculty' ? 'faculty' : 'student';
        $stmt->bind_param("isss", $postId, $commenterType, $auth['id'], $commentText);
        
        if ($stmt->execute()) {
            $response["success"] = true;
            $response["comment_id"] = $stmt->insert_id;
        } else {
            throw new Exception("Failed to create comment: " . $stmt->error);
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