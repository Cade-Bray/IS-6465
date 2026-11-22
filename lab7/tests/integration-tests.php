<?php
/**
 * Automated CLI Integration Test Suite for Peer Tutoring Hub Lab 7
 * Verifies UM-01 through UM-10 and protected Tutoring Request workflows
 * IS 6465 Lab 7
 */

require_once dirname(__DIR__) . '/includes/auth.php';
startSecureSession();

require_once dirname(__DIR__) . '/services/userService.php';
require_once dirname(__DIR__) . '/services/requestService.php';

// Initialize isolated in-memory SQLite database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON');

// Load schema and seeds from setup SQL
$sql = file_get_contents(dirname(__DIR__) . '/database/setup-database.sql');
$pdo->exec($sql);

$passed = 0;
$failed = 0;

function check(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: $label" . PHP_EOL;
    } else {
        $failed++;
        echo "FAIL: $label" . PHP_EOL;
    }
}

echo "=== IS 6465 Lab 7: Automated Integration Test Suite ===" . PHP_EOL;

// ---------------------------------------------------------
// 1. Database Schema & Seed Verification
// ---------------------------------------------------------
$admin = findUserByEmail($pdo, 'admin@example.edu');
check($admin !== null && $admin['role'] === 'Administrator' && $admin['status'] === 'Active', 'Seed: Admin account loaded');

$maria = findUserByEmail($pdo, 'maria.lopez@example.com');
check($maria !== null && $maria['role'] === 'Student' && $maria['status'] === 'Active', 'Seed: Student Maria loaded');

$tutor = findUserByEmail($pdo, 'tutor@example.edu');
check($tutor !== null && $tutor['role'] === 'Tutor' && $tutor['status'] === 'Active', 'Seed: Tutor Taylor loaded');

$inactive = findUserByEmail($pdo, 'inactive@example.edu');
check($inactive !== null && $inactive['status'] === 'Inactive', 'Seed: Inactive account loaded');

// ---------------------------------------------------------
// 2. UM-01: Create User Account (Registration)
// ---------------------------------------------------------
// Valid Student Registration
$studentReg = [
    'full_name' => 'Charlie Brown',
    'email' => 'charlie@example.edu',
    'password' => 'Peanuts123!',
    'confirm_password' => 'Peanuts123!',
    'role' => 'Student',
];
$charlieId = registerUser($pdo, $studentReg);
$charlie = findUserById($pdo, $charlieId);
check($charlie !== null && $charlie['role'] === 'Student' && $charlie['status'] === 'Active', 'UM-01: Student self-registration creates Active user');
check(password_verify('Peanuts123!', $charlie['password_hash']), 'UM-01: Stored password hash verifies correctly');

// Check that student record was automatically linked in students table
$charlieStudent = findStudentByUserId($pdo, $charlieId);
check($charlieStudent !== null && $charlieStudent['student_email'] === 'charlie@example.edu', 'UM-01: Student registration links students table row');

// Valid Tutor Registration
$tutorReg = [
    'full_name' => 'Linus Van Pelt',
    'email' => 'linus@example.edu',
    'password' => 'Blanket123!',
    'confirm_password' => 'Blanket123!',
    'role' => 'Tutor',
];
$linusId = registerUser($pdo, $tutorReg);
$linus = findUserById($pdo, $linusId);
check($linus !== null && $linus['role'] === 'Tutor' && $linus['status'] === 'Active', 'UM-01: Tutor self-registration creates Active user');

// Reject Duplicate Email
try {
    registerUser($pdo, $studentReg);
    check(false, 'UM-01: Duplicate email rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Duplicate email rejected');
}

// Reject Self-Registration with Administrator Role
$adminAttempt = [
    'full_name' => 'Hacker Admin',
    'email' => 'hacker@example.edu',
    'password' => 'HackPass123!',
    'confirm_password' => 'HackPass123!',
    'role' => 'Administrator',
];
try {
    registerUser($pdo, $adminAttempt);
    check(false, 'UM-01: Public registration cannot grant Administrator role');
} catch (InvalidArgumentException $e) {
    check(str_contains($e->getMessage(), 'Administrator'), 'UM-01: Public registration cannot grant Administrator role');
}

// Reject Password Mismatch
$mismatchReg = [
    'full_name' => 'Lucy Van Pelt',
    'email' => 'lucy@example.edu',
    'password' => 'Football123!',
    'confirm_password' => 'Mismatch123!',
    'role' => 'Student',
];
try {
    registerUser($pdo, $mismatchReg);
    check(false, 'UM-01: Password mismatch rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Password mismatch rejected');
}

