<?php
/**
 * Automated CLI & Browser Test Suite for User Management (UM-01 through UM-10)
 * Peer Tutoring Hub - IS 6465 Lab 7
 */

require_once dirname(__DIR__) . '/includes/auth.php';
startSecureSession();

require_once dirname(__DIR__) . '/services/userService.php';
require_once dirname(__DIR__) . '/repositories/userPDO.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';

// Initialize isolated in-memory SQLite database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

// Load schema and seeds from setup SQL
$sql = file_get_contents(dirname(__DIR__) . '/database/setup-database.sql');
$pdo->exec($sql);

$isCli = (php_sapi_name() === 'cli');

function check(bool $condition, string $label): void
{
    global $isCli;
    if ($isCli) {
        echo $label . ': ' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
    } else {
        echo '<p>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ': ';
        echo $condition ? '<strong style="color:green">PASS</strong>' : '<strong style="color:red">FAIL</strong>';
        echo '</p>';
    }
}

// ---------------------------------------------------------
// 1. Initial State & Seed Verification
// ---------------------------------------------------------
$admin = findUserByEmail($pdo, 'admin@example.edu');
check($admin !== null && $admin['role'] === 'Administrator' && $admin['status'] === 'Active', 'Seed: Admin account loaded with Active status');

$maria = findUserByEmail($pdo, 'maria.lopez@example.com');
check($maria !== null && $maria['role'] === 'Student' && $maria['status'] === 'Active', 'Seed: Student account loaded with Active status');

$tutor = findUserByEmail($pdo, 'tutor@example.edu');
check($tutor !== null && $tutor['role'] === 'Tutor' && $tutor['status'] === 'Active', 'Seed: Tutor account loaded with Active status');

$inactive = findUserByEmail($pdo, 'inactive@example.edu');
check($inactive !== null && $inactive['status'] === 'Inactive', 'Seed: Inactive account loaded with Inactive status');

// ---------------------------------------------------------
// 2. UM-01: Create User Account (Registration)
// ---------------------------------------------------------
// Positive: Student self-registration
$studentInput = [
    'full_name' => 'Lucy van Pelt',
    'email' => 'lucy@example.edu',
    'password' => 'Football123!',
    'confirm_password' => 'Football123!',
    'role' => 'Student',
];
$lucyId = registerUser($pdo, $studentInput);
$lucy = findUserById($pdo, $lucyId);
check($lucy !== null && $lucy['full_name'] === 'Lucy van Pelt' && $lucy['role'] === 'Student' && $lucy['status'] === 'Active', 'UM-01: Student self-registration creates Active account');
check(password_verify('Football123!', $lucy['password_hash']), 'UM-01: Registered password hash verifies correctly');

// Check automatic linking to students table
$lucyStudent = findStudentByUserId($pdo, $lucyId);
check($lucyStudent !== null && $lucyStudent['student_email'] === 'lucy@example.edu', 'UM-01: Student registration links record in students table');

// Positive: Tutor self-registration
$tutorInput = [
    'full_name' => 'Franklin Armstrong',
    'email' => 'franklin@example.edu',
    'password' => 'Franklin123!',
    'confirm_password' => 'Franklin123!',
    'role' => 'Tutor',
];
$franklinId = registerUser($pdo, $tutorInput);
$franklin = findUserById($pdo, $franklinId);
check($franklin !== null && $franklin['role'] === 'Tutor' && $franklin['status'] === 'Active', 'UM-01: Tutor self-registration creates Active account');

// Negative: Reject duplicate email
try {
    registerUser($pdo, $studentInput);
    check(false, 'UM-01: Duplicate email rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Duplicate email rejected');
}

// Negative: Reject public self-registration with Administrator role
$adminRegInput = [
    'full_name' => 'Rogue Admin',
    'email' => 'rogue@example.edu',
    'password' => 'AdminPass123!',
    'confirm_password' => 'AdminPass123!',
    'role' => 'Administrator',
];
try {
    registerUser($pdo, $adminRegInput);
    check(false, 'UM-01: Public registration cannot grant Administrator role');
} catch (InvalidArgumentException $e) {
    check(strpos($e->getMessage(), 'Administrator') !== false, 'UM-01: Public registration cannot grant Administrator role');
}

// Negative: Reject password confirmation mismatch
$mismatchInput = $studentInput;
$mismatchInput['email'] = 'mismatch@example.edu';
$mismatchInput['confirm_password'] = 'DifferentPass123!';
try {
    registerUser($pdo, $mismatchInput);
    check(false, 'UM-01: Password confirmation mismatch rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Password confirmation mismatch rejected');
}

// Negative: Reject password under 8 characters
$shortPassInput = $studentInput;
$shortPassInput['email'] = 'short@example.edu';
$shortPassInput['password'] = 'short7!';
$shortPassInput['confirm_password'] = 'short7!';
try {
    registerUser($pdo, $shortPassInput);
    check(false, 'UM-01: Password under 8 characters rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Password under 8 characters rejected');
}

