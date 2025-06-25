<?php
require_once 'config.php';
require_once 'ftheader.php';

// Fetching data from the internship table
$sql = "SELECT name, usn FROM internship ORDER BY usn";
$result = $conn->query($sql);

// Extract USN numbers for range filter
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
// Clear previous selections when loading the selection page
unset($_SESSION['selected_internships']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .checkbox-container {
            position: relative;
            display: inline-block;
        }

        .checkbox {
            position: absolute;
            opacity: 0;
            height: 0;
            width: 0;
        }

        .checkmark {
            display: inline-block;
            width: 20px;
            height: 20px;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .checkbox:checked~.checkmark {
            background-color: #10b981;
            border-color: #10b981;
        }

        .checkmark:after {
            content: "✓";
            position: absolute;
            display: none;
            color: white;
            font-weight: bold;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
        }

        .checkbox:checked~.checkmark:after {
            display: block;
        }

        .noUi-connect {
            background: #3b82f6;
        }

        .active-filter {
            box-shadow: 0 0 0 2px #3b82f6;
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
                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Internship Details</h1>
            <p class="text-blue-100 font-medium">Select the Internship Records to view them</p>
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

            <!-- Form submission to ftinternship.php -->
            <form id="checkboxForm" action="ftinternship.php" method="POST" onsubmit="saveSelections()">
                <div class="overflow-x-auto relative">
                    <table id="internshipTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    USN</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Select</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr class='hover:bg-gray-50 hover:cursor-pointer'>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 usn-cell'>" . htmlspecialchars($row["usn"]) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($row["name"]) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>
                                                <label class='checkbox-container hover:cursor-pointer'>
                                                    <input type='checkbox' name='selected_data[]' value='" . htmlspecialchars($row["usn"]) . "|" . htmlspecialchars($row["name"]) . "' class='checkbox'>
                                                    <span class='checkmark'></span>
                                                </label>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='px-6 py-4 text-center text-sm text-gray-500'>No records found</td></tr>";
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
                    <button type="submit"
                        class="mt-6 w-full sm:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Submit Selected
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="hidden fixed inset-0 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        // Apply USN range filter
        function applyUsnRangeFilter() {
            showLoading();

            const minInput = document.getElementById('usnMin').value.padStart(3, '0');
            const maxInput = document.getElementById('usnMax').value.padStart(3, '0');
            const min = parseInt(minInput) || 0;
            const max = parseInt(maxInput) || 999;

            const rows = document.querySelectorAll('#internshipTable tbody tr');

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
            hideLoading();
        }

        // Clear USN range filter
        function clearUsnRangeFilter() {
            showLoading();

            document.getElementById('usnMin').value = '';
            document.getElementById('usnMax').value = '';

            const rows = document.querySelectorAll('#internshipTable tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });

            filterTable();
            updateFilterStatus();
            hideLoading();
        }

        // Clear all filters
        function clearAllFilters() {
            showLoading();

            document.getElementById('searchInput').value = '';
            clearUsnRangeFilter();

            hideLoading();
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

        // Loading indicator functions
        function showLoading() {
            document.getElementById('internshipTable').classList.add('opacity-50');
            document.getElementById('loadingIndicator').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('internshipTable').classList.remove('opacity-50');
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        // Function to validate checkbox selection
        function validateSelection(event) {
            const checkboxes = document.querySelectorAll('.checkbox');
            const errorMessage = document.getElementById('errorMessage');
            let isChecked = false;

            checkboxes.forEach((checkbox) => {
                if (checkbox.checked) {
                    isChecked = true;
                }
            });

            if (!isChecked) {
                event.preventDefault();
                errorMessage.classList.remove('hidden');
            }
        }

        // Function to hide error message when a checkbox is clicked
        function hideError() {
            document.getElementById('errorMessage').classList.add('hidden');
        }

        // Function to filter table based on search input
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('internshipTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                if (rows[i].style.display === 'none') continue;

                const columns = rows[i].getElementsByTagName('td');
                const name = columns[1].textContent.toLowerCase();
                const usn = columns[0].textContent.toLowerCase();

                if (name.includes(searchInput) || usn.includes(searchInput)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }

            updateFilterStatus();
        }

        // Attach event listeners
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('checkboxForm');
            const checkboxes = document.querySelectorAll('.checkbox');

            form.addEventListener('submit', validateSelection);
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    hideError();
                    this.closest('tr').classList.toggle('row-selected', this.checked);
                });
            });

            // Make rows clickable for selection
            // Replace the existing event listener for row clicks with this:
            document.querySelectorAll('#internshipTable tbody tr').forEach(row => {
                row.addEventListener('click', (e) => {
                    // Only handle clicks that aren't on the checkbox or its container
                    if (!e.target.closest('.checkbox-container, a, button, input')) {
                        const checkbox = row.querySelector('.checkbox');
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });


            // Add event listeners for Enter key in search fields
            document.getElementById('searchInput').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    filterTable();
                }
            });

            document.getElementById('usnMin').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    applyUsnRangeFilter();
                }
            });

            document.getElementById('usnMax').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    applyUsnRangeFilter();
                }
            });
        });

        function saveSelections() {
            // Store current search/filter state in localStorage
            localStorage.setItem('searchTerm', document.getElementById('searchInput').value);
            localStorage.setItem('usnMin', document.getElementById('usnMin').value);
            localStorage.setItem('usnMax', document.getElementById('usnMax').value);
        }

    </script>
</body>

</html>