<?php
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

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Fetch the student data from the database
$query = "SELECT Name, USN, Email, Password FROM students ORDER BY USN ASC";
$result = mysqli_query($conn, $query);

// Check if data is available
if ($result && mysqli_num_rows($result) > 0) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $students = [];
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="display.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <title>Student Data</title>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="College Logo" class="logo-img">
            <div class="logo-text">
                <h1>St. Joseph Engineering College</h1><br>
                <p>Vamanjoor, Mangalore - 575028</p>
            </div>
        </div>
    </header>
    
    <div class="heading">STUDENT DATA</div>
    

    <main id="mainContent">
        <div class="section">
            <div class="box">
                <table id="dataTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>USN</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Loop through the fetched data and display it in the table
                        foreach ($students as $student) {
                            echo "<tr>
                                    <td>{$student['Name']}</td>
                                    <td>{$student['USN']}</td>
                                    <td>{$student['Email']}</td>
                                    <td>{$student['Password']}</td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div class="actions">
                    <button class="print-button" onclick="printTable()">Print to PDF</button>
                    <button class="back-to-menu" onclick="window.location.href='uploadFile.php'">Back to Menu</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Function to print the table as PDF using jsPDF
        function printTable() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Get the table element
            const table = document.getElementById('dataTable');
            
            // Extract table header (headings)
            const headers = [];
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach(cell => headers.push(cell.innerText));

            // Extract table body data (rows)
            const tableRows = table.querySelectorAll('tbody tr');
            const tableData = [];
            tableRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => rowData.push(cell.innerText));
                tableData.push(rowData);
            });

            // Add the table header and body to the PDF
            doc.autoTable({
                head: [headers], // The header (column names)
                body: tableData, // The actual data rows
            });

            // Save the PDF file
            doc.save('student-data.pdf');
        }
    </script>
</body>
</html>
