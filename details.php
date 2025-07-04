<?php
require_once 'config.php';
require_once 'ftheader.php';

if (!isset($_SESSION['referrer'])) {
    $_SESSION['referrer'] = $_SERVER['HTTP_REFERER'] ?? 'ftinternship.php';
}

$usn = '';
$name = '';
$role = '';
$company_phone = '';
$location = '';
$start_date = '';
$end_date = '';
$languages_used = '';
$certificate_data = '';

if (isset($_GET['usn'])) {
    $usn = $conn->real_escape_string($_GET['usn']);

    $sql = "SELECT * FROM internship WHERE usn = '$usn'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $role = $row['role'];
        $company_phone = $row['phone'];
        $location = $row['location'];
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $languages_used = $row['languages_used'];
        $certificate_data = $row['certificate'];
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
    <title>Internship Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Enhanced Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            overflow: auto;
            backdrop-filter: blur(3px);
            transition: all 0.3s ease;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            border: none;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        #certificateContainer iframe {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            to {
                transform: rotate(360deg);
            }
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
                    <i class="fas fa-briefcase mr-2 text-blue-600"></i> Internship Details
                </h1>
                <div class="flex space-x-2">
                    <a href="ftinternship.php" class="safe-back-btn">
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
                        </div>
                    </div>

                    <!-- Internship Details -->
                    <div class="bg-indigo-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-indigo-800 mb-3">
                            <i class="fas fa-building mr-2"></i> Company Details
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Role</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($role); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Location</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($location); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600">Company Phone</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                    <?php echo htmlspecialchars($company_phone); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline and Technical Details -->
                <div class="space-y-4">
                    <!-- Timeline -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-green-800 mb-3">
                            <i class="far fa-calendar-alt mr-2"></i> Internship Timeline
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

                    <!-- Certificate -->
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h2 class="text-lg font-semibold text-yellow-800 mb-3">
                            <i class="fas fa-certificate mr-2"></i> Certificate
                        </h2>
                        <button id="viewCertificateBtn"
                            class="w-full py-3 px-4 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-lg transition-colors flex items-center justify-center font-medium">
                            <i class="fas fa-eye mr-2"></i> View Certificate
                        </button>
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
                    <i class="fas fa-certificate mr-2 text-blue-600"></i> Internship Certificate
                </h3>
                <span class="close">&times;</span>
            </div>

            <?php if (!empty($certificate_data)): ?>
                <div id="certificateContainer">
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="loading-spinner"></div>
                        <p class="mt-3 text-gray-600">Loading certificate...</p>
                    </div>
                </div>

                <div class="modal-actions">
                    <button onclick="downloadCertificate()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i> Download
                    </button>
                    <button onclick="printCertificate()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-circle text-5xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600 text-lg">No certificate uploaded for this internship.</p>
                    <p class="text-gray-500 mt-2">Please contact the student to upload their certificate.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("certificateModal");
        const btn = document.getElementById("viewCertificateBtn");
        const span = document.getElementsByClassName("close")[0];
        const hasCertificate = <?php echo !empty($certificate_data) ? 'true' : 'false'; ?>;

        btn.onclick = function () {
            if (!hasCertificate) {
                modal.style.display = "block";
                return;
            }

            modal.style.display = "block";

            fetch('view_certificate.php?usn=<?php echo $usn; ?>')
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
                        img.alt = 'Internship Certificate';
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
                                <p class="text-red-500 font-medium">Unsupported certificate format</p>
                                <p class="text-gray-600 mt-2">The certificate file type cannot be displayed in the browser.</p>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching certificate:', error);
                    document.getElementById('certificateContainer').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <p class="text-red-500 font-medium">Error loading certificate</p>
                            <p class="text-gray-600 mt-2">${error.message}</p>
                        </div>`;
                });
        }

        function downloadCertificate() {
            if (!hasCertificate) return;

            fetch('view_certificate.php?usn=<?php echo $usn; ?>')
                .then(response => response.blob())
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `certificate_<?php echo $usn; ?>_${new Date().getTime()}`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                })
                .catch(error => {
                    console.error('Error downloading certificate:', error);
                    alert('Error downloading certificate. Please try again.');
                });
        }

        function printCertificate() {
            if (!hasCertificate) return;

            const certificateContainer = document.getElementById('certificateContainer');
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Certificate - <?php echo $name; ?></title>
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

        span.onclick = function () {
            modal.style.display = "none";
        }

        window.onclick = function (event) {
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