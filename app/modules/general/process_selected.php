<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['selected_usn'])) {
        echo "Selected USNs: <br>";
        foreach ($_POST['selected_usn'] as $usn) {
            echo htmlspecialchars($usn) . "<br>";
        }
    } else {
        echo "No rows selected.";
    }
}
?>
