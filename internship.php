<?php
require_once 'config.php';
$title = "Internship Details";
require_once 'header.php';

// Fetch user data from session
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$email = $_SESSION['email'];
$query = "SELECT name, usn FROM students WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userName, $usn);
$stmt->fetch();
$stmt->close();

if (!$userName || !$usn) {
  header("Location: login.php");
  exit();
}

// Check if student has already submitted internship details
$checkQuery = "SELECT id FROM internship WHERE usn = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("s", $usn);
$checkStmt->execute();
$checkStmt->store_result();
$hasSubmitted = $checkStmt->num_rows > 0;
$checkStmt->close();

$submitted = false; // Initialize submission flag

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // If already submitted, prevent resubmission
  if ($hasSubmitted) {
    echo "<script>alert('You have already submitted internship details.');</script>";
  } else {
    try {
      // Validate and fetch form data
      $internName = trim($_POST['name']);
      $usn = trim($_POST['usn']);
      $role = trim($_POST['role']);
      $phone = trim($_POST['phone']);
      $location = trim($_POST['location']);
      $start_date = trim($_POST['start-date']);
      $end_date = trim($_POST['end-date']);
      $languages_used = trim($_POST['working-on']);

      // Handle file upload
      $certificateContent = null;
      if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $certificateContent = file_get_contents($_FILES['certificate']['tmp_name']);
      }

      $sql = "INSERT INTO internship (name, usn, role, phone, location, start_date, end_date, languages_used, certificate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param(
        "sssssssss",
        $internName,
        $usn,
        $role,
        $phone,
        $location,
        $start_date,
        $end_date,
        $languages_used,
        $certificateContent
      );

      if ($stmt->execute()) {
        $submitted = true;
        $hasSubmitted = true; // Update flag after successful submission
      } else {
        throw new Exception("Database Error: " . $conn->error);
      }
    } catch (mysqli_sql_exception $e) {
      // Handle duplicate entry error (code 1062)
      if ($e->getCode() == 1062) {
        echo "<script>alert('You have already submitted internship details.');</script>";
        $hasSubmitted = true;
      } else {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
      }
    } catch (Exception $e) {
      echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    } finally {
      if (isset($stmt)) {
        $stmt->close();
      }
    }
  }
}
?>


