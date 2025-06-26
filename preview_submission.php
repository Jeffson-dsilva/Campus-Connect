<?php
require_once 'auth_check.php';
$auth = verifySession();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("Invalid submission ID.");

$conn = new mysqli("localhost", "root", "", "college_ipm_system", $port=3307);
$stmt = $conn->prepare("SELECT submission_file_name, submission_file_type, submission_file_data FROM classops_submissions WHERE submission_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) die("File not found.");
$stmt->bind_result($name, $type, $data);
$stmt->fetch();

header("Content-Type: $type");
header("Content-Disposition: inline; filename=\"" . basename($name) . "\"");
echo $data;
$stmt->close();
$conn->close();
exit;
?>
