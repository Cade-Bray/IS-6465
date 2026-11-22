<?php
/**
 * Data Access Layer: Tutoring Request Repository
 * IS 6465 Lab 7
 */

function insertTutoringRequest(PDO $pdo, int $studentId, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $studentId,
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time'],
    ]);
    return (int)$pdo->lastInsertId();
}

function findRequest(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, s.student_name, s.student_email, s.user_id
         FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE r.request_id = ?'
    );
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    return $request ?: null;
}

function findRequestForStudent(PDO $pdo, int $id, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, s.student_name, s.student_email, s.user_id
         FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE r.request_id = ? AND s.user_id = ?'
    );
    $stmt->execute([$id, $userId]);
    $request = $stmt->fetch();
    return $request ?: null;
}

function listRequests(PDO $pdo, string $course = '', string $status = ''): array
{
    $sql = 'SELECT r.*, s.student_name, s.student_email
            FROM tutoring_requests r
            JOIN students s ON r.student_id = s.student_id
            WHERE 1=1';
    $args = [];

    if ($course !== '') {
        $sql .= ' AND r.course = ?';
        $args[] = $course;
    }
    if ($status !== '') {
        $sql .= ' AND r.status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY r.request_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function listRequestsForStudent(PDO $pdo, int $userId, string $course = '', string $status = ''): array
{
    $sql = 'SELECT r.*, s.student_name, s.student_email
            FROM tutoring_requests r
            JOIN students s ON r.student_id = s.student_id
            WHERE s.user_id = ?';
    $args = [$userId];

    if ($course !== '') {
        $sql .= ' AND r.course = ?';
        $args[] = $course;
    }
    if ($status !== '') {
        $sql .= ' AND r.status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY r.request_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function updateOpenRequest(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET course = ?, topic = ?, preferred_date = ?, preferred_time = ?
         WHERE request_id = ? AND status = 'Open'"
    );
    $stmt->execute([
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time'],
        $id,
    ]);
    return $stmt->rowCount() === 1;
}

function updateOpenRequestForStudent(PDO $pdo, int $id, int $userId, array $data): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET course = ?, topic = ?, preferred_date = ?, preferred_time = ?
         WHERE request_id = ? AND status = 'Open'
           AND student_id IN (SELECT student_id FROM students WHERE user_id = ?)"
    );
    $stmt->execute([
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time'],
        $id,
        $userId,
    ]);
    return $stmt->rowCount() === 1;
}

function cancelOpenRequest(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET status = 'Cancelled'
         WHERE request_id = ? AND status = 'Open'"
    );
    $stmt->execute([$id]);
    return $stmt->rowCount() === 1;
}

function cancelOpenRequestForStudent(PDO $pdo, int $id, int $userId): bool
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET status = 'Cancelled'
         WHERE request_id = ? AND status = 'Open'
           AND student_id IN (SELECT student_id FROM students WHERE user_id = ?)"
    );
    $stmt->execute([$id, $userId]);
    return $stmt->rowCount() === 1;
}

function countRequestsForStudentUser(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE s.user_id = ?'
    );
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
