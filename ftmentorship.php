
<?php
// Set the title for the page
$title = 'Mentorship Records';

// Database connection
require_once 'config.php';

// Check if any data was sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_data'])) {
        // Store the selected data in session
        $_SESSION['selected_mentorship_data'] = $_POST['selected_data'];
    }
    
    // Redirect to prevent form resubmission
    header("Location: ftmentorship.php");
    exit();
}

// Fetch data from session if available
$selected_data = $_SESSION['selected_mentorship_data'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Additional custom styles */
        .table-container {
            overflow-x: auto;
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Make table rows slightly taller for better readability */
        .table-row {
            height: 3.5rem;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400">
    <?php require_once 'ftheader.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
       <div class="bg-white rounded-xl shadow-md overflow-hidden p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Selected Mentorship Records</h1>
                    <p class="text-gray-600 mt-2">View details of student mentorship activities</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" placeholder="Search by USN or name..." 
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-64"
                            id="searchInput">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-600">
                        <!-- [Table headers remain the same] -->
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="mentorshipTableBody">
                        <?php
                        if (!empty($selected_data)) {
                            foreach ($selected_data as $data) {
                                list($usn, $name) = explode('|', $data);
                                echo "<tr class='hover:bg-gray-50 table-row'>
                                        <td class='px-6 py-4 whitespace-nowrap'>
                                            <a href='mentorshipdetails.php?usn=" . urlencode($usn) . "' class='text-blue-600 hover:text-blue-800 hover:underline font-medium'>
                                                " . htmlspecialchars($usn) . "
                                            </a>
                                        </td>
                                        <td class='px-6 py-4 whitespace-nowrap text-gray-700'>
                                            " . htmlspecialchars($name) . "
                                        </td>
                                        <td class='px-6 py-4 whitespace-nowrap'>
                                            <a href='mentorshipdetails.php?usn=" . urlencode($usn) . "' 
                                                class='action-btn inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                                <i class='fas fa-eye mr-1'></i> View Details
                                            </a>
                                        </td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr class='table-row'>
                                    <td colspan='3' class='px-6 py-4 text-center text-gray-500'>
                                        No mentorship records selected. Please select records from the previous page.
                                    </td>
                                </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>


    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const tableBody = document.getElementById('mentorshipTableBody');
            const rows = tableBody.querySelectorAll('tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                rows.forEach(row => {
                    // Skip if this is the "no records" row
                    if (row.querySelector('td[colspan]')) return;
                    
                    const usn = row.querySelector('td:first-child').textContent.toLowerCase();
                    const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    
                    if (usn.includes(searchTerm) || name.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show "no results" message if all rows are hidden
                const visibleRows = Array.from(rows).filter(row => 
                    row.style.display !== 'none' && !row.querySelector('td[colspan]')
                );
                
                const noRecordsRow = tableBody.querySelector('tr td[colspan]');
                if (noRecordsRow) {
                    if (visibleRows.length === 0 && searchTerm !== '') {
                        noRecordsRow.textContent = 'No matching records found';
                        noRecordsRow.closest('tr').style.display = '';
                    } else {
                        noRecordsRow.closest('tr').style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>