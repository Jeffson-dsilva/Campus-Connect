<?php
require_once 'config.php';
require_once 'ftheader.php';

// Initialize variables for the project details
$usn = '';
$name = '';
$project_role = '';
$phone = '';
$location = '';
$start_date = '';
$end_date = '';
$languages_used = '';
$project_title = '';
$project_domain = '';
$project_description = '';
$features = '';
$problem_statement = '';
$proposed_solution = '';
$github_link = '';
$file_name = '';

// Check if the USN is passed as a query parameter
if (isset($_GET['usn'])) {
    $usn = $conn->real_escape_string($_GET['usn']);

    // Query to fetch details for the selected USN
    $sql = "SELECT * FROM project WHERE usn = '$usn'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $project_role = $row['project_role'];
        $phone = $row['phone'];
        $location = $row['location'];
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $languages_used = $row['languages_used'];
        $project_title = $row['project_title'];
        $project_domain = $row['project_domain'];
        $project_description = $row['project_description'];
        $features = $row['features'];
        $problem_statement = $row['problem_statement'];
        $proposed_solution = $row['proposed_solution'];
        $github_link = $row['github_link'];
        $file_name = $row['uploaded_file_name'];
    } else {
        echo "<p class='text-red-500'>No details found for the selected USN.</p>";
    }
} else {
    echo "<p class='text-red-500'>Invalid access. No USN provided.</p>";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            overflow: auto;
            backdrop-filter: blur(3px);
            transition: all 0.3s ease;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            border: none;
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .close {
            color: #94a3b8;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close:hover {
            color: #64748b;
            transform: scale(1.1);
        }
        
        /* Certificate container */
        #certificateContainer {
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            min-height: 60vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        #certificateContainer img {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        
        #certificateContainer iframe {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .safe-back-btn {
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .safe-back-btn:hover {
            background-color: #2563eb;
        }
        
        .safe-back-btn i {
            margin-right: 0.5rem;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400">
    <!-- Header is included from ftheader.php -->
    
    <main class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md overflow-hidden p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-project-diagram mr-2 text-blue-600"></i> Project Details
                </h1>
                <div class="flex space-x-2">
                    <a href="ftproject.php" class="safe-back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Student Information -->
                <div class="space-y-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-blue-800 mb-3">
                            <i class="fas fa-user-graduate mr-2"></i> Student Information
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">USN</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($usn); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Name</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($name); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Project Role</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($project_role); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Project Timeline -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-green-800 mb-3">
                            <i class="far fa-calendar-alt mr-2"></i> Project Timeline
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Start Date</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($start_date); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">End Date</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($end_date); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Technical Details -->
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-purple-800 mb-3">
                            <i class="fas fa-code mr-2"></i> Technical Details
                        </h2>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Languages/Technologies Used</label>
                            <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700 min-h-20">
                                <?php echo nl2br(htmlspecialchars($languages_used)); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Project Details -->
                <div class="space-y-4">
                    <!-- Project Information -->
                    <div class="bg-indigo-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-indigo-800 mb-3">
                            <i class="fas fa-info-circle mr-2"></i> Project Information
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Project Title</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($project_title); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Project Domain</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($project_domain); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Project Description</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700 min-h-20">
                                    <?php echo nl2br(htmlspecialchars($project_description)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Project Content -->
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-yellow-800 mb-3">
                            <i class="fas fa-lightbulb mr-2"></i> Project Content
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Problem Statement</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700 min-h-20">
                                    <?php echo nl2br(htmlspecialchars($problem_statement)); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Proposed Solution</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700 min-h-20">
                                    <?php echo nl2br(htmlspecialchars($proposed_solution)); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Features</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700 min-h-20">
                                    <?php echo nl2br(htmlspecialchars($features)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resources -->
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-red-800 mb-3">
                            <i class="fas fa-link mr-2"></i> Resources
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">GitHub Link</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php if (!empty($github_link)): ?>
                                        <a href="<?php echo htmlspecialchars($github_link); ?>" target="_blank" class="text-blue-600 hover:underline">
                                            <?php echo htmlspecialchars($github_link); ?>
                                        </a>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Uploaded File</label>
                                <button id="viewCertificateBtn" class="w-full mt-1 py-2 px-4 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition-colors flex items-center justify-center">
                                    <i class="fas fa-eye mr-2"></i> View Uploaded File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Certificate Modal -->
    <div id="certificateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-alt mr-2 text-blue-600"></i> Uploaded Project File
                </h3>
                <span class="close">&times;</span>
            </div>

            <?php if (!empty($file_name)): ?>
                <div id="certificateContainer">
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="loading-spinner"></div>
                        <p class="mt-3 text-gray-600">Loading file...</p>
                    </div>
                </div>

                <div class="modal-actions">
                    <button onclick="downloadCertificate()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i> Download
                    </button>
                    <button onclick="printCertificate()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-circle text-5xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 text-lg">No file uploaded for this project.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("certificateModal");
        const btn = document.getElementById("viewCertificateBtn");
        const span = document.getElementsByClassName("close")[0];
        const hasCertificate = <?php echo !empty($file_name) ? 'true' : 'false'; ?>;

        btn.onclick = function() {
            if (!hasCertificate) {
                modal.style.display = "block";
                return;
            }

            modal.style.display = "block";
            
            fetch('view_project_certificate.php?usn=<?php echo $usn; ?>')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.blob();
                })
                .then(blob => {
                    const contentType = blob.type;
                    const certificateContainer = document.getElementById('certificateContainer');
                    
                    // Clear previous content
                    certificateContainer.innerHTML = '';
                    
                    if (contentType.startsWith('image')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(blob);
                        img.className = 'max-w-full max-h-[70vh] object-contain';
                        img.alt = 'Project File';
                        img.onload = () => URL.revokeObjectURL(img.src);
                        certificateContainer.appendChild(img);
                    } else if (contentType === 'application/pdf') {
                        const iframe = document.createElement('iframe');
                        iframe.src = URL.createObjectURL(blob);
                        iframe.className = 'w-full h-[70vh] border-0';
                        iframe.onload = () => URL.revokeObjectURL(iframe.src);
                        certificateContainer.appendChild(iframe);
                    } else {
                        certificateContainer.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                                <p class="text-red-500 font-medium">Unsupported file format</p>
                                <p class="text-gray-600 mt-2">The file type cannot be displayed in the browser.</p>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching file:', error);
                    document.getElementById('certificateContainer').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <p class="text-red-500 font-medium">Error loading file</p>
                            <p class="text-gray-600 mt-2">${error.message}</p>
                        </div>`;
                });
        }

        function downloadCertificate() {
            if (!hasCertificate) return;
            
            fetch('view_project_certificate.php?usn=<?php echo $usn; ?>')
                .then(response => response.blob())
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'project_file_<?php echo $usn; ?>';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                })
                .catch(error => {
                    console.error('Error downloading file:', error);
                    alert('Error downloading file. Please try again.');
                });
        }

        function printCertificate() {
            if (!hasCertificate) return;
            
            const certificateContainer = document.getElementById('certificateContainer');
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Project File - <?php echo $name; ?></title>
                        <style>
                            body { margin: 0; padding: 20px; }
                            img, iframe { max-width: 100%; height: auto; }
                            @page { size: auto; margin: 0mm; }
                        </style>
                    </head>
                    <body>
                        ${certificateContainer.innerHTML}
                        <script>
                            window.onload = function() {
                                setTimeout(function() {
                                    window.print();
                                    window.close();
                                }, 500);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>