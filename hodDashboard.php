<?php
$title = "HOD Dashboard";
require_once 'hodheader.php';

// Get HOD's department code from session
$hodDept = $_SESSION['dept_code']; // Make sure this is set during login

// Get analytics data for HOD's department only
$totalStudentsQuery = "SELECT COUNT(*) as total FROM students WHERE dept_code = ?";
$totalStudentsStmt = $conn->prepare($totalStudentsQuery);
$totalStudentsStmt->bind_param("s", $hodDept);
$totalStudentsStmt->execute();
$totalStudentsResult = $totalStudentsStmt->get_result();
$totalStudents = $totalStudentsResult->fetch_assoc()['total'];

// Internship stats for department
$internshipStatsQuery = "SELECT COUNT(DISTINCT i.usn) as submitted 
                         FROM internship i
                         JOIN students s ON i.usn = s.usn
                         WHERE s.dept_code = ?";
$internshipStmt = $conn->prepare($internshipStatsQuery);
$internshipStmt->bind_param("s", $hodDept);
$internshipStmt->execute();
$internshipStatsResult = $internshipStmt->get_result();
$internshipSubmitted = $internshipStatsResult->fetch_assoc()['submitted'];

// Project stats for department
$projectStatsQuery = "SELECT COUNT(DISTINCT p.usn) as submitted 
                      FROM project p
                      JOIN students s ON p.usn = s.usn
                      WHERE s.dept_code = ?";
$projectStmt = $conn->prepare($projectStatsQuery);
$projectStmt->bind_param("s", $hodDept);
$projectStmt->execute();
$projectStatsResult = $projectStmt->get_result();
$projectSubmitted = $projectStatsResult->fetch_assoc()['submitted'];

// MOOC stats for department
$moocStatsQuery = "SELECT COUNT(DISTINCT m.usn) as submitted 
                   FROM mooc_courses m
                   JOIN students s ON m.usn = s.usn
                   WHERE s.dept_code = ?";
$moocStmt = $conn->prepare($moocStatsQuery);
$moocStmt->bind_param("s", $hodDept);
$moocStmt->execute();
$moocStatsResult = $moocStmt->get_result();
$moocSubmitted = $moocStatsResult->fetch_assoc()['submitted'];

// Calculate percentages
$internshipPercent = $totalStudents > 0 ? round(($internshipSubmitted / $totalStudents) * 100) : 0;
$projectPercent = $totalStudents > 0 ? round(($projectSubmitted / $totalStudents) * 100) : 0;
$moocPercent = $totalStudents > 0 ? round(($moocSubmitted / $totalStudents) * 100) : 0;
?>

<div class="container mx-auto mt-6 px-4 py-8 md:py-12 pb-20">
    <!-- Welcome Header -->
    <div class="text-center mb-8 md:mb-12">
        <h1 class="text-2xl md:text-3xl font-bold text-[#0452a5] mb-2">Welcome, <?php echo explode(' ', $hodName)[1]; ?>!</h1>
        <p class="text-gray-600">HOD - <?php echo $_SESSION['dept_code']; ?> Department 
        </p>
    </div>
    <!-- Dashboard Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <!-- Internship Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-briefcase text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Internship</h3>
                <p class="text-gray-600 text-sm mb-4">View student internship details</p>
                <a href="hodviewinternship.php"
                    class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    View Details
                </a>
            </div>
        </div>

        <!-- Project Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-project-diagram text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Projects</h3>
                <p class="text-gray-600 text-sm mb-4">View student project details</p>
                <a href="hodviewproject.php"
                    class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    View Details
                </a>
            </div>
        </div>

        <!-- Courses Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-book-open text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Courses</h3>
                <p class="text-gray-600 text-sm mb-4">View MOOC course details</p>
                <a href="hodview_mooc.php"
                    class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    View Details
                </a>
            </div>
        </div>

        <!-- Upload Files Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-upload text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Files</h3>
                <p class="text-gray-600 text-sm mb-4">Upload important documents</p>
                <a href="uploadFile.php"
                    class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    Upload Now
                </a>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="mb-10 mt-10">
        <h2 class="text-2xl text-center font-bold text-gray-800 mb-6">Student Submissions Analytics</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Students Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Students</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $totalStudents; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Internship Submissions Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Internship Submissions</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $internshipSubmitted; ?> <span
                                    class="text-sm font-normal text-gray-500">(<?php echo $internshipPercent; ?>%)</span>
                            </h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-briefcase text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $internshipPercent; ?>%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Submissions Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Project Submissions</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $projectSubmitted; ?> <span
                                    class="text-sm font-normal text-gray-500">(<?php echo $projectPercent; ?>%)</span>
                            </h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-project-diagram text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $projectPercent; ?>%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- MOOC Submissions Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500">MOOC Submissions</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $moocSubmitted; ?> <span
                                    class="text-sm font-normal text-gray-500">(<?php echo $moocPercent; ?>%)</span></h3>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-book-open text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: <?php echo $moocPercent; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Bar Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Submissions Overview</h3>
                <canvas id="submissionsBarChart" height="250"></canvas>
            </div>

            <!-- Doughnut Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Submission Completion Rates</h3>
                <canvas id="completionDoughnutChart" height="250"></canvas>
            </div>
        </div>
    </div>


</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Bar Chart
    const barCtx = document.getElementById('submissionsBarChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Internship', 'Projects', 'MOOC Courses'],
            datasets: [{
                label: 'Submitted',
                data: [<?php echo $internshipSubmitted; ?>, <?php echo $projectSubmitted; ?>, <?php echo $moocSubmitted; ?>],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(249, 115, 22, 0.7)'
                ],
                borderColor: [
                    'rgba(16, 185, 129, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(249, 115, 22, 1)'
                ],
                borderWidth: 1
            }, {
                label: 'Remaining',
                data: [
                    <?php echo $totalStudents - $internshipSubmitted; ?>,
                    <?php echo $totalStudents - $projectSubmitted; ?>,
                    <?php echo $totalStudents - $moocSubmitted; ?>
                ],
                backgroundColor: 'rgba(209, 213, 219, 0.7)',
                borderColor: 'rgba(156, 163, 175, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function (context) {
                            const datasetIndex = context.datasetIndex;
                            const dataIndex = context.dataIndex;
                            const total = <?php echo $totalStudents; ?>;
                            const value = context.dataset.data[dataIndex];
                            const percentage = Math.round((value / total) * 100);
                            return `Percentage: ${percentage}%`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Students'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Doughnut Chart
    const doughnutCtx = document.getElementById('completionDoughnutChart').getContext('2d');
    const doughnutChart = new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Internship', 'Projects', 'MOOC Courses', 'Not Submitted'],
            datasets: [{
                data: [
                    <?php echo $internshipPercent; ?>,
                    <?php echo $projectPercent; ?>,
                    <?php echo $moocPercent; ?>,
                    <?php echo 100 - max($internshipPercent, $projectPercent, $moocPercent); ?>
                ],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(249, 115, 22, 0.7)',
                    'rgba(209, 213, 219, 0.7)'
                ],
                borderColor: [
                    'rgba(16, 185, 129, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(249, 115, 22, 1)',
                    'rgba(156, 163, 175, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return `${label}: ${value}%`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
</script>

</body>

</html>