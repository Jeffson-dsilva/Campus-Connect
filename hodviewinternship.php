<?php
require_once 'config.php';
require_once 'hodheader.php';

// Get HOD's department code
$hodDept = $_SESSION['dept'];

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "AND (i.name LIKE ? OR i.usn LIKE ?)";
    $search_param = "%$search%";
    $search_params[] = $search_param;
    $search_params[] = $search_param;
}

// Get total number of records
$total_records_query = "SELECT COUNT(*) FROM internship i 
                       JOIN students s ON i.usn = s.usn 
                       WHERE s.dept_code = ? $search_condition";
$stmt = $conn->prepare($total_records_query);

if (!empty($search)) {
    $stmt->bind_param("sss", $hodDept, $search_param, $search_param);
} else {
    $stmt->bind_param("s", $hodDept);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);

// Main query
$sql = "SELECT i.name, i.usn FROM internship i 
        JOIN students s ON i.usn = s.usn 
        WHERE s.dept_code = ? $search_condition 
        ORDER BY i.usn ASC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $stmt->bind_param("sssii", $hodDept, $search_param, $search_param, $offset, $records_per_page);
} else {
    $stmt->bind_param("sii", $hodDept, $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();

$title = "Internship Details";
?>

<!-- Rest of your HTML remains exactly the same -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-50">
    <main class="mx-auto px-4 py-8 lg:w-3/4 xl:w-3/4 2xl:w-3/4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Internship Records</h2>
                        <p class="text-blue-100 text-sm mt-1">View all student internship details</p>
                    </div>
                    <div class="flex items-center gap-4 w-full sm:w-auto">
                        <!-- Search Form -->
                        <form method="GET" action="" class="flex-1 sm:flex-none">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                    placeholder="Search by name or USN..." 
                                    aria-label="Search">
                                <?php if (!empty($search)) : ?>
                                    <a href="hodviewinternship.php" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        <div class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                            <?php echo $total_records; ?> records
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <?php if ($result->num_rows > 0) : ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        USN
                                        <span class="ml-1 text-blue-600">
                                            <i class="fas fa-sort-up"></i>
                                        </span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $result->fetch_assoc()) : ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-medium"><?php echo substr(htmlspecialchars($row["usn"]), -3); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row["usn"]); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($row["name"]); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form action="hodview_details.php" method="GET" class="inline">
                                                <input type="hidden" name="usn" value="<?php echo htmlspecialchars($row["usn"]); ?>">
                                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <i class="fas fa-eye mr-1"></i> View
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="text-center py-12">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <i class="fas fa-folder-open text-gray-400"></i>
                        </div>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">
                            <?php echo empty($search) ? 'No records found' : 'No matching records'; ?>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <?php echo empty($search) ? 'There are currently no internship records available.' : 'Try a different search term.'; ?>
                        </p>
                        <?php if (!empty($search)) : ?>
                            <a href="hodviewinternship.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-undo mr-2"></i> Clear search
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card Footer with Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between">
                        <div class="text-sm text-gray-500 mb-4 sm:mb-0">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                        </div>
                        
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <!-- Previous Page Link -->
                            <a href="?page=<?php echo $page > 1 ? $page - 1 : 1; ?>&search=<?php echo urlencode($search); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            
                            <!-- Page Numbers -->
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="?page=1&search='.urlencode($search).'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active = $i == $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50';
                                echo "<a href=\"?page=$i&search=".urlencode($search)."\" class=\"relative inline-flex items-center px-4 py-2 border text-sm font-medium $active\">$i</a>";
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                echo "<a href=\"?page=$total_pages&search=".urlencode($search)."\" class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50\">$total_pages</a>";
                            }
                            ?>
                            
                            <!-- Next Page Link -->
                            <a href="?page=<?php echo $page < $total_pages ? $page + 1 : $total_pages; ?>&search=<?php echo urlencode($search); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>