// ---------------------------------------------------------
// 3. UM-09: Authenticate User (Login)
// ---------------------------------------------------------
// Positive: Valid credentials authenticate
$authLucy = authenticateUser($pdo, 'lucy@example.edu', 'Football123!');
check($authLucy !== null && (int)$authLucy['user_id'] === $lucyId, 'UM-09: Valid credentials authenticate successfully');

// Positive: Case-insensitive email authentication
$authCase = authenticateUser($pdo, 'LUCY@EXAMPLE.EDU', 'Football123!');
check($authCase !== null && (int)$authCase['user_id'] === $lucyId, 'UM-09: Email login is case-insensitive');

// Negative: Incorrect password returns null
$authBadPass = authenticateUser($pdo, 'lucy@example.edu', 'WrongPass123!');
check($authBadPass === null, 'UM-09: Invalid password returns null');

// Negative: Unknown email returns null
$authUnknown = authenticateUser($pdo, 'nonexistent@example.edu', 'Football123!');
check($authUnknown === null, 'UM-09: Unknown email returns null');

// Negative: Inactive account denied authentication
$authInactive = authenticateUser($pdo, 'inactive@example.edu', 'StudentPass123!');
check($authInactive === null, 'UM-09: Inactive user account is denied authentication');

// ---------------------------------------------------------
// 4. UM-02: View User Profile
// ---------------------------------------------------------
$profile = findUserById($pdo, $lucyId);
check($profile !== null && $profile['full_name'] === 'Lucy van Pelt' && $profile['email'] === 'lucy@example.edu' && $profile['role'] === 'Student', 'UM-02: User profile returns accurate name, email, and role');

$missingProfile = findUserById($pdo, 99999);
check($missingProfile === null, 'UM-02: Non-existent user profile returns null');

// ---------------------------------------------------------
// 5. UM-04: Edit User Profile
// ---------------------------------------------------------
// Positive: Update full name and email
updateProfile($pdo, $lucyId, [
    'full_name' => 'Lucille van Pelt',
    'email' => 'lucille@example.edu',
]);
$updatedLucy = findUserById($pdo, $lucyId);
check($updatedLucy['full_name'] === 'Lucille van Pelt' && $updatedLucy['email'] === 'lucille@example.edu', 'UM-04: Profile name and email updated');

// Positive: Linked student record is synchronized
$syncedStudent = findStudentByUserId($pdo, $lucyId);
check($syncedStudent !== null && $syncedStudent['student_name'] === 'Lucille van Pelt' && $syncedStudent['student_email'] === 'lucille@example.edu', 'UM-04: Linked student record synchronized with profile update');

// Negative: Colliding email update rejected
try {
    updateProfile($pdo, $lucyId, [
        'full_name' => 'Lucille van Pelt',
        'email' => 'franklin@example.edu',
    ]);
    check(false, 'UM-04: Colliding email update rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-04: Colliding email update rejected');
}

// ---------------------------------------------------------
// 6. UM-05: Change Password
// ---------------------------------------------------------
// Positive: Password change succeeds with correct current password
changePassword($pdo, $lucyId, 'Football123!', 'NewPsychHelp123!', 'NewPsychHelp123!');
$authNewPass = authenticateUser($pdo, 'lucille@example.edu', 'NewPsychHelp123!');
check($authNewPass !== null, 'UM-05: New password authenticates successfully');

// Negative: Old password rejected
$authOldPass = authenticateUser($pdo, 'lucille@example.edu', 'Football123!');
check($authOldPass === null, 'UM-05: Old password rejected after change');

// Negative: Wrong current password rejected
try {
    changePassword($pdo, $lucyId, 'WrongOldPass123!', 'AnotherPass123!', 'AnotherPass123!');
    check(false, 'UM-05: Wrong current password rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-05: Wrong current password rejected');
}

// Negative: New password confirmation mismatch rejected
try {
    changePassword($pdo, $lucyId, 'NewPsychHelp123!', 'AnotherPass123!', 'MismatchPass123!');
    check(false, 'UM-05: New password confirmation mismatch rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-05: New password confirmation mismatch rejected');
}

// ---------------------------------------------------------
// 7. UM-03: Search and Filter Users
// ---------------------------------------------------------
$allUsers = listUsers($pdo, '', '', '');
check(count($allUsers) >= 6, 'UM-03: Admin list returns all users');

$searchLucille = listUsers($pdo, 'Lucille', '', '');
check(count($searchLucille) === 1 && $searchLucille[0]['email'] === 'lucille@example.edu', 'UM-03: Search by keyword returns matching user');

$filterTutors = listUsers($pdo, '', 'Tutor', '');
check(count($filterTutors) >= 2, 'UM-03: Filter by Tutor role returns tutors');

