<?php
/**
 * Business Service Layer: User Management
 * Handles UM-01 through UM-10 workflows, validations, and business rules
 * IS 6465 Lab 7
 */

require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/repositories/userPDO.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';

function registerUser(PDO $pdo, array $input): int
{
    $fullName = trim((string)($input['full_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? '');
    $role = trim((string)($input['role'] ?? ''));

    $errors = [];

    if ($msg = validateFullName($fullName)) {
        $errors[] = $msg;
    }

    if ($msg = validateEmailAddress($email)) {
        $errors[] = $msg;
    }

    // Public registration role check: Only Student or Tutor allowed. Administrator strictly denied.
    if ($role === 'Administrator') {
        $errors[] = 'Public registration cannot grant Administrator privileges.';
    } elseif (!in_array($role, ['Student', 'Tutor'], true)) {
        $errors[] = 'Please select a valid account role (Student or Tutor).';
    }

    if ($msg = validatePasswordStrength($password)) {
        $errors[] = $msg;
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    // Email uniqueness check
    if (findUserByEmail($pdo, $email)) {
        throw new InvalidArgumentException('An account with this email address already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        $userId = insertUser($pdo, $fullName, $email, $passwordHash, $role, 'Active');

        // If the registered user is a Student, create/link student profile record
        if ($role === 'Student') {
            linkStudentToUser($pdo, $userId, $fullName, $email);
        }

        $pdo->commit();
        return $userId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function authenticateUser(PDO $pdo, string $email, string $password): ?array
{
    $user = findUserByEmail($pdo, $email);

    // Generic error path prevents account enumeration
    if (!$user || $user['status'] !== 'Active' || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function updateProfile(PDO $pdo, int $userId, array $input): void
{
    $fullName = trim((string)($input['full_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));

    $errors = [];

    if ($msg = validateFullName($fullName)) {
        $errors[] = $msg;
    }

    if ($msg = validateEmailAddress($email)) {
        $errors[] = $msg;
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    // Verify target user exists
    $user = findUserById($pdo, $userId);
    if (!$user) {
        throw new RuntimeException('User account not found.');
    }

    // Check duplicate email
    if (isEmailTakenByOtherUser($pdo, $email, $userId)) {
        throw new InvalidArgumentException('The submitted email address is already in use by another account.');
    }

    $pdo->beginTransaction();
    try {
        updateUserProfile($pdo, $userId, $fullName, $email);

        // If user is a Student, synchronize students table record
        if ($user['role'] === 'Student') {
            updateStudentProfile($pdo, $userId, $fullName, $email);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function changePassword(PDO $pdo, int $userId, string $currentPassword, string $newPassword, string $confirmPassword): void
{
    $user = findUserById($pdo, $userId);
    if (!$user) {
        throw new RuntimeException('User account not found.');
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }

    $errors = [];
    if ($msg = validatePasswordStrength($newPassword)) {
        $errors[] = $msg;
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password confirmation does not match.';
    }

    if ($errors) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    updateUserPassword($pdo, $userId, $newHash);
}

function changeUserRole(PDO $pdo, int $targetUserId, string $newRole, int $adminUserId): void
{
    $targetUser = findUserById($pdo, $targetUserId);
    if (!$targetUser) {
        throw new RuntimeException('Target user account not found.');
    }

    if (!in_array($newRole, ['Student', 'Tutor', 'Administrator'], true)) {
        throw new InvalidArgumentException('Invalid role specified.');
    }

    // Business rule: Prevent removing or demoting the last active administrator
    if ($targetUser['role'] === 'Administrator' && $newRole !== 'Administrator' && $targetUser['status'] === 'Active') {
        if (countActiveAdmins($pdo) <= 1) {
            throw new DomainException('Cannot change role: the system must retain at least one active administrator.');
        }
    }

    $pdo->beginTransaction();
    try {
        updateUserRole($pdo, $targetUserId, $newRole);

        // If newly assigned Student role, ensure student record exists
        if ($newRole === 'Student') {
            linkStudentToUser($pdo, $targetUserId, $targetUser['full_name'], $targetUser['email']);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function changeUserStatus(PDO $pdo, int $targetUserId, string $newStatus, int $adminUserId): void
{
    $targetUser = findUserById($pdo, $targetUserId);
    if (!$targetUser) {
        throw new RuntimeException('Target user account not found.');
    }

    if (!in_array($newStatus, ['Active', 'Inactive'], true)) {
        throw new InvalidArgumentException('Invalid status specified.');
    }

    // Business rule: Prevent deactivating the last active administrator
    if ($targetUser['role'] === 'Administrator' && $newStatus === 'Inactive' && $targetUser['status'] === 'Active') {
        if (countActiveAdmins($pdo) <= 1) {
            throw new DomainException('Cannot deactivate: the system must retain at least one active administrator.');
        }
    }

    updateUserStatus($pdo, $targetUserId, $newStatus);
}

function deleteUserAccount(PDO $pdo, int $targetUserId, int $adminUserId): void
{
    $targetUser = findUserById($pdo, $targetUserId);
    if (!$targetUser) {
        throw new RuntimeException('Target user account not found.');
    }

    // Business rule: Administrators cannot delete their own active account
    if ($targetUserId === $adminUserId) {
        throw new DomainException('Administrators cannot delete their own active account.');
    }

    // Business rule: Prevent deleting the last active administrator
    if ($targetUser['role'] === 'Administrator' && $targetUser['status'] === 'Active') {
        if (countActiveAdmins($pdo) <= 1) {
            throw new DomainException('Cannot delete: the system must retain at least one active administrator.');
        }
    }

    // Business rule: Check related tutoring requests under approved retention policy
    if (hasTutoringRequests($pdo, $targetUserId)) {
        throw new DomainException(
            'Account cannot be deleted because related tutoring request records exist under the approved data retention policy. You may deactivate the account instead.'
        );
    }

    $pdo->beginTransaction();
    try {
        // Remove linked student record if present
        deleteStudentByUserId($pdo, $targetUserId);
        deleteUser($pdo, $targetUserId);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

