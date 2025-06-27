<?php
require_once 'config.php';
require_once 'ftheader.php';

// Fetching data from the project table for students in the same department
$sql = "SELECT p.name, p.usn 
        FROM project p
        JOIN students s ON p.usn = s.usn
        WHERE s.dept_code = ?
        ORDER BY p.usn";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['dept_code']);
$stmt->execute();
$result = $stmt->get_result();

$usnNumbers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usnNumbers[] = $row['usn'];
    }
    // Reset pointer for later use
    $result->data_seek(0);
}

// Function to extract numeric part from USN
function getNumericPart($usn)
{
    preg_match('/\d{3}$/', $usn, $matches);
    return isset($matches[0]) ? (int) $matches[0] : 0;
}

unset($_SESSION['selected_projects']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .checkbox-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .checkbox {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: relative;
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .checkbox:checked ~ .checkmark {
            background-color: #10b981;
            border-color: #10b981;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .checkbox:checked ~ .checkmark:after {
            display: block;
        }

        .row-selected {
            background-color: #f0f9ff;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-indigo-400">
    <div class="container mx-auto px-4 py-8 w-full lg:w-3/4">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-center rounded-t-xl">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Project Details</h1>
            <p class="text-blue-100 font-medium">Select the Project Records to view them</p>
        </div>

        <div class="bg-white rounded-b-xl shadow-md overflow-hidden p-6">
            <!-- Filter Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <!-- Search by Name/USN -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search by Name or USN</label>
                    <div class="flex">
                        <input type="text" id="searchInput" placeholder="Enter name or USN"
                            class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="filterTable()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- USN Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by USN Range</label>
                    <div class="flex items-center space-x-4">
                        <input type="text" id="usnMin" placeholder="From (001)"
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            oninput="validateUsnInput(this)">
                        <span class="text-gray-500">to</span>
                        <input type="text" id="usnMax" placeholder="To (030)"
                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            oninput="validateUsnInput(this)">
                        <button type="button" onclick="applyUsnRangeFilter()"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Apply
                        </button>
                        <button type="button" onclick="clearUsnRangeFilter()"
                            class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Status -->
            <div id="filterStatus" class="text-sm text-gray-600 mb-4 hidden">
                Showing results for: <span id="activeFilters"></span>
                <button onclick="clearAllFilters()" class="text-blue-600 hover:text-blue-800 ml-2">
                    <i class="fas fa-times"></i> Clear all
                </button>
            </div>

            <form id="checkboxForm" action="ftproject.php" method="POST">
                <div class="overflow-x-auto relative">
                    <table id="projectTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='hover:bg-gray-50'>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 usn-cell'>" . htmlspecialchars($row["usn"]) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($row["name"]) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>
                                                <label class='checkbox-container'>
                                                    <input type='checkbox' name='selected_data[]' value='" . htmlspecialchars($row["usn"]) . "|" . htmlspecialchars($row["name"]) . "' class='checkbox'>
                                                    <span class='checkmark'></span>
                                                </label>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='px-6 py-4 text-center text-sm text-gray-500'>No records found in your department</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="mt-4 text-sm text-red-600 hidden">
                    Please select at least one record before submitting.
                </div>
                
                <div class="flex items-center justify-center">
                    <button type="submit" class="mt-6 w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Submit Selected
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to extract numeric part from USN
        function getUsnNumber(usn) {
            const matches = usn.match(/\d{3}$/);
            return matches ? parseInt(matches[0]) : 0;
        }

        // Validate USN input (only numbers, max 3 digits)
        function validateUsnInput(input) {
            input.value = input.value.replace(/\D/g, '');
            if (input.value.length > 3) {
                input.value = input.value.slice(0, 3);
            }
        }

        // Filter table based on search input
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#projectTable tbody tr');

            rows.forEach(row => {
                if (row.style.display === 'none') return;
                
                const usn = row.querySelector('.usn-cell').textContent.toLowerCase();
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                if (usn.includes(searchInput) || name.includes(searchInput)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            updateFilterStatus();
        }

        // Apply USN range filter
        function applyUsnRangeFilter() {
            const minInput = document.getElementById('usnMin').value.padStart(3, '0');
            const maxInput = document.getElementById('usnMax').value.padStart(3, '0');
            const min = parseInt(minInput) || 0;
            const max = parseInt(maxInput) || 999;

            const rows = document.querySelectorAll('#projectTable tbody tr');

            rows.forEach(row => {
                const usnCell = row.querySelector('.usn-cell');
                if (usnCell) {
                    const usn = usnCell.textContent;
                    const usnNum = getUsnNumber(usn);
                    const shouldShow = (usnNum >= min && usnNum <= max);
                    row.style.display = shouldShow ? '' : 'none';
                }
            });

            updateFilterStatus();
        }

        // Clear USN range filter
        function clearUsnRangeFilter() {
            document.getElementById('usnMin').value = '';
            document.getElementById('usnMax').value = '';

            const rows = document.querySelectorAll('#projectTable tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });

            filterTable();
            updateFilterStatus();
        }

        // Clear all filters
        function clearAllFilters() {
            document.getElementById('searchInput').value = '';
            clearUsnRangeFilter();
        }

        // Update filter status display
        function updateFilterStatus() {
            const status = document.getElementById('filterStatus');
            const activeFilters = document.getElementById('activeFilters');
            const filters = [];
            
            const searchValue = document.getElementById('searchInput').value;
            if (searchValue) {
                filters.push(`Search: "${searchValue}"`);
            }
            
            const minValue = document.getElementById('usnMin').value;
            const maxValue = document.getElementById('usnMax').value;
            if (minValue || maxValue) {
                filters.push(`USN Range: ${minValue.padStart(3, '0')}-${maxValue.padStart(3, '0')}`);
            }
            
            if (filters.length) {
                activeFilters.innerHTML = filters.join(', ');
                status.classList.remove('hidden');
            } else {
                status.classList.add('hidden');
            }
        }

        // Form validation
        document.getElementById('checkboxForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.checkbox:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                document.getElementById('errorMessage').classList.remove('hidden');
            } else {
                // Collect all selected values
                const selectedData = Array.from(checkboxes).map(cb => cb.value);
                // Create a hidden input to send all selected data
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_data';
                hiddenInput.value = JSON.stringify(selectedData);
                this.appendChild(hiddenInput);
            }
        });

        // Attach event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Make rows clickable for selection
            document.querySelectorAll('#projectTable tbody tr').forEach(row => {
                row.addEventListener('click', (e) => {
                    if (!e.target.closest('a, button, input')) {
                        const checkbox = row.querySelector('.checkbox');
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                        document.getElementById('errorMessage').classList.add('hidden');
                    }
                });
            });

            // Checkbox change event
            document.querySelectorAll('.checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    this.closest('tr').classList.toggle('row-selected', this.checked);
                });
            });

            // Search input event
            document.getElementById('searchInput').addEventListener('input', filterTable);
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    filterTable();
                }
            });

            // USN range input events
            document.getElementById('usnMin').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyUsnRangeFilter();
                }
            });

            document.getElementById('usnMax').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyUsnRangeFilter();
                }
            });
        });
    </script>
</body>
</html>