$filterInactive = listUsers($pdo, '', '', 'Inactive');
check(count($filterInactive) >= 1 && $filterInactive[0]['status'] === 'Inactive', 'UM-03: Filter by Inactive status returns inactive accounts');

$emptySearch = listUsers($pdo, 'NonExistentStringXYZ', '', '');
check(count($emptySearch) === 0, 'UM-03: Non-matching search returns empty array');

// ---------------------------------------------------------
// 8. UM-06: Assign User Role
// ---------------------------------------------------------
// Positive: Change student to Tutor
changeUserRole($pdo, $lucyId, 'Tutor', 1);
$lucyAsTutor = findUserById($pdo, $lucyId);
check($lucyAsTutor['role'] === 'Tutor', 'UM-06: Administrator can update user role');

// Revert back to Student
changeUserRole($pdo, $lucyId, 'Student', 1);
$lucyReverted = findUserById($pdo, $lucyId);
check($lucyReverted['role'] === 'Student', 'UM-06: Role change can re-assign Student role');

// Negative: Demoting sole active administrator is blocked
check(countActiveAdmins($pdo) === 1, 'Precondition: Exactly one active administrator exists');
try {
    changeUserRole($pdo, 1, 'Student', 1);
    check(false, 'UM-06: Demoting sole active administrator is blocked');
} catch (DomainException $e) {
    check(true, 'UM-06: Demoting sole active administrator is blocked');
}

// ---------------------------------------------------------
// 9. UM-07: Deactivate / Reactivate User Account
// ---------------------------------------------------------
// Positive: Deactivate user account
changeUserStatus($pdo, $lucyId, 'Inactive', 1);
$lucyInactive = findUserById($pdo, $lucyId);
check($lucyInactive['status'] === 'Inactive', 'UM-07: Administrator can deactivate an account');

// Deactivated user cannot authenticate
$authDeactivated = authenticateUser($pdo, 'lucille@example.edu', 'NewPsychHelp123!');
check($authDeactivated === null, 'UM-07: Deactivated user cannot authenticate');

// Positive: Reactivate user account
changeUserStatus($pdo, $lucyId, 'Active', 1);
$lucyReactivated = findUserById($pdo, $lucyId);
check($lucyReactivated['status'] === 'Active', 'UM-07: Administrator can reactivate an account');

// Reactivated user can authenticate
$authReactivated = authenticateUser($pdo, 'lucille@example.edu', 'NewPsychHelp123!');
check($authReactivated !== null, 'UM-07: Reactivated user can authenticate');

// Negative: Deactivating sole active administrator is blocked
try {
    changeUserStatus($pdo, 1, 'Inactive', 1);
    check(false, 'UM-07: Deactivating sole active administrator is blocked');
} catch (DomainException $e) {
    check(true, 'UM-07: Deactivating sole active administrator is blocked');
}

// ---------------------------------------------------------
// 10. UM-08: Delete User Account
// ---------------------------------------------------------
// Negative: Admin deleting own account is blocked
try {
    deleteUserAccount($pdo, 1, 1);
    check(false, 'UM-08: Admin deleting own account is blocked');
} catch (DomainException $e) {
    check(true, 'UM-08: Admin deleting own account is blocked');
}

// Negative: Deletion blocked if tutoring requests exist (Data Retention Policy)
// Create a tutoring request for Maria Lopez (User 4)
$stmt = $pdo->prepare("
    INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time, status)
    VALUES (1, 'IS 6465', '{\"topics\":[\"Exam review\"],\"reason\":\"Need exam review assistance\"}', '2026-12-01', 'Morning', 'Open')
");
$stmt->execute();
check(hasTutoringRequests($pdo, 4), 'Precondition: Maria Lopez has associated tutoring requests');

try {
    deleteUserAccount($pdo, 4, 1);
    check(false, 'UM-08: Deletion of user with tutoring requests blocked by retention policy');
} catch (DomainException $e) {
    check(true, 'UM-08: Deletion of user with tutoring requests blocked by retention policy');
}

// Positive: User without tutoring requests can be deleted
check(!hasTutoringRequests($pdo, $franklinId), 'Precondition: Franklin has no tutoring requests');
deleteUserAccount($pdo, $franklinId, 1);
$deletedFranklin = findUserById($pdo, $franklinId);
check($deletedFranklin === null, 'UM-08: User without tutoring requests can be deleted');

// ---------------------------------------------------------
// 11. UM-10: Security, Sessions & CSRF
// ---------------------------------------------------------
$token = csrfToken();
check(!empty($token) && strlen($token) === 64, 'UM-10: CSRF token is cryptographically secure (64 hex chars)');
check(hash_equals($token, csrfToken()), 'UM-10: CSRF token is consistent within session');

$fkCheck = $pdo->query("PRAGMA foreign_keys")->fetchColumn();
check((int)$fkCheck === 1, 'UM-10: SQLite foreign keys strictly enforced');

