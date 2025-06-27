<?php
require_once 'config.php';
require_once 'hodheader.php';

// Get HOD's department from session
$hodDept = $_SESSION['dept'];

// Fetch student data only from HOD's department
$query = "SELECT Name, USN, Email, Password, dept_code 
          FROM students 
          WHERE dept_code = ?
          ORDER BY USN ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hodDept);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $students = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Data - <?= htmlspecialchars($hodDept) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table th {
            background-color: #3b82f6;
            color: white;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: #f9fafb;
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        .dept-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-user-graduate mr-2 text-blue-600"></i>
                            Student Records
                        </h1>
                        <div class="flex items-center mt-1">
                            <span class="text-sm text-gray-500 mr-2">Department:</span>
                            <span class="dept-badge"><?= htmlspecialchars($hodDept) ?></span>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="printTable()" 
                                class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-file-pdf mr-2"></i> Export PDF
                        </button>
                        <a href="uploadFile.php" 
                           class="action-btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </a>
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No student records found in <?= htmlspecialchars($hodDept) ?> department</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table id="dataTable" class="data-table w-full">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>USN</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="font-medium"><?= htmlspecialchars($student['Name']) ?></td>
                                        <td><?= htmlspecialchars($student['USN']) ?></td>
                                        <td><?= htmlspecialchars($student['Email']) ?></td>
                                        <td>
                                            <span class="dept-badge"><?= htmlspecialchars($student['dept_code']) ?></span>
                                        </td>
                                        <td class="text-gray-500"><?= htmlspecialchars($student['Password']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function printTable() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape'
            });

            // Title
            doc.setFontSize(18);
            doc.text('Student Records - <?= htmlspecialchars($hodDept) ?> Department', 14, 15);
            doc.setFontSize(12);
            doc.setTextColor(100);
            doc.text(new Date().toLocaleDateString(), 14, 22);

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

            // Add the table to the PDF
            doc.autoTable({
                head: [headers],
                body: tableData,
                startY: 30,
                styles: {
                    cellPadding: 5,
                    fontSize: 10,
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: [59, 130, 246],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [243, 244, 246]
                }
            });

            // Save the PDF file
            doc.save('student-records-<?= strtolower($hodDept) ?>-' + new Date().toISOString().slice(0,10) + '.pdf');
        }
    </script>
</body>
</html>