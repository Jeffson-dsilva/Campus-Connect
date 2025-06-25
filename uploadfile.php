<?php
require_once 'config.php';

require_once 'hodheader.php';
?>


    <link rel="stylesheet" href="uploadfile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<body class="max-h-screen bg-gradient-to-br from-blue-100 to-indigo-400">
    <div class="flex items-center justify-evenly mt-8">
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-upload text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Files</h3>
                <p class="text-gray-600 text-sm mb-4">Upload important documents</p>
                <button onclick="showSection('upload')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                    Go to Upload
                </button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-upload text-[#0452a5] text-2xl"></i>
                </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Manage Data</h3>
                        <p class="text-gray-600 mb-5">Remove student or faculty records</p>
                <button onclick="showSection('delete')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                    Delete
                </button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-database text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">View Data</h3>
                <p class="text-gray-600 text-sm mb-4">Browse existing student or faculty records</p>
                <button onclick="showSection('display')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors duration-200">
                    Display
                </button>
            </div>
        </div>
    </div>
    <main id="mainContent">
        <!-- Upload Section -->
        <div class="section upload-section animated" id="uploadSection">
            <div class="box">
                <form id="uploadForm" method="post" enctype="multipart/form-data">
                    <!-- Radio buttons for selecting Student or Faculty -->
                    <div class="wrapper">
                        <input type="radio" name="select" id="option-1" checked>
                        <input type="radio" name="select" id="option-2">
                        <label for="option-1" class="option option-1">
                            <div class="dot"></div>
                            <span>Student</span>
                        </label>
                        <label for="option-2" class="option option-2">
                            <div class="dot"></div>
                            <span>Faculty</span>
                        </label>
                    </div><br><br>

                    <input type="file" id="fileInput" name="fileInput" class="file-input" accept=".xls,.xlsx" required
                        onchange="showFileName()" />
                    <div id="fileName">No file selected</div>
                    <button type="button" class="upload-button" onclick="uploadData()"><i class="fas fa-upload"></i>
                        Upload File</button>
                </form>
                <div id="uploadStatus"></div>
                <button class="back-to-menu" onclick="hideSections()">Back to Menu</button>
            </div>
        </div>

        <!-- Display Section with Radio Buttons -->
        <div class="section display-section animated" id="displaySection">
            <div class="box">
                <!-- Radio buttons for selecting Student or Faculty -->
                <div class="wrapper">
                    <input type="radio" name="displaySelect" id="display-option-1" checked>
                    <input type="radio" name="displaySelect" id="display-option-2">
                    <label for="display-option-1" class="option option-1">
                        <div class="dot"></div>
                        <span>Student</span>
                    </label>
                    <label for="display-option-2" class="option option-2">
                        <div class="dot"></div>
                        <span>Faculty</span>
                    </label>
                </div><br><br>

                <button class="display-button" onclick="displayData()"><i class="fas fa-eye"></i> Display</button>
                <button class="print-button" onclick="printTable()" style="display:none;"><i class="fas fa-print"></i>
                    Print</button>
                <table id="dataTable" class="data-table">
                    <thead>
                        <tr>
                            <!-- Removed the headers as per request -->
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button class="back-to-menu" onclick="hideSections()">Back to Menu</button>
            </div>
        </div>


        <!-- Delete Section -->
        <!-- Delete Section -->
        <div class="section delete-section animated" id="deleteSection">
            <div class="box">
                <!-- Radio buttons for selecting Student or Faculty -->
                <div class="wrapper">
                    <input type="radio" name="deleteSelect" id="delete-option-1" checked>
                    <input type="radio" name="deleteSelect" id="delete-option-2">

                    <label for="delete-option-1" class="option option-1">
                        <div class="dot"></div>
                        <span>Student</span>
                    </label>
                    <label for="delete-option-2" class="option option-2">
                        <div class="dot"></div>
                        <span>Faculty</span>
                    </label>
                </div><br><br>


                <button class="delete-button" onclick="deleteAllData()"><i class="fas fa-trash"></i> Delete</button>
                <button class="back-to-menu" onclick="hideSections()">Back to Menu</button>
            </div>
        </div>

    </main>

    <script>
        function showFileName() {
            const fileInput = document.getElementById('fileInput');
            const fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';
            document.getElementById('fileName').innerText = fileName;
        }

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

        function uploadData() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            const typeSelect = document.querySelector('input[name="select"]:checked').id === 'option-1' ? 'students' : 'faculty';

            if (!file) {
                alert('Please choose a file to upload.');
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

        function showPopup(message, type) {
            const popup = document.createElement('div');
            popup.classList.add('popup', type);
            const popupContent =
                `<div class="popup-content">
                    <div class="popup-icon">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'info' ? 'fa-info-circle' : 'fa-times-circle')}"></i>
                    </div>
                    <p>${message}</p>
                    <button class="popup-close" onclick="closePopup()">Close</button>
                </div>`;
            popup.innerHTML = popupContent;
            document.body.appendChild(popup);
            setTimeout(closePopup, 5000);
        }

        function closePopup() {
            const popup = document.querySelector('.popup');
            if (popup) {
                popup.remove();
            }
            document.getElementById('uploadStatus').innerText = '';
        }

        function toggleMenu() {
            const sideMenu = document.getElementById('sideMenu');
            sideMenu.classList.toggle('active');
        }
        function showSection(section) {
            if (section === 'home') {
                window.location.href = 'hodDashboard.php';
                return;
            }

            document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
            const sectionToShow = document.getElementById(section + 'Section');
            if (sectionToShow) {
                sectionToShow.style.display = 'block';
            }
        }


        function hideSections() {
            document.querySelectorAll('.section').forEach(sec => sec.style.display = 'none');
        }


        function displayData() {
            const displaySelect = document.querySelector('input[name="displaySelect"]:checked').id;

            if (displaySelect === 'display-option-1') {
                // Redirect to display.php for Student
                window.location.href = 'display.php';
            } else if (displaySelect === 'display-option-2') {
                // Redirect to facultydisplay.php for Faculty
                window.location.href = 'facultydisplay.php';
            }
        }



        function deleteAllData() {
            // Check if Student or Faculty is selected
            const deleteSelect = document.querySelector('input[name="deleteSelect"]:checked').id === 'delete-option-1' ? 'students' : 'faculty';

            if (deleteSelect === 'students') {
                // Call the PHP script to check if student data exists
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
                                // Data found, proceed with deletion
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
                                // No data found to delete
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
                // Call the PHP script to check if faculty data exists
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
                                // Data found, proceed with deletion
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
                                // No data found to delete
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


        function toggleUserInfo() {
            const userInfoPanel = document.getElementById('userInfoPanel');
            userInfoPanel.classList.toggle('active');
        }


        function logout() {
            window.location.href = 'login.php';
        }
    </script>
</body>

</html>