<?php
/**
 * Business Service Layer: Tutoring Request Workflows
 * IS 6465 Lab 7
 */

require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';
require_once dirname(__DIR__) . '/repositories/requestPDO.php';

function escapeHtml($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function decodeTopic($topic): array
{
    $v = json_decode((string)$topic, true);
    if (is_array($v) && isset($v['reason']) && is_array($v['topics'] ?? null)) {
        return $v;
    }
    return ['reason' => (string)$topic, 'topics' => []];
}

function validateSubmission($input, $requireStudent = true): array
{
    $errors = [];
    $courses = ['IS 6465', 'IS 6495', 'IS 6570'];

    $name = trim((string)($input['student_name'] ?? ''));
    $email = trim((string)($input['student_email'] ?? ''));
    $course = trim((string)($input['course'] ?? ''));
    $reason = trim((string)($input['reason'] ?? ''));
    $date = (string)($input['preferreddate'] ?? '');
    $time = (string)($input['preferredtime'] ?? '');
    $topics = $input['topics'] ?? [];

    if (!is_array($topics)) {
        $errors[] = 'Topics must be a list.';
        $topics = [];
    }

    if ($requireStudent && $name === '') {
        $errors[] = 'Student name is required.';
    }
    if ($requireStudent && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid student email required.';
    }
    if (!in_array($course, $courses, true)) {
        $errors[] = 'Select a valid course.';
    }
    if ($msg = validatePreferredTime($time)) {
        $errors[] = $msg;
    }
    if ($msg = validateTopics($topics)) {
        $errors[] = $msg;
    }
    if ($msg = validateReason($reason)) {
        $errors[] = $msg;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $dt < new DateTimeImmutable('today')) {
        $errors[] = 'Choose a valid date today or later.';
    }

    $payload = [
        'student_name' => $name,
        'student_email' => $email,
        'course' => $course,
        'topic' => json_encode(['reason' => $reason, 'topics' => array_values($topics)]),
        'preferred_date' => $date,
        'preferred_time' => $time,
    ];

    return [$errors, $payload];
}

function submitRequest(PDO $pdo, array $input, ?int $userId = null): int
{
    [$errors, $data] = validateSubmission($input, true);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $pdo->beginTransaction();
    try {
        if ($userId !== null) {
            $studentId = linkStudentToUser($pdo, $userId, $data['student_name'], $data['student_email']);
        } else {
            insertStudentIfAbsent($pdo, $data['student_name'], $data['student_email']);
            $student = findStudentByEmail($pdo, $data['student_email']);
            if (!$student) {
                throw new RuntimeException('Student lookup failed.');
            }
            $studentId = (int)$student['student_id'];
        }

        $id = insertTutoringRequest($pdo, $studentId, $data);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function reviseRequest(PDO $pdo, int $id, array $input, ?int $userId = null, bool $isAdmin = false): bool
{
    [$errors, $data] = validateSubmission($input, false);
    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    if ($isAdmin) {
        return updateOpenRequest($pdo, $id, $data);
    }

    if ($userId !== null) {
        return updateOpenRequestForStudent($pdo, $id, $userId, $data);
    }

    return updateOpenRequest($pdo, $id, $data);
}

function cancelRequest(PDO $pdo, int $id, ?int $userId = null, bool $isAdmin = false): bool
{
    if ($isAdmin) {
        return cancelOpenRequest($pdo, $id);
    }

    if ($userId !== null) {
        return cancelOpenRequestForStudent($pdo, $id, $userId);
    }

    return cancelOpenRequest($pdo, $id);
}
