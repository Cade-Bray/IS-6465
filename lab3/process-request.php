<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST"){
    http_response_code(405);
    exit("Method not allowed\n");
}

// This is the enumerated values from the post request
$course = trim($_POST["course"] ?? "");
$topic = trim($_POST["topic"] ?? "");
$preferredTime = trim($_POST["preferredtime"] ?? "");
$preferredDate = trim($_POST["preferreddate"] ?? "");

// This is the allowed values for santizing.
$errors = [];
$allowedCourses = ["IS 6465", "IS 6495", "IS 6570"];
$allowedTimes = ["Morning", "Afternoon", "Evening"];

// Check data for business rule adherence to reject malformed requests
if (!in_array($course, $allowedCourses, true)) {
    $errors["Course"] = "Select a valid course.";
}

if (!in_array($preferredTime, $allowedTimes, true)) {
    $errors["Preferred Time"] = "Select a valid preferred time.";
}

if ($topic === "" || mb_strlen($topic) > 120) {
    $errors["Topic"] = "Enter a topic of 1 to 120 characters.";
}

// Prepare data for comparison and then check it
$date = DateTime::createFromFormat("!Y-m-d", $preferredDate); // changed format to set time to midnight
$now = (new DateTime())->setTime(0, 0, 0); // set the time to midnight
$validDate = $date && $date->format("Y-m-d") === $preferredDate;
if (!$validDate) { // date format and past time check
    $errors["Preferred Date"] = "Enter a valid preferred date.";
} elseif ($date < $now) {
    $errors["Preferred Date"] = "Enter a date that is not in the past.";
}

if (!$errors) {
    // If there are no errors then provide the request details.
    echo "<h1>Request received</h1>\n";
    echo "<p>Course: " . htmlspecialchars($course, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Topic: " . htmlspecialchars($topic, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Preferred date: " . htmlspecialchars($preferredDate, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Preferred time: " . htmlspecialchars($preferredTime, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Status: Pending review</p>";
} else {
    // Sending back errors
    echo "<h1>Request Errors</h1>";
    echo "<h4> Please fix the following values</h4>";
    foreach ($errors as $error => $error_value){
        echo "<p>" . htmlspecialchars($error, ENT_QUOTES, "UTF-8") . ": " 
        . htmlspecialchars($error_value, ENT_QUOTES, "UTF-8") . "</p>";
    }
    echo "<p><strong>Status: Request declined.</strong></p>";
}

?>