<main class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-6xl mx-auto">
    <!-- Form Card with Glass Morphism Effect -->
    <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl overflow-hidden border border-white/20">
      <!-- Form Header with Gradient -->
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-full mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">Internship Details</h1>
        <p class="text-blue-100 font-medium">Share your professional experience with us</p>
      </div>

      <!-- Form Content -->
      <div class="p-8 md:p-10">
        <?php if ($hasSubmitted): ?>
          <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                  fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-700">
                  You have already submitted your internship details. You cannot submit again.
                </p>
              </div>
            </div>
          </div>
          <div class="text-center mt-6">
            <a href="stDashboard.php"
              class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
              Return to Dashboard
            </a>
          </div>
        <?php else: ?>
          <form id="applicationForm" action="internship.php" method="POST" enctype="multipart/form-data"
            onsubmit="return checkForm(event)" class="space-y-6">
            <!-- Personal Information Section -->
            <div class="space-y-6">
              <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Personal Information</h2>

              <!-- Name Field -->
              <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span
                    class="text-red-500">*</span></label>
                <div class="relative">
                  <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($userName); ?>"
                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg bg-gray-100">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- USN Field -->
              <div class="space-y-2">
                <label for="usn" class="block text-sm font-medium text-gray-700">USN <span
                    class="text-red-500">*</span></label>
                <div class="relative">
                  <input id="usn" name="usn" type="text" value="<?php echo htmlspecialchars($usn); ?>"
                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg bg-gray-100">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Internship Details Section -->
              <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Internship Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Role Field -->
                  <div class="space-y-2">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role <span
                        class="text-red-500">*</span></label>
                    <div class="relative">
                      <input id="role" name="role" type="text" placeholder="Enter your Internship Role"
                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                      </div>
                    </div>
                    <p class="mt-1 text-sm text-red-600 hidden" id="roleError"></p>
                  </div>

                  <!-- Phone Field -->
                  <div class="space-y-2">
                    <label for="phone" class="block text-sm font-medium text-gray-700">Contact No <span
                        class="text-red-500">*</span></label>
                    <div class="relative">
                      <input id="phone" name="phone" type="tel" placeholder="Enter company Contanct number"
                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                      </div>
                    </div>
                    <p class="mt-1 text-sm text-red-600 hidden" id="phoneError"></p>
                  </div>
                </div>

                <!-- Location Field -->
                <div class="space-y-2">
                  <label for="location" class="block text-sm font-medium text-gray-700">Location <span
                      class="text-red-500">*</span></label>
                  <div class="relative">
                    <input id="location" name="location" type="text" placeholder="Enter company location"
                      class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                    </div>
                  </div>
                  <p class="mt-1 text-sm text-red-600 hidden" id="locationError"></p>
                </div>

                <!-- Date Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date <span
                        class="text-red-500">*</span></label>
                    <div class="relative">
                      <input id="start-date" name="start-date" type="date"
                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                    </div>
                    <p class="mt-1 text-sm text-red-600 hidden" id="startDateError"></p>
                  </div>
                  <div class="space-y-2">
                    <label for="end-date" class="block text-sm font-medium text-gray-700">End Date <span
                        class="text-red-500">*</span></label>
                    <div class="relative">
                      <input id="end-date" name="end-date" type="date"
                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                    </div>
                    <p class="mt-1 text-sm text-red-600 hidden" id="endDateError"></p>
                  </div>
                </div>

                <!-- Languages Field -->
                <div class="space-y-2">
                  <label for="working-on" class="block text-sm font-medium text-gray-700">Languages Used <span
                      class="text-red-500">*</span></label>
                  <div class="relative">
                    <input id="working-on" name="working-on" type="text" placeholder="Enter languages used"
                      class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                      </svg>
                    </div>
                  </div>
                  <p class="mt-1 text-sm text-red-600 hidden" id="workingOnError"></p>
                </div>

                <!-- Certificate Field -->
                <div class="space-y-2">
                  <label for="certificate" class="block text-sm font-medium text-gray-700">Certificate <span
                      class="text-red-500">*</span></label>
                  <div class="relative">
                    <input id="certificate" name="certificate" type="file" accept=".pdf,.doc,.docx,.jpg,.png"
                      onchange="validateFile(this)"
                      class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, DOC, JPG, PNG (Max 200KB)</p>
                  <p class="mt-1 text-sm text-red-600 hidden" id="file-error"></p>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="pt-6">
                <button type="submit"
                  class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 transform hover:scale-[1.01] focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                  Submit Internship Details
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline ml-2 -mr-1" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                      d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<!-- Success Modal -->
<?php if ($submitted): ?>
  <div id="successModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-md w-full animate-zoom-in">
      <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">Success!</h2>
        <p class="text-green-100">Your internship details have been submitted</p>
      </div>
      <div class="p-6 text-center">
        <p class="text-gray-600 mb-6">Thank you for sharing your internship experience with us.</p>
        <button onclick="closeModal()"
          class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
          Return to Dashboard
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  // File validation for certificate upload
  function validateFile(input) {
    const file = input.files[0];
    const fileError = document.getElementById('file-error');
    const validTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const maxSize = 200 * 1024; // 200KB

    if (file) {
      const fileExtension = file.name.split('.').pop().toLowerCase();
      if (!validTypes.includes(fileExtension)) {
        fileError.textContent = 'Invalid file type. Please upload a valid file (PDF, DOC, DOCX, JPG, PNG).';
        fileError.classList.remove('hidden');
        input.value = '';
      } else if (file.size > maxSize) {
        fileError.textContent = 'File size exceeds the maximum limit of 200KB.';
        fileError.classList.remove('hidden');
        input.value = '';
      } else {
        fileError.textContent = '';
        fileError.classList.add('hidden');
      }
    }
  }

  // Form validation function
  function checkForm(event) {
    let isValid = true;

    // Clear previous errors
    document.querySelectorAll('[id$="Error"]').forEach(function (el) {
      el.textContent = '';
      el.classList.add('hidden');
    });

    // Name validation
    const name = document.getElementById('name').value.trim();
    if (name === '') {
      isValid = false;
      document.getElementById('nameError').textContent = 'Name is required.';
      document.getElementById('nameError').classList.remove('hidden');
    }

    // USN validation
    const usn = document.getElementById('usn').value.trim();
    if (usn === '') {
      isValid = false;
      document.getElementById('usnError').textContent = 'USN is required.';
      document.getElementById('usnError').classList.remove('hidden');
    }

    // Role validation
    const role = document.getElementById('role').value.trim();
    if (role === '') {
      isValid = false;
      document.getElementById('roleError').textContent = 'Role is required.';
      document.getElementById('roleError').classList.remove('hidden');
    }

    // Phone number validation
    const phone = document.getElementById('phone').value.trim();
    const phoneRegex = /^[0-9]{10}$/;
    if (phone === '') {
      document.getElementById('phoneError').textContent = 'Contact number is required.';
      document.getElementById('phoneError').classList.remove('hidden');
      isValid = false;
    } else if (!phoneRegex.test(phone)) {
      document.getElementById('phoneError').textContent = 'Enter a valid 10-digit phone number.';
      document.getElementById('phoneError').classList.remove('hidden');
      isValid = false;
    }

    // Location validation
    const location = document.getElementById('location').value.trim();
    if (location === '') {
      isValid = false;
      document.getElementById('locationError').textContent = 'Location is required.';
      document.getElementById('locationError').classList.remove('hidden');
    }

    // Start date validation
    const startDate = document.getElementById('start-date').value.trim();
    if (startDate === '') {
      document.getElementById('startDateError').textContent = 'Start date is required.';
      document.getElementById('startDateError').classList.remove('hidden');
      isValid = false;
    }

    // End date validation
    const endDate = document.getElementById('end-date').value.trim();
    if (endDate === '') {
      document.getElementById('endDateError').textContent = 'End date is required.';
      document.getElementById('endDateError').classList.remove('hidden');
      isValid = false;
    } else if (startDate && new Date(startDate) > new Date(endDate)) {
      document.getElementById('endDateError').textContent = 'End date must be after start date.';
      document.getElementById('endDateError').classList.remove('hidden');
      isValid = false;
    }

    // Working on languages validation
    const languagesUsed = document.getElementById('working-on').value.trim();
    if (languagesUsed === '') {
      isValid = false;
      document.getElementById('workingOnError').textContent = 'Languages used is required.';
      document.getElementById('workingOnError').classList.remove('hidden');
    }

    // File validation
    const certificate = document.getElementById('certificate').files[0];
    if (!certificate) {
      isValid = false;
      document.getElementById('file-error').textContent = 'Certificate file is required.';
      document.getElementById('file-error').classList.remove('hidden');
    }

    if (!isValid) {
      event.preventDefault();
    }

    return isValid;
  }

  function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    window.location.href = 'stDashboard.php';
  }
</script>
<style>
  @keyframes zoom-in {
    0% {
      transform: scale(0.95);
      opacity: 0;
    }

    100% {
      transform: scale(1);
      opacity: 1;
    }
  }

  .animate-zoom-in {
    animation: zoom-in 0.3s ease-out forwards;
  }
</style>


</body>

</html>