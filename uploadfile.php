<?php
require_once 'config.php';
require_once 'hodheader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        .file-upload:hover {
            border-color: #8b5cf6;
            background-color: #f5f3ff;
        }
        .file-upload.drag-over {
            border-color: #7c3aed;
            background-color: #ede9fe;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Main Cards -->
    <div class="container mx-auto mt-10 justify-between px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Upload Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="p-6 flex flex-col items-center text-center">
                    <div class="bg-purple-100 p-4 rounded-full mb-4">
                        <i class="fas fa-upload text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Files</h3>
                    <p class="text-gray-600 text-sm mb-4">Upload important documents</p>
                    <button onclick="showSection('upload')"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                        Upload
                    </button>
                </div>
            </div>

            <!-- Delete Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="p-6 flex flex-col items-center text-center">
                    <div class="bg-purple-100 p-4 rounded-full mb-4">
                        <i class="fas fa-trash-alt text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Delete Data</h3>
                    <p class="text-gray-600 mb-5">Remove student or faculty records</p>
                    <button onclick="showSection('delete')"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Display Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="p-6 flex flex-col items-center text-center">
                    <div class="bg-purple-100 p-4 rounded-full mb-4">
                        <i class="fas fa-database text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">View Data</h3>
                    <p class="text-gray-600 text-sm mb-4">Browse existing records</p>
                    <button onclick="showSection('display')"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                        Display
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-fade-in">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Upload Data</h3>
                        <button onclick="hideSections()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <form id="uploadForm" method="post" enctype="multipart/form-data">
                            <div class="flex space-x-4 mb-6">
                                <div class="flex items-center">
                                    <input id="option-1" name="select" type="radio" checked class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                    <label for="option-1" class="ml-2 block text-sm text-gray-700">Student</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="option-2" name="select" type="radio" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                    <label for="option-2" class="ml-2 block text-sm text-gray-700">Faculty</label>
                                </div>
                            </div>
                            
                            <div id="fileUploadContainer" class="file-upload border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer mb-4 transition-colors duration-200"
                                ondragover="event.preventDefault(); this.classList.add('drag-over')"
                                ondragleave="this.classList.remove('drag-over')"
                                ondrop="handleDrop(event)">
                                <input type="file" id="fileInput" name="fileInput" class="hidden" accept=".xls,.xlsx" required onchange="showFileName()">
                                <label for="fileInput" class="cursor-pointer">
                                    <i class="fas fa-cloud-upload-alt text-purple-500 text-3xl mb-2"></i>
                                    <p class="text-sm text-gray-600" id="fileName">Drag & drop your file here or click to browse</p>
                                    <p class="text-xs text-gray-500 mt-1">Supports: .xls, .xlsx</p>
                                </label>
                            </div>
                            
                            <div class="mt-6">
                                <button type="button" onclick="uploadData()" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                    <i class="fas fa-upload mr-2"></i> Upload File
                                </button>
                            </div>
                        </form>
                        <div id="uploadStatus" class="mt-4 text-sm text-center"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Modal -->
    <div id="displayModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full animate-fade-in">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">View Data</h3>
                        <button onclick="hideSections()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-evenly mb-6">
                            <div class="flex items-center">
                                <input id="display-option-1" name="displaySelect" type="radio" checked class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                <label for="display-option-1" class="ml-2 block text-lg text-gray-700">Student</label>
                            </div>
                            <div class="flex items-center">
                                <input id="display-option-2" name="displaySelect" type="radio" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                <label for="display-option-2" class="ml-2 block text-lg text-gray-700">Faculty</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-center mb-4">
                            <button onclick="displayData()" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <i class="fas fa-eye mr-2"></i> Display Data
                            </button>
                            <button onclick="printTable()" class="hidden inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <i class="fas fa-print mr-2"></i> Print
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table id="dataTable" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr></tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-fade-in">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Data</h3>
                        <button onclick="hideSections()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex space-x-4 mb-6">
                            <div class="flex items-center">
                                <input id="delete-option-1" name="deleteSelect" type="radio" checked class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                <label for="delete-option-1" class="ml-2 block text-sm text-gray-700">Student</label>
                            </div>
                            <div class="flex items-center">
                                <input id="delete-option-2" name="deleteSelect" type="radio" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300">
                                <label for="delete-option-2" class="ml-2 block text-sm text-gray-700">Faculty</label>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        This will permanently delete all records. Are you sure?
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button onclick="deleteAllData()" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash mr-2"></i> Confirm Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show file name when selected
        function showFileName() {
            const fileInput = document.getElementById('fileInput');
            const fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'Drag & drop your file here or click to browse';
            document.getElementById('fileName').innerText = fileName;
        }

        // Handle file drop
        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('fileUploadContainer').classList.remove('drag-over');
            
            const fileInput = document.getElementById('fileInput');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                showFileName();
            }
        }

        // Validate Excel file structure
        function isValidFile(jsonData, type) {
            const requiredFields = type === 'students' ? ['Name', 'USN', 'Email', 'Password'] : ['Employee_ID', 'Name', 'Email', 'Password'];
            for (let i = 0; i < jsonData.length; i++) {
                for (let field of requiredFields) {
                    if (!jsonData[i].hasOwnProperty(field)) {
                        return false;
                    }
                }
            }
            return true;
        }

        // Upload data function
        function uploadData() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            const typeSelect = document.querySelector('input[name="select"]:checked').id === 'option-1' ? 'students' : 'faculty';

            if (!file) {
                showPopup('Please choose a file to upload.', 'error');
                return;
            }

            document.getElementById('uploadStatus').innerText = 'Uploading... Please wait.';

            const reader = new FileReader();
            reader.onload = function (event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(sheet);

                if (!isValidFile(jsonData, typeSelect)) {
                    showPopup('Failed to load data: Missing required fields.', 'error');
                    return;
                }

                fetch('upload.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ data: jsonData, type: typeSelect }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showPopup(data.message, 'success');
                        } else {
                            showPopup('Some records were already present in the database. Only new data was uploaded.', 'info');
                        }
                    })
                    .catch(error => {
                        console.error('Error uploading file:', error);
                        showPopup('Failed to upload data.', 'error');
                    });
            };

            reader.readAsArrayBuffer(file);
        }

        // Show popup notification
        function showPopup(message, type) {
            const popup = document.createElement('div');
            popup.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${type === 'success' ? 'bg-green-50 border-l-4 border-green-400' : type === 'error' ? 'bg-red-50 border-l-4 border-red-400' : 'bg-blue-50 border-l-4 border-blue-400'}`;
            
            const popupContent = `
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="${type === 'success' ? 'fas fa-check-circle text-green-400' : type === 'error' ? 'fas fa-times-circle text-red-400' : 'fas fa-info-circle text-blue-400'}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : type === 'error' ? 'text-red-800' : 'text-blue-800'}">
                            ${message}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            popup.innerHTML = popupContent;
            document.body.appendChild(popup);
            setTimeout(() => popup.remove(), 5000);
            document.getElementById('uploadStatus').innerText = '';
        }

        // Show/hide sections
        function showSection(section) {
            if (section === 'home') {
                window.location.href = 'hodDashboard.php';
                return;
            }

            document.querySelectorAll('[id$="Modal"]').forEach(modal => {
                modal.classList.add('hidden');
            });
            
            const modalToShow = document.getElementById(section + 'Modal');
            if (modalToShow) {
                modalToShow.classList.remove('hidden');
            }
        }

        function hideSections() {
            document.querySelectorAll('[id$="Modal"]').forEach(modal => {
                modal.classList.add('hidden');
            });
        }

        // Display data function
        function displayData() {
            const displaySelect = document.querySelector('input[name="displaySelect"]:checked').id;

            if (displaySelect === 'display-option-1') {
                window.location.href = 'display.php';
            } else if (displaySelect === 'display-option-2') {
                window.location.href = 'facultydisplay.php';
            }
        }

        // Delete all data function
        function deleteAllData() {
            const deleteSelect = document.querySelector('input[name="deleteSelect"]:checked').id === 'delete-option-1' ? 'students' : 'faculty';

            if (deleteSelect === 'students') {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ type: 'students', checkData: true }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.found) {
                                fetch('delete.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ type: 'students' }),
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        showPopup(data.message, data.success ? 'success' : 'error');
                                    });
                            } else {
                                showPopup('No student data found to delete.', 'error');
                            }
                        } else {
                            showPopup(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking student data:', error);
                        showPopup('Failed to check student data.', 'error');
                    });
            } else if (deleteSelect === 'faculty') {
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ type: 'faculty', checkData: true }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.found) {
                                fetch('delete.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ type: 'faculty' }),
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        showPopup(data.message, data.success ? 'success' : 'error');
                                    });
                            } else {
                                showPopup('No faculty data found to delete.', 'error');
                            }
                        } else {
                            showPopup(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking faculty data:', error);
                        showPopup('Failed to check faculty data.', 'error');
                    });
            }
        }

        // Print table function
        function printTable() {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Table</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(document.getElementById('dataTable').outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 200);
        }
    </script>
</body>
</html>