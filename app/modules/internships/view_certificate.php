<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['usn'])) {
    $usn = $conn->real_escape_string($_GET['usn']);

    // Query to fetch the certificate binary data for the selected USN
    $sql = "SELECT certificate FROM internship WHERE usn = '$usn'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $certificate_data = $row['certificate'];

        if ($certificate_data) {
            // Detect MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->buffer($certificate_data);

            // Set headers based on MIME type
            switch ($mime_type) {
                case 'application/pdf':
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="certificate.pdf"');
                    break;
                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    header('Content-Type: ' . $mime_type);
                    header('Content-Disposition: inline; filename="certificate.' . pathinfo($mime_type, PATHINFO_EXTENSION) . '"');
                    break;
                default:
                    echo 'Unsupported certificate format.';
                    exit;
            }

            // Output the certificate
            echo $certificate_data;
        } else {
            echo 'No certificate found.';
        }
    } else {
        echo 'No record found for this USN.';
    }
} else {
    echo 'Invalid request. No USN provided.';
}

$conn->close();
?>