// Reject Short Password
$shortReg = [
    'full_name' => 'Snoopy Dog',
    'email' => 'snoopy@example.edu',
    'password' => 'short',
    'confirm_password' => 'short',
    'role' => 'Student',
];
try {
    registerUser($pdo, $shortReg);
    check(false, 'UM-01: Short password rejected (min 8 chars)');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-01: Short password rejected (min 8 chars)');
}

// ---------------------------------------------------------
// 3. UM-09: Log In (Authentication)
// ---------------------------------------------------------
// Valid Login
$authCharlie = authenticateUser($pdo, 'charlie@example.edu', 'Peanuts123!');
check($authCharlie !== null && (int)$authCharlie['user_id'] === $charlieId, 'UM-09: Valid credentials authenticate successfully');

// Case-insensitive email login
$authCaps = authenticateUser($pdo, 'CHARLIE@EXAMPLE.EDU', 'Peanuts123!');
check($authCaps !== null && (int)$authCaps['user_id'] === $charlieId, 'UM-09: Email login is case-insensitive');

// Invalid Password
$badPass = authenticateUser($pdo, 'charlie@example.edu', 'WrongPass123!');
check($badPass === null, 'UM-09: Invalid password returns null');

// Nonexistent Email
$badEmail = authenticateUser($pdo, 'nobody@example.edu', 'Peanuts123!');
check($badEmail === null, 'UM-09: Unknown email returns null');

// Inactive Account Blocked from Login
$badInactive = authenticateUser($pdo, 'inactive@example.edu', 'StudentPass123!');
check($badInactive === null, 'UM-09: Inactive user account is denied login');

// ---------------------------------------------------------
// 4. UM-02: View Own Account
// ---------------------------------------------------------
$profile = findUserById($pdo, $charlieId);
check(
    $profile['full_name'] === 'Charlie Brown' &&
    $profile['email'] === 'charlie@example.edu' &&
    $profile['role'] === 'Student' &&
    $profile['status'] === 'Active',
    'UM-02: Own profile returns accurate name, email, role, and status'
);

// ---------------------------------------------------------
// 5. UM-04: Update Own Account
// ---------------------------------------------------------
updateProfile($pdo, $charlieId, [
    'full_name' => 'Charlie M. Brown',
    'email' => 'charlie.brown@example.edu',
]);
$updatedCharlie = findUserById($pdo, $charlieId);
check($updatedCharlie['full_name'] === 'Charlie M. Brown' && $updatedCharlie['email'] === 'charlie.brown@example.edu', 'UM-04: Profile name and email updated');

// Verify student table was synchronized
$syncedStudent = findStudentByUserId($pdo, $charlieId);
check($syncedStudent['student_name'] === 'Charlie M. Brown' && $syncedStudent['student_email'] === 'charlie.brown@example.edu', 'UM-04: Student record synchronized with profile update');

// Reject duplicate email on update
try {
    updateProfile($pdo, $charlieId, [
        'full_name' => 'Charlie M. Brown',
        'email' => 'admin@example.edu', // belongs to Admin Alice
    ]);
    check(false, 'UM-04: Colliding email update rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-04: Colliding email update rejected');
}

// ---------------------------------------------------------
// 6. UM-05: Change Password
// ---------------------------------------------------------
// Successful Password Change
changePassword($pdo, $charlieId, 'Peanuts123!', 'NewKite2026!', 'NewKite2026!');
check(authenticateUser($pdo, 'charlie.brown@example.edu', 'NewKite2026!') !== null, 'UM-05: New password authenticates');
check(authenticateUser($pdo, 'charlie.brown@example.edu', 'Peanuts123!') === null, 'UM-05: Old password rejected after change');

// Wrong Current Password Rejected
try {
    changePassword($pdo, $charlieId, 'WrongCurrentPass!', 'ThirdPass123!', 'ThirdPass123!');
    check(false, 'UM-05: Wrong current password rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-05: Wrong current password rejected');
}

// New Password Confirmation Mismatch Rejected
try {
    changePassword($pdo, $charlieId, 'NewKite2026!', 'PassA123!', 'PassB123!');
    check(false, 'UM-05: New password mismatch rejected');
} catch (InvalidArgumentException $e) {
    check(true, 'UM-05: New password mismatch rejected');
}

