<?php
require_once 'config.php';
require_once 'hodheader.php';

$usn = isset($_GET['usn']) ? $conn->real_escape_string($_GET['usn']) : '';

// Fetch internship details
$sql = "SELECT * FROM internship WHERE usn = '$usn'";
$result = $conn->query($sql);
$details = $result->fetch_assoc();

$title = "Internship Details - " . htmlspecialchars($details['name'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-50">
    <main class="mx-auto px-4 py-5 lg:w-3/4 xl:w-3/4 2xl:w-3/4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Internship Details</h2>
                        <p class="text-blue-100 text-sm mt-1">Detailed information about student's internship</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="hodviewinternship.php" class="text-white hover:text-blue-200 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <?php if ($details) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Student Information -->
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-800 mb-4 border-b border-blue-200 pb-2">
                                <i class="fas fa-user-graduate mr-2"></i>Student Information
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Name</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">USN</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['usn']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Phone</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['phone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Internship Details -->
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-800 mb-4 border-b border-blue-200 pb-2">
                                <i class="fas fa-briefcase mr-2"></i>Internship Details
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Role</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['role']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Location</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['location']); ?></p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Start Date</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['start_date']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">End Date</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['end_date']); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Languages Used</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['languages_used']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Section -->
                        <div class="md:col-span-2 bg-blue-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-blue-800 mb-4 border-b border-blue-200 pb-2">
                                <i class="fas fa-certificate mr-2"></i>Certificate
                            </h3>
                            <div class="flex items-center justify-center">
                                <?php if (!empty($details['certificate'])) : ?>
                                    <button id="viewCertificateBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-eye mr-2"></i> View Certificate
                                    </button>
                                <?php else : ?>
                                    <p class="text-gray-500">No certificate uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="text-center py-12">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <i class="fas fa-exclamation-circle text-gray-400"></i>
                        </div>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No details found</h3>
                        <p class="mt-1 text-sm text-gray-500">No internship details found for the selected USN.</p>
                        <div class="mt-6">
                            <a href="hodviewinternship.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Internship List
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificate Modal -->
        <div id="certificateModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Internship Certificate
                            </h3>
                            <button id="closeModal" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <?php if (!empty($details['certificate'])) : ?>
                                <?php
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $fileType = finfo_buffer($finfo, $details['certificate']);
                                finfo_close($finfo);

                                if (strpos($fileType, 'image') !== false) : ?>
                                    <img src="data:<?php echo $fileType; ?>;base64,<?php echo base64_encode($details['certificate']); ?>" alt="Internship Certificate" class="w-full h-auto max-h-[70vh] object-contain">
                                <?php elseif ($fileType === 'application/pdf') : ?>
                                    <embed src="data:application/pdf;base64,<?php echo base64_encode($details['certificate']); ?>" type="application/pdf" width="100%" height="600px">
                                <?php else : ?>
                                    <p class="text-gray-500">Unsupported certificate format.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button id="closeModalBtn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Certificate Modal Handling
        const modal = document.getElementById('certificateModal');
        const btn = document.getElementById('viewCertificateBtn');
        const closeModal = document.getElementById('closeModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        btn?.addEventListener('click', () => {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });

        const closeModalFunc = () => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        };

        closeModal?.addEventListener('click', closeModalFunc);
        closeModalBtn?.addEventListener('click', closeModalFunc);

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModalFunc();
            }
        });
    </script>
</body>

</html>