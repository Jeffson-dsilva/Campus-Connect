<?php
require_once 'config.php';
require_once 'hodheader.php';

$usn = isset($_GET['usn']) ? $conn->real_escape_string($_GET['usn']) : '';

// Fetch project details
$sql = "SELECT * FROM project WHERE usn = '$usn'";
$result = $conn->query($sql);
$details = $result->fetch_assoc();

$title = "Project Details - " . htmlspecialchars($details['name'] ?? '');
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
    <main class="mx-auto px-4 py-8 lg:w-3/4 xl:w-3/4 2xl:w-3/4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Project Details</h2>
                        <p class="text-purple-100 text-sm mt-1">Detailed information about student's project</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="hodviewproject.php" class="text-white hover:text-purple-200 transition-colors">
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
                        <div class="bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-purple-800 mb-4 border-b border-purple-200 pb-2">
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
                            </div>
                        </div>

                        <!-- Project Basic Info -->
                        <div class="bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-purple-800 mb-4 border-b border-purple-200 pb-2">
                                <i class="fas fa-info-circle mr-2"></i>Project Overview
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Project Title</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['project_title']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Domain</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['project_domain']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Technologies Used</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($details['languages_used']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">GitHub Link</p>
                                    <a href="<?php echo htmlspecialchars($details['github_link']); ?>" target="_blank" class="mt-1 text-sm font-medium text-blue-600 hover:text-blue-800 break-all">
                                        <?php echo htmlspecialchars($details['github_link']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="md:col-span-2 bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-purple-800 mb-4 border-b border-purple-200 pb-2">
                                <i class="fas fa-file-alt mr-2"></i>Project Description
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Problem Statement</p>
                                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($details['problem_statement']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Proposed Solution</p>
                                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($details['proposed_solution']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Project Description</p>
                                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($details['project_description']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Features</p>
                                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($details['features']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Project Files -->
                        <div class="md:col-span-2 bg-purple-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-purple-800 mb-4 border-b border-purple-200 pb-2">
                                <i class="fas fa-file-upload mr-2"></i>Project Files
                            </h3>
                            <div class="flex items-center justify-center">
                                <?php if (!empty($details['uploaded_file_name'])) : ?>
                                    <button id="viewFileBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                        <i class="fas fa-eye mr-2"></i> View Uploaded File
                                    </button>
                                <?php else : ?>
                                    <p class="text-gray-500">No file uploaded</p>
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
                        <p class="mt-1 text-sm text-gray-500">No project details found for the selected USN.</p>
                        <div class="mt-6">
                            <a href="hodviewproject.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Project List
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- File Modal -->
        <div id="fileModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Project File
                            </h3>
                            <button id="closeModal" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <?php if (!empty($details['uploaded_file_name'])) : ?>
                                <?php
                                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                                $fileType = finfo_buffer($fileInfo, $details['uploaded_file_name']);
                                finfo_close($fileInfo);

                                if (strpos($fileType, 'image/') === 0) : ?>
                                    <img src="data:<?php echo $fileType; ?>;base64,<?php echo base64_encode($details['uploaded_file_name']); ?>" class="w-full h-auto max-h-[70vh] object-contain">
                                <?php elseif ($fileType === 'application/pdf') : ?>
                                    <embed src="data:application/pdf;base64,<?php echo base64_encode($details['uploaded_file_name']); ?>" type="application/pdf" width="100%" height="600px">
                                <?php else : ?>
                                    <p class="text-gray-500">Unsupported file format.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button id="closeModalBtn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // File Modal Handling
        const modal = document.getElementById('fileModal');
        const btn = document.getElementById('viewFileBtn');
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