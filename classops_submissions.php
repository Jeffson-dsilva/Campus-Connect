<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
ob_start();

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
    
    if ($action === 'grade') {
        // Handle grading by faculty
        if ($auth['type'] !== 'faculty') {
            throw new Exception("Only faculty can grade submissions");
        }

        $submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
        $grade = $_POST['grade'] ?? '';
        $feedback = $_POST['feedback'] ?? ''; // This will be saved to 'remarks' column
        
        if (!$submissionId) {
            throw new Exception("Invalid submission ID");
        }
        
        // Verify faculty owns this submission (either created the assignment or is class faculty)
        $checkSql = "SELECT s.submission_id 
                     FROM classops_submissions s
                     JOIN classops_posts p ON s.post_id = p.post_id
                     JOIN classops_classes c ON p.class_id = c.class_id
                     WHERE s.submission_id = ? 
                     AND (p.faculty_empid = ? OR c.faculty_empid = ?)";
        
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("iss", $submissionId, $auth['id'], $auth['id']);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            throw new Exception("You don't have permission to grade this submission");
        }
        $checkStmt->close();
        
        // Update grade and feedback (remarks)
        $sql = "UPDATE classops_submissions 
                SET grade = ?, remarks = ? 
                WHERE submission_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $grade, $feedback, $submissionId);
        
        if ($stmt->execute()) {
            $response["success"] = true;
            $response["message"] = "Grade updated successfully";
        } else {
            throw new Exception("Failed to update grade: " . $stmt->error);
        }
        $stmt->close();
        
    } elseif ($action === 'submit' && $auth['type'] === 'student') {
        // Handle assignment submission by student
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $remarks = $_POST['remarks'] ?? '';
        
        if (!$postId) {
            throw new Exception("Invalid post ID");
        }
        
        // Verify student is enrolled in this class
        $checkSql = "SELECT 1 
                     FROM classops_enrollments e
                     JOIN classops_posts p ON e.class_id = p.class_id
                     WHERE p.post_id = ? AND e.student_usn = ?";
        
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("is", $postId, $auth['id']);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            throw new Exception("You are not enrolled in this class or the assignment doesn't exist");
        }
        $checkStmt->close();
        
        // Handle file upload if present
        $fileName = null;
        $fileType = null;
        $fileData = null;
        
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['submission_file'];
            $fileName = $conn->real_escape_string($file['name']);
            $fileType = $conn->real_escape_string($file['type']);
            $fileData = file_get_contents($file['tmp_name']);
        }
        
        // Check if submission already exists
        $checkSubSql = "SELECT submission_id FROM classops_submissions 
                        WHERE post_id = ? AND student_usn = ?";
        $checkSubStmt = $conn->prepare($checkSubSql);
        $checkSubStmt->bind_param("is", $postId, $auth['id']);
        $checkSubStmt->execute();
        $checkSubStmt->store_result();
        
        if ($checkSubStmt->num_rows > 0) {
            // Update existing submission
            $sql = "UPDATE classops_submissions 
                    SET submitted_at = CURRENT_TIMESTAMP(),
                        submission_file_name = ?,
                        submission_file_type = ?,
                        submission_file_data = ?,
                        remarks = ?
                    WHERE post_id = ? AND student_usn = ?";
            
            $stmt = $conn->prepare($sql);
            $null = null;
            $stmt->bind_param("ssbss", $fileName, $fileType, $null, $remarks, $postId, $auth['id']);
            if ($fileData) {
                $stmt->send_long_data(2, $fileData);
            }
        } else {
            // Create new submission
            $sql = "INSERT INTO classops_submissions 
                    (post_id, student_usn, submission_file_name, 
                     submission_file_type, submission_file_data, remarks)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $null = null;
            $stmt->bind_param("isssbs", $postId, $auth['id'], $fileName, $fileType, $null, $remarks);
            if ($fileData) {
                $stmt->send_long_data(4, $fileData);
            }
        }
        
        if ($stmt->execute()) {
            $response["success"] = true;
            $response["message"] = "Assignment submitted successfully";
        } else {
            throw new Exception("Failed to submit assignment: " . $stmt->error);
        }
        $stmt->close();
        
     } else {
        throw new Exception("Invalid action or unauthorized access");
    }
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

$conn->close();
ob_end_clean(); // Clean any accidental output
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>