// ---------------------------------------------------------
// 7. UM-03: View and Search User Accounts (Administrator)
// ---------------------------------------------------------
$allUsers = listUsers($pdo);
check(count($allUsers) >= 6, 'UM-03: Admin list returns all users');

$searchMaria = listUsers($pdo, 'Maria');
check(count($searchMaria) === 1 && $searchMaria[0]['email'] === 'maria.lopez@example.com', 'UM-03: Search by keyword returns matching user');

$tutorsOnly = listUsers($pdo, '', 'Tutor');
check(count($tutorsOnly) >= 2, 'UM-03: Filter by Tutor role returns tutors');

$inactiveOnly = listUsers($pdo, '', '', 'Inactive');
check(count($inactiveOnly) === 1 && $inactiveOnly[0]['email'] === 'inactive@example.edu', 'UM-03: Filter by Inactive status returns inactive accounts');

$emptySearch = listUsers($pdo, 'NonExistentUserXYZ');
check(empty($emptySearch), 'UM-03: Empty search returns empty array');

// ---------------------------------------------------------
// 8. UM-06: Update User Role (Administrator)
// ---------------------------------------------------------
$adminId = (int)$admin['user_id'];
changeUserRole($pdo, $linusId, 'Student', $adminId);
$linusUpdated = findUserById($pdo, $linusId);
check($linusUpdated['role'] === 'Student', 'UM-06: Admin can update user role');

// Rule check: Demoting the only active administrator is prohibited
check(countActiveAdmins($pdo) === 1, 'Precondition: Exactly one active administrator exists');
try {
    changeUserRole($pdo, $adminId, 'Tutor', $adminId);
    check(false, 'UM-06: Demoting sole active administrator is blocked');
} catch (DomainException $e) {
    check(str_contains($e->getMessage(), 'active administrator'), 'UM-06: Demoting sole active administrator is blocked');
}

// ---------------------------------------------------------
// 9. UM-07: Activate or Deactivate Account (Administrator)
// ---------------------------------------------------------
// Deactivate Linus
changeUserStatus($pdo, $linusId, 'Inactive', $adminId);
$linusDeactivated = findUserById($pdo, $linusId);
check($linusDeactivated['status'] === 'Inactive', 'UM-07: Admin can deactivate an account');
check(authenticateUser($pdo, 'linus@example.edu', 'Blanket123!') === null, 'UM-07: Deactivated user cannot authenticate');

// Reactivate Linus
changeUserStatus($pdo, $linusId, 'Active', $adminId);
$linusReactivated = findUserById($pdo, $linusId);
check($linusReactivated['status'] === 'Active', 'UM-07: Admin can reactivate an account');
check(authenticateUser($pdo, 'linus@example.edu', 'Blanket123!') !== null, 'UM-07: Reactivated user can authenticate');

// Rule check: Deactivating the sole active administrator is prohibited
try {
    changeUserStatus($pdo, $adminId, 'Inactive', $adminId);
    check(false, 'UM-07: Deactivating sole active administrator is blocked');
} catch (DomainException $e) {
    check(str_contains($e->getMessage(), 'active administrator'), 'UM-07: Deactivating sole active administrator is blocked');
}

// ---------------------------------------------------------
// 10. Protected Tutoring Request Workflows
// ---------------------------------------------------------
// Student Charlie Brown submits a tutoring request
$sampleRequest = [
    'student_name' => 'Charlie M. Brown',
    'student_email' => 'charlie.brown@example.edu',
    'course' => 'IS 6465',
    'reason' => 'Need help understanding PDO transactions and role authorization.',
    'topics' => ['Exam review', 'Study plan'],
    'preferreddate' => (new DateTimeImmutable('+5 days'))->format('Y-m-d'),
    'preferredtime' => 'Morning',
];

$charlieReqId = submitRequest($pdo, $sampleRequest, $charlieId);
check($charlieReqId > 0, 'Tutoring: Student can submit request linked to user_id');

$req = findRequestForStudent($pdo, $charlieReqId, $charlieId);
check($req !== null && $req['status'] === 'Open', 'Tutoring: Student can view their own Open request');
check(decodeTopic($req['topic'])['topics'] === ['Exam review', 'Study plan'], 'Tutoring: Checkbox topic values preserved in JSON');

