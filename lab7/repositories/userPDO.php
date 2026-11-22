<?php
/**
 * Data Access Layer: User Repository
 * IS 6465 Lab 7
 */

function findUserById(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, status, created_at
         FROM users
         WHERE user_id = ?'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function findUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, status, created_at
         FROM users
         WHERE LOWER(email) = LOWER(?)'
    );
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function insertUser(PDO $pdo, string $fullName, string $email, string $passwordHash, string $role, string $status = 'Active'): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, status)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        trim($fullName),
        strtolower(trim($email)),
        $passwordHash,
        $role,
        $status,
    ]);
    return (int)$pdo->lastInsertId();
}

function updateUserProfile(PDO $pdo, int $userId, string $fullName, string $email): bool
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET full_name = ?, email = ?
         WHERE user_id = ?'
    );
    return $stmt->execute([
        trim($fullName),
        strtolower(trim($email)),
        $userId,
    ]);
}

function updateUserPassword(PDO $pdo, int $userId, string $passwordHash): bool
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET password_hash = ?
         WHERE user_id = ?'
    );
    return $stmt->execute([$passwordHash, $userId]);
}

function updateUserRole(PDO $pdo, int $userId, string $role): bool
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET role = ?
         WHERE user_id = ?'
    );
    return $stmt->execute([$role, $userId]);
}

function updateUserStatus(PDO $pdo, int $userId, string $status): bool
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET status = ?
         WHERE user_id = ?'
    );
    return $stmt->execute([$status, $userId]);
}

function deleteUser(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
    return $stmt->execute([$userId]);
}

function listUsers(PDO $pdo, string $search = '', string $role = '', string $status = ''): array
{
    $sql = 'SELECT user_id, full_name, email, role, status, created_at FROM users WHERE 1=1';
    $params = [];

    $trimmedSearch = trim($search);
    if ($trimmedSearch !== '') {
        $sql .= ' AND (full_name LIKE ? OR email LIKE ?)';
        $params[] = '%' . $trimmedSearch . '%';
        $params[] = '%' . $trimmedSearch . '%';
    }

    if ($role !== '' && in_array($role, ['Student', 'Tutor', 'Administrator'], true)) {
        $sql .= ' AND role = ?';
        $params[] = $role;
    }

    if ($status !== '' && in_array($status, ['Active', 'Inactive'], true)) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }

    $sql .= ' ORDER BY user_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countActiveAdmins(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Administrator' AND status = 'Active'");
    return (int)$stmt->fetchColumn();
}

function hasTutoringRequests(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE s.user_id = ?'
    );
    $stmt->execute([$userId]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function isEmailTakenByOtherUser(PDO $pdo, string $email, int $excludeUserId): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM users
         WHERE LOWER(email) = LOWER(?) AND user_id != ?'
    );
    $stmt->execute([trim($email), $excludeUserId]);
    return ((int)$stmt->fetchColumn()) > 0;
}

