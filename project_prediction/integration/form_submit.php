<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description']
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents('http://localhost:5001/predict', false, $context);
    $response = json_decode($result, true);

    echo "<h3>🧠 Project Acceptance Chance: " . $response['acceptance_probability'] . "%</h3>";
}
?>
<form method="POST">
    <input type="text" name="title" placeholder="Project Title" required><br><br>
    <textarea name="description" placeholder="Project Description" required></textarea><br><br>
    <button type="submit">Check Prediction</button>
</form>
