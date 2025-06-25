<?php
require_once 'config.php';
$title = "Faculty Dashboard";
require_once 'ftheader.php';
?>

<div class="container mx-auto mt-6 px-4 py-8 md:py-12 pb-20">
    <!-- Welcome Header -->
    <div class="text-center mb-8 md:mb-12">
        <h1 class="text-2xl md:text-3xl font-bold text-[#0452a5] mb-2">Welcome, <?php echo explode(' ', $facultyName)[1]; ?>!</h1>
        <p class="text-gray-600">Faculty Portal - St. Joseph Engineering College</p>
    </div>

    <!-- Dashboard Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <!-- Internship Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-briefcase text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Internship</h3>
                <p class="text-gray-600 text-sm mb-4">View student internship details</p>
                <a href="ftviewinternship.php" class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
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
                <a href="ftviewproject.php" class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
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
                <a href="ftviewmooc.php" class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    View Details
                </a>
            </div>
        </div>

        <!-- Mentorship Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-user-friends text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Mentorship</h3>
                <p class="text-gray-600 text-sm mb-4">View mentorship details</p>
                <a href="ftviewmentorship.php" class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    View Details
                </a>
            </div>
        </div>

        <!-- Class Ops Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 flex flex-col items-center text-center">
                <div class="bg-[#e3f2fd] p-4 rounded-full mb-4">
                    <i class="fas fa-chalkboard-teacher text-[#0452a5] text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Class Ops</h3>
                <p class="text-gray-600 text-sm mb-4">Class operations portal</p>
                <a href="classops_dashboard.php" class="w-full bg-blue-600 hover:bg-green-500 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-300">
                    Access Portal
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>