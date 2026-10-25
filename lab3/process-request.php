<?php

echo "Hello from the server\n";

if ($_SERVER["REQUEST_METHOD"] !== "POST"){
    http_response_code(405);
    exit("Method not allowed\n");
}

// This is the enumerated values from the post request
$course = trim($_POST["course"] ?? "");
$topic = trim($_POST["topic"] ?? "");
$preferredTime = $_POST["preferredtime"] ?? "";
$preferredDate = $_POST["preferreddate"] ?? "";

// This is the allowed values for santizing.
$errors = [];
$allowedCourses = ["IS 6465", "IS 6495", "IS 6570"];
$allowedTimes = ["Morning", "Afternoon", "Evening"];

// Check data for business rule adherence to reject malformed requests
if (!in_array($course, $allowedCourses, true)) {
    $errors["course"] = "Select a valid course.";
}

if (!in_array($preferredTime, $allowedTimes, true)) {
    $errors["preferred_time"] = "Select a valid preferred time.";
}

if ($topic === "" || mb_strlen($topic) > 120) {
    $errors["topic"] = "Enter a topic of 1 to 120 characters.";
}

// Prepare data for comparison and then check it
$date = DateTime::createFromFormat("!Y-m-d", $preferredDate); // changed format to set time to midnight
$now = (new DateTime())->setTime(0, 0, 0); // set the time to midnight
$validDate = $date && $date->format("Y-m-d") === $preferredDate;
if (!$validDate || $date < $now) { // date format and past time check
    $errors["preferred_date"] = "Enter a valid preferred date.";
}

// If there are errors let's send them back.
if (!$errors) {
    echo "<h1>Request received</h1>";
    echo "<p>Course: " . htmlspecialchars($course, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Topic: " . htmlspecialchars($topic, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>Status: Pending review</p>";
}

?>