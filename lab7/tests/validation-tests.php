<?php
/**
 * Input validation unit tests
 * Tests time, topic, reason, full name, email, and password validation
 * IS 6465 Lab 7
 */

require_once dirname(__DIR__) . '/includes/validation.php';

$isCli = (php_sapi_name() === 'cli');

function outputTestResult(string $name, bool $passed): void
{
    global $isCli;
    if ($isCli) {
        echo ($passed ? 'PASS: ' : 'FAIL: ') . $name . PHP_EOL;
    } else {
        echo '<p>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ';
        echo $passed ? '<strong style="color:green">PASS</strong>' : '<strong style="color:red">FAIL</strong>';
        echo '</p>';
    }
}

// 1. validatePreferredTime
$timeTests = [
    ['name' => 'PreferredTime: Valid Morning', 'input' => 'Morning', 'expected' => ''],
    ['name' => 'PreferredTime: Valid Afternoon', 'input' => 'Afternoon', 'expected' => ''],
    ['name' => 'PreferredTime: Valid Evening', 'input' => 'Evening', 'expected' => ''],
    ['name' => 'PreferredTime: Missing time', 'input' => '', 'expected' => 'Choose a valid preferred time.'],
    ['name' => 'PreferredTime: Altered value', 'input' => 'Midnight', 'expected' => 'Choose a valid preferred time.'],
];
foreach ($timeTests as $test) {
    outputTestResult($test['name'], validatePreferredTime($test['input']) === $test['expected']);
}

// 2. validateTopics
$topicTests = [
    ['name' => 'Topics: Multiple valid topics', 'input' => ['Exam review', 'Study plan'], 'expected' => ''],
    ['name' => 'Topics: Single valid topic', 'input' => ['Practice problems'], 'expected' => ''],
    ['name' => 'Topics: Missing topics (empty array)', 'input' => [], 'expected' => 'Select at least one valid topic.'],
    ['name' => 'Topics: Altered topic string', 'input' => ['Free tutoring'], 'expected' => 'Select at least one valid topic.'],
];
foreach ($topicTests as $test) {
    outputTestResult($test['name'], validateTopics($test['input']) === $test['expected']);
}

// 3. validateReason
$reasonTests = [
    ['name' => 'Reason: Minimum length (10 chars)', 'input' => '1234567890', 'expected' => ''],
    ['name' => 'Reason: Maximum length (500 chars)', 'input' => str_repeat('a', 500), 'expected' => ''],
    ['name' => 'Reason: Too short (9 chars)', 'input' => '123456789', 'expected' => 'Describe your request using 10 to 500 characters.'],
    ['name' => 'Reason: Too long (501 chars)', 'input' => str_repeat('a', 501), 'expected' => 'Describe your request using 10 to 500 characters.'],
];
foreach ($reasonTests as $test) {
    outputTestResult($test['name'], validateReason($test['input']) === $test['expected']);
}

// 4. validatePasswordStrength
$passwordTests = [
    ['name' => 'Password: Valid length (8 chars)', 'input' => '12345678', 'expected' => ''],
    ['name' => 'Password: Valid long password', 'input' => 'CorrectHorseBatteryStaple123!', 'expected' => ''],
    ['name' => 'Password: Too short (7 chars)', 'input' => '1234567', 'expected' => 'Password must be at least 8 characters long.'],
    ['name' => 'Password: Empty string', 'input' => '', 'expected' => 'Password must be at least 8 characters long.'],
];
foreach ($passwordTests as $test) {
    outputTestResult($test['name'], validatePasswordStrength($test['input']) === $test['expected']);
}

// 5. validateEmailAddress
$emailTests = [
    ['name' => 'Email: Valid standard email', 'input' => 'student@example.edu', 'expected' => ''],
    ['name' => 'Email: Valid sub-domain email', 'input' => 'alex.morgan@mail.utah.edu', 'expected' => ''],
    ['name' => 'Email: Missing email', 'input' => '', 'expected' => 'Email address is required.'],
    ['name' => 'Email: Invalid format (no @)', 'input' => 'invalid-email', 'expected' => 'Please enter a valid email address.'],
];
foreach ($emailTests as $test) {
    outputTestResult($test['name'], validateEmailAddress($test['input']) === $test['expected']);
}

// 6. validateFullName
$nameTests = [
    ['name' => 'FullName: Valid standard name', 'input' => 'Alex Morgan', 'expected' => ''],
    ['name' => 'FullName: Empty name', 'input' => '', 'expected' => 'Full name is required.'],
    ['name' => 'FullName: Whitespace only', 'input' => '   ', 'expected' => 'Full name is required.'],
    ['name' => 'FullName: Exceeds 120 chars', 'input' => str_repeat('a', 121), 'expected' => 'Full name cannot exceed 120 characters.'],
];
foreach ($nameTests as $test) {
    outputTestResult($test['name'], validateFullName($test['input']) === $test['expected']);
}
