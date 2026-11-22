<?php
/**
 * Data Access Layer: Student Repository
 * IS 6465 Lab 7
 */

function findStudentByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email, user_id FROM students WHERE LOWER(student_email) = LOWER(?)');
    $stmt->execute([trim($email)]);
    $student = $stmt->fetch();
    return $student ?: null;
}

function findStudentByUserId(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT student_id, student_name, student_email, user_id FROM students WHERE user_id = ?');
    $stmt->execute([$userId]);
    $student = $stmt->fetch();
    return $student ?: null;
}

function insertStudentIfAbsent(PDO $pdo, string $name, string $email, ?int $userId = null): void
{
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO students (student_name, student_email, user_id) VALUES (?, ?, ?)');
    $stmt->execute([trim($name), strtolower(trim($email)), $userId]);
}

function linkStudentToUser(PDO $pdo, int $userId, string $name, string $email): int
{
    $name = trim($name);
    $email = strtolower(trim($email));

    // Check if student with user_id already exists
    $existing = findStudentByUserId($pdo, $userId);
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE students SET student_name = ?, student_email = ? WHERE user_id = ?');
        $stmt->execute([$name, $email, $userId]);
        return (int)$existing['student_id'];
    }

    // Check if student with same email exists
    $byEmail = findStudentByEmail($pdo, $email);
    if ($byEmail) {
        $stmt = $pdo->prepare('UPDATE students SET user_id = ?, student_name = ? WHERE student_id = ?');
        $stmt->execute([$userId, $name, $byEmail['student_id']]);
        return (int)$byEmail['student_id'];
    }

    // Insert new student linked to user
    $stmt = $pdo->prepare('INSERT INTO students (student_name, student_email, user_id) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $userId]);
    return (int)$pdo->lastInsertId();
}

function updateStudentProfile(PDO $pdo, int $userId, string $name, string $email): bool
{
    $stmt = $pdo->prepare('UPDATE students SET student_name = ?, student_email = ? WHERE user_id = ?');
    return $stmt->execute([trim($name), strtolower(trim($email)), $userId]);
}

function deleteStudentByUserId(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('DELETE FROM students WHERE user_id = ?');
    return $stmt->execute([$userId]);
}