// Ownership check: Student Maria Lopez CANNOT view Charlie Brown's request
$mariaId = (int)$maria['user_id'];
$mariaViewCharlie = findRequestForStudent($pdo, $charlieReqId, $mariaId);
check($mariaViewCharlie === null, 'Tutoring: Ownership rule prevents student from viewing peer request');

// Administrator CAN view any request
$adminViewCharlie = findRequest($pdo, $charlieReqId);
check($adminViewCharlie !== null && (int)$adminViewCharlie['request_id'] === $charlieReqId, 'Tutoring: Administrator can view any request');

// Student Charlie Brown edits own Open request
$editRequest = $sampleRequest;
$editRequest['reason'] = 'Updated request reason: assistance with session security.';
check(reviseRequest($pdo, $charlieReqId, $editRequest, $charlieId, false), 'Tutoring: Student can edit own Open request');

$updatedReq = findRequestForStudent($pdo, $charlieReqId, $charlieId);
check(decodeTopic($updatedReq['topic'])['reason'] === $editRequest['reason'], 'Tutoring: Revised reason persisted');

// Ownership check: Student Maria Lopez CANNOT edit Charlie Brown's request
check(!reviseRequest($pdo, $charlieReqId, $editRequest, $mariaId, false), 'Tutoring: Peer student cannot edit another student request');

// Student cancels own Open request
check(cancelRequest($pdo, $charlieReqId, $charlieId, false), 'Tutoring: Student can cancel own Open request');
$cancelledReq = findRequestForStudent($pdo, $charlieReqId, $charlieId);
check($cancelledReq['status'] === 'Cancelled', 'Tutoring: Request status updated to Cancelled');

// Cancelled row is retained in SQLite
$countTotal = (int)$pdo->query('SELECT COUNT(*) FROM tutoring_requests WHERE request_id = ' . $charlieReqId)->fetchColumn();
check($countTotal === 1, 'Tutoring: Cancelled request row is retained in database');

// Re-cancelling or editing a cancelled request is rejected
check(!cancelRequest($pdo, $charlieReqId, $charlieId, false), 'Tutoring: Second cancellation on cancelled request rejected');
check(!reviseRequest($pdo, $charlieReqId, $editRequest, $charlieId, false), 'Tutoring: Editing a Cancelled request rejected');

// ---------------------------------------------------------
// 11. UM-08: Delete User Account (Administrator)
// ---------------------------------------------------------
// User with tutoring requests CANNOT be deleted (Data Retention Policy)
check(hasTutoringRequests($pdo, $charlieId), 'Precondition: Charlie Brown has associated tutoring request records');
try {
    deleteUserAccount($pdo, $charlieId, $adminId);
    check(false, 'UM-08: Deletion of user with tutoring requests blocked by retention policy');
} catch (DomainException $e) {
    check(str_contains($e->getMessage(), 'retention policy'), 'UM-08: Deletion of user with tutoring requests blocked by retention policy');
}

// Deleting self (current administrator) is blocked
try {
    deleteUserAccount($pdo, $adminId, $adminId);
    check(false, 'UM-08: Admin deleting own account is blocked');
} catch (DomainException $e) {
    check(str_contains($e->getMessage(), 'own active account'), 'UM-08: Admin deleting own account is blocked');
}

// User without tutoring requests CAN be deleted
check(!hasTutoringRequests($pdo, $linusId), 'Precondition: Linus has no tutoring requests');
deleteUserAccount($pdo, $linusId, $adminId);
check(findUserById($pdo, $linusId) === null, 'UM-08: User without tutoring requests can be deleted');

// ---------------------------------------------------------
// 12. Security Controls: Foreign Keys & CSRF
// ---------------------------------------------------------
// SQLite foreign keys enforced
try {
    $pdo->exec("INSERT INTO tutoring_requests(student_id, course, topic, preferred_date, preferred_time) VALUES(99999, 'IS 6465', 'test', '2026-12-01', 'Morning')");
    check(false, 'Security: SQLite foreign keys enforced');
} catch (PDOException $e) {
    check(true, 'Security: SQLite foreign keys enforced');
}

// CSRF token generation
$token1 = csrfToken();
$token2 = csrfToken();
check(strlen($token1) === 64 && $token1 === $token2, 'Security: CSRF token generated and consistent within session');

echo PHP_EOL . "=== Test Results ===" . PHP_EOL;
echo "Passed: $passed" . PHP_EOL;
echo "Failed: $failed" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
