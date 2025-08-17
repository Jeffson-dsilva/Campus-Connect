<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307; // Specify the port number

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$html = "<h1>Student Records</h1><table border='1'><tr><th>Name</th><th>USN</th><th>Email</th><th>Password</th></tr>";

$query = "SELECT * FROM students ORDER BY usn ASC";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $html .= "<tr><td>{$row['name']}</td><td>{$row['usn']}</td><td>{$row['email']}</td><td>{$row['password']}</td></tr>";
}

$html .= "</table>";

$conn->close();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Student_Records.pdf");
?>
