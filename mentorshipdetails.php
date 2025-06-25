<?php
require_once 'config.php';
require_once 'ftheader.php';

// Initialize variables for the mentorship details
$usn = '';
$student_name = '';
$email = '';
$phone = '';
$career = '';
$hobbies = [];
$achievements = [];
$internal_marks = [];

// Check if the USN is passed as a query parameter
if (isset($_GET['usn'])) {
    $usn = $conn->real_escape_string($_GET['usn']);

    // Query to fetch details for the selected USN
    $sql = "SELECT * FROM mentor_form WHERE usn = '$usn'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student_name = $row['student_name'];
        $email = $row['email'];
        $phone = $row['phone'];
        $career = $row['career'];

        // Fetch hobbies
        $hobbies_stmt = $conn->prepare("SELECT hobby FROM student_hobbies WHERE usn = ?");
        $hobbies_stmt->bind_param("s", $usn);
        $hobbies_stmt->execute();
        $hobbies_result = $hobbies_stmt->get_result();
        while ($hobby_row = $hobbies_result->fetch_assoc()) {
            $hobbies[] = $hobby_row['hobby'];
        }
        $hobbies_stmt->close();

        // Fetch achievements
        $achievements_stmt = $conn->prepare("SELECT achievement FROM student_achievements WHERE usn = ?");
        $achievements_stmt->bind_param("s", $usn);
        $achievements_stmt->execute();
        $achievements_result = $achievements_stmt->get_result();
        while ($achievement_row = $achievements_result->fetch_assoc()) {
            $achievements[] = $achievement_row['achievement'];
        }
        $achievements_stmt->close();

        // Fetch internal marks
        $marks_stmt = $conn->prepare("SELECT * FROM internal_marks WHERE usn = ? ORDER BY semester, internal_number");
        $marks_stmt->bind_param("s", $usn);
        $marks_stmt->execute();
        $marks_result = $marks_stmt->get_result();
        while ($mark_row = $marks_result->fetch_assoc()) {
            $internal_marks[$mark_row['semester']][$mark_row['internal_number']][] = $mark_row;
        }
        $marks_stmt->close();
    } else {
        echo "<p class='text-red-500'>No details found for the selected USN.</p>";
    }
} else {
    echo "<p class='text-red-500'>Invalid access. No USN provided.</p>";
    exit;
}

$conn->close();
if (!isset($_SESSION['selected_mentorship_data'])) {
    $_SESSION['selected_mentorship_data'] = [];
}
?>
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentorship Details - <?php echo htmlspecialchars($student_name); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Screen Styles */
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-item {
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        .info-value {
            padding: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.9375rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #eff6ff;
            font-weight: 600;
            color: #1e40af;
        }

        ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin: 0.5rem 0;
        }

        li {
            margin-bottom: 0.25rem;
        }

        .print-btn, .safe-back-btn {
            background-color: #1e40af;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .print-btn:hover, .safe-back-btn:hover {
            background-color: #1e3a8a;
        }

        .print-btn i {
            margin-right: 0.5rem;
        }

        /* Print Styles */
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }

            body,
            html {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-size: 12pt;
            }

            /* Hide all elements that shouldn't print */
            .no-print,
            header,
            footer,
            nav,
            .print-btn,
            .safe-back-btn {
                display: none !important;
            }

            /* Reset main content area */
            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            /* Remove card styling */
            .card {
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 0 0.5cm 0 !important;
                page-break-inside: avoid;
            }

            /* Section styling */
            .section-title {
                font-size: 14pt;
                font-weight: bold;
                color: black !important;
                border-bottom: 1px solid #000;
                padding-bottom: 0.2cm;
                margin: 0.5cm 0 0.3cm 0 !important;
            }

            /* Table styling */
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 0.3cm 0 0.5cm 0;
                page-break-inside: avoid;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 0.2cm;
                font-size: 11pt;
            }

            th {
                background-color: #f0f0f0 !important;
            }

            /* List styling */
            ul {
                padding-left: 1.2cm;
                margin: 0.2cm 0;
            }

            li {
                font-size: 11pt;
                margin-bottom: 0.1cm;
            }

            /* Info grid styling */
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.3cm;
                margin: 0.3cm 0;
            }

            .info-label {
                font-weight: bold;
                font-size: 11pt;
            }

            .info-value {
                border: 1px solid #ddd !important;
                padding: 0.2cm;
                margin-bottom: 0.2cm;
            }

            /* Force page breaks between semesters */
            .semester-section {
                page-break-after: avoid;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-400">
    <main>
        <div class="card no-print">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-user-friends mr-2 text-blue-600"></i> Mentorship Details
                </h1>
                <div>
                    <a href="ftmentorship.php" class="safe-back-btn bg">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button onclick="window.print()" class="print-btn ml-4">
                        <i class="fas fa-print"></i> Print Record
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Student Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <h2 class="section-title">
                            <i class="fas fa-user-graduate"></i> Student Information
                        </h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">USN</div>
                                <div class="info-value"><?php echo htmlspecialchars($usn); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student_name); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($phone); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Aspirations -->
                    <div>
                        <h2 class="section-title">
                            <i class="fas fa-bullseye"></i> Career Aspirations
                        </h2>
                        <div class="info-value min-h-20">
                            <?php echo nl2br(htmlspecialchars($career)); ?>
                        </div>
                    </div>
                </div>

                <!-- Hobbies and Achievements -->
                <div class="space-y-4">
                    <!-- Hobbies -->
                    <div>
                        <h2 class="section-title">
                            <i class="fas fa-paint-brush"></i> Hobbies
                        </h2>
                        <div class="info-value min-h-20">
                            <?php if (!empty($hobbies)): ?>
                                <ul>
                                    <?php foreach ($hobbies as $hobby): ?>
                                        <li><?php echo htmlspecialchars($hobby); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No hobbies listed</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Achievements -->
                    <div>
                        <h2 class="section-title">
                            <i class="fas fa-trophy"></i> Achievements
                        </h2>
                        <div class="info-value min-h-20">
                            <?php if (!empty($achievements)): ?>
                                <ul>
                                    <?php foreach ($achievements as $achievement): ?>
                                        <li><?php echo htmlspecialchars($achievement); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No achievements listed</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Internal Marks -->
            <div class="mt-8">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i> Internal Marks
                </h2>
                <div class="space-y-6 semester-section">
                    <?php foreach ($internal_marks as $semester => $internals): ?>
                        <div>
                            <h3 class="font-medium text-lg mb-2">Semester <?php echo $semester; ?></h3>
                            <?php foreach ([1, 2] as $internalNum): ?>
                                <?php if (isset($internals[$internalNum])): ?>
                                    <h4 class="font-medium text-gray-700 mt-4 mb-2">Internal <?php echo $internalNum; ?></h4>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Subject Code</th>
                                                <th>Marks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($internals[$internalNum] as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['subject_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['marks']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Set document title before printing
        function prepareForPrint() {
            document.title = "Mentorship Record - <?php echo htmlspecialchars($student_name); ?> (<?php echo htmlspecialchars($usn); ?>)";
            return true;
        }

        window.onbeforeprint = prepareForPrint;

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>