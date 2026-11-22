<?php
/**
 * Validation helpers for Peer Tutoring Hub
 * Preserves Lab 4 input validation and adds User Management validation helpers
 */

function validatePreferredTime($preferredTime): string
{
    $allowedTimes = ['Morning', 'Afternoon', 'Evening'];
    if (!in_array($preferredTime, $allowedTimes, true)) {
        return 'Choose a valid preferred time.';
    }
    return '';
}

function validateTopics($topics): string
{
    $allowedTopics = ['Exam review', 'Practice problems', 'Study plan'];

    if (!is_array($topics) || $topics === []) {
        return 'Select at least one valid topic.';
    }

    foreach ($topics as $topic) {
        if (!in_array($topic, $allowedTopics, true)) {
            return 'Select at least one valid topic.';
        }
    }

    return '';
}

function validateReason($reason): string
{
    $length = mb_strlen(trim((string)$reason));

    if ($length < 10 || $length > 500) {
        return 'Describe your request using 10 to 500 characters.';
    }

    return '';
}

function validatePasswordStrength(string $password): string
{
    if (mb_strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    return '';
}

function validateEmailAddress(string $email): string
{
    $trimmed = trim($email);
    if ($trimmed === '') {
        return 'Email address is required.';
    }
    if (mb_strlen($trimmed) > 254 || !filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    return '';
}

function validateFullName(string $name): string
{
    $trimmed = trim($name);
    if ($trimmed === '') {
        return 'Full name is required.';
    }
    if (mb_strlen($trimmed) > 120) {
        return 'Full name cannot exceed 120 characters.';
    }
    return '';
}
