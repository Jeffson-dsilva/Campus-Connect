<?php
// Common ClassOps sidebar
// Expects:
// - $isFaculty (bool)
// - $userClasses (array of classes with class_id, title)

// Use robust relative links to dashboards from classops directory
$campusConnectHref = $isFaculty ? '../dashboards/ftDashboard.php' : '../dashboards/stDashboard.php';
?>
<aside id="sidebar"
    class="fixed top-14 left-0 h-[calc(100%-3.5rem)] bg-white shadow-lg transition-all duration-300 z-40 w-64 overflow-hidden">
    <nav class="p-4 text-gray-800 space-y-1 text-lg">
        <!-- Home -->
        <a href="classops_dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
            <i class="fas fa-home w-5 text-gray-600 text-2xl"></i>
            <span class="menu-text">Home</span>
        </a>

        <!-- Enrolled/My Classes -->
        <div>
            <button id="enrolledToggle"
                class="w-full flex items-center justify-between px-4 py-2 rounded hover:bg-[#e8f0fe] focus:outline-none">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-book w-5 text-gray-600 text-2xl"></i>
                    <span class="menu-text"><?php echo $isFaculty ? 'My Classes' : 'Enrolled Courses'; ?></span>
                </div>
                <i id="enrolledArrow" class="fas fa-chevron-down text-xs text-gray-600"></i>
            </button>
            <div id="enrolledMenu" class="ml-8 mt-1 space-y-1 hidden">
                <?php if (!empty($userClasses)): ?>
                    <?php foreach ($userClasses as $c): ?>
                        <a href="class_view.php?id=<?php echo $c['class_id']; ?>"
                            class="block px-2 py-1 rounded hover:bg-[#e8f0fe] text-sm"><?php echo htmlspecialchars($c['title']); ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments -->
        <a href="student_assignments.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
            <i class="fas fa-tasks w-5 text-gray-600 text-2xl"></i>
            <span class="menu-text">Assignments</span>
        </a>

        <!-- Campus Connect -->
        <a href="<?php echo $campusConnectHref; ?>" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
            <i class="fas fa-university w-5 text-gray-600 text-2xl"></i>
            <span class="menu-text">Campus Connect</span>
        </a>
    </nav>
</aside>

