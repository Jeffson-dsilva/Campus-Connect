<?php
session_start();
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed"]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check for duplicate class code first
    $classCode = $conn->real_escape_string($_POST['class_code']);
    $checkSql = "SELECT class_code FROM classops_classes WHERE class_code = '$classCode'";
    $result = $conn->query($checkSql);
    
    if ($result->num_rows > 0) {
        echo json_encode(["success" => false, "error" => "Class code already exists"]);
        exit;
    }

    // Proceed with insertion if no duplicate
    $title = $conn->real_escape_string($_POST['class_name']);
    $section = $conn->real_escape_string($_POST['section']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $facultyEmpId = $conn->real_escape_string($_POST['faculty_empid']);
    $thumbnailUrl = $conn->real_escape_string($_POST['thumbnail_url']);
    $description = "$semester - $subject";

    $sql = "INSERT INTO classops_classes 
            (class_code, title, section, description, faculty_empid, thumbnail_url) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $classCode, $title, $section, $description, $facultyEmpId, $thumbnailUrl);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>