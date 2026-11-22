<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$user = requireLogin($pdo);

// Handout Rule: Only Students can submit tutoring requests (Tutors & Admins denied)
requireRole($user, ['Student']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4" style="max-width: 720px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h1 class="h3 mb-0">Request a Tutoring Session</h1>
        </div>
        <div class="card-body p-4">
            <form class="tutoring-request-form" action="process-request.php" method="post" novalidate>
                <input type="hidden" name="form_name" value="tutoring_request">
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="student_name" class="form-label">Student Name</label>
                        <input type="text" class="form-control bg-light" id="student_name" name="student_name" value="<?= escapeHtml($user['full_name']) ?>" readonly required maxlength="120">
                        <div class="form-text">Authenticated student name (read-only).</div>
                    </div>
                    <div class="col-md-6">
                        <label for="student_email" class="form-label">Student Email</label>
                        <input type="email" class="form-control bg-light" id="student_email" name="student_email" value="<?= escapeHtml($user['email']) ?>" readonly required maxlength="254">
                        <div class="form-text">Authenticated student email (read-only).</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="course" class="form-label">Course</label>
                    <select class="form-select" id="course" name="course" style="min-width: 200px;" required>
                        <option value="">Choose a course</option>
                        <option value="IS 6465">IS 6465</option>
                        <option value="IS 6495">IS 6495</option>
                        <option value="IS 6570">IS 6570</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="preferreddate" class="form-label">Preferred Date</label>
                    <input type="date" class="form-control" id="preferreddate" name="preferreddate" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">What would you like help with?</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" minlength="10" maxlength="500" placeholder="Please describe what concepts, assignments, or questions you need assistance with (10-500 characters)..." required></textarea>
                </div>

                <fieldset class="mb-3">
                    <legend class="col-form-label pt-0 fw-semibold">Topics</legend>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="topics[]" id="topic_exam" value="Exam review">
                        <label class="form-check-label" for="topic_exam">Exam review</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="topics[]" id="topic_practice" value="Practice problems">
                        <label class="form-check-label" for="topic_practice">Practice problems</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="topics[]" id="topic_study" value="Study plan">
                        <label class="form-check-label" for="topic_study">Study plan</label>
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="col-form-label pt-0 fw-semibold">Preferred Time</legend>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="preferredtime" id="time_morning" value="Morning" required>
                        <label class="form-check-label" for="time_morning">Morning</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="preferredtime" id="time_afternoon" value="Afternoon">
                        <label class="form-check-label" for="time_afternoon">Afternoon</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="preferredtime" id="time_evening" value="Evening">
                        <label class="form-check-label" for="time_evening">Evening</label>
                    </div>
                </fieldset>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Request Session</button>
                    <a href="requests.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
