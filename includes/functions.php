<?php
/**
 * functions.php - validation, file upload and the shared student form.
 * Used by signup, student edit and admin edit so the logic lives in ONE place.
 */
require_once __DIR__ . '/../config.php';

const QUALIFICATIONS = ['High School', "Bachelor's", "Master's", 'PhD'];
const INTERESTS      = ['Coding', 'Design', 'Gaming', 'Sports'];
const GENDERS        = ['Male', 'Female', 'Other'];

/**
 * Checks the uploaded Aadhaar file. Returns an error text, or null when OK.
 * We check extension AND real MIME type (a renamed .exe will be rejected).
 */
function check_pdf(array $file): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'Please upload your Aadhaar document (PDF only).';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload failed. Please try again.';
    }
    if ($file['size'] > MAX_PDF_MB * 1024 * 1024) {
        return 'Aadhaar file must be smaller than ' . MAX_PDF_MB . ' MB.';
    }
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($ext !== 'pdf' || $mime !== 'application/pdf') {
        return 'Only PDF files are allowed for the Aadhaar document.';
    }
    return null;
}

/**
 * Saves the PDF with a random name, e.g. 9f2c...e1.pdf
 * The original file name is never used, so two users uploading "aadhaar.pdf" cannot overwrite each other.
 */
function save_aadhaar(array $file): string
{
    $newName = bin2hex(random_bytes(16)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return $newName;
}

function delete_aadhaar(?string $fileName): void
{
    // basename() makes sure we can only ever delete inside /uploads
    if ($fileName && is_file(UPLOAD_DIR . basename($fileName))) {
        unlink(UPLOAD_DIR . basename($fileName));
    }
}

function email_exists(string $email, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
    $stmt->execute([$email, $ignoreId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Validates the student form.
 * $opts: 'is_new'      => true on signup (email + password + file are required)
 *        'check_name'  => false when name is locked (admin edit)
 * Returns an array of error messages (empty array = valid).
 */
function validate_student(array $p, array $file, array $opts = []): array
{
    $errors = [];
    $isNew     = $opts['is_new'] ?? false;
    $checkName = $opts['check_name'] ?? true;

    if ($checkName && trim($p['name'] ?? '') === '') {
        $errors[] = 'Full name is required.';
    }
    if ($isNew) {
        $email = trim($p['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (email_exists($email)) {
            $errors[] = 'This email is already registered.';   // exact message required by the task
        }
        if (strlen($p['password'] ?? '') < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
    }
    if (empty($p['dob']) || $p['dob'] > date('Y-m-d')) {
        $errors[] = 'Please enter a valid date of birth.';
    }
    if (!in_array($p['gender'] ?? '', GENDERS, true)) {
        $errors[] = 'Please select a gender.';
    }
    if (!in_array($p['qualification'] ?? '', QUALIFICATIONS, true)) {
        $errors[] = 'Please select a qualification.';
    }
    if (trim($p['class'] ?? '') === '' || trim($p['subject'] ?? '') === '') {
        $errors[] = 'Class and subject are required.';
    }
    if (!isset($p['marks']) || !ctype_digit((string)$p['marks'])) {
        $errors[] = 'Marks must be a whole number.';
    }
    // File is mandatory on signup; optional (replace only) when editing
    $hasFile = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($isNew || $hasFile) {
        if ($err = check_pdf($file)) {
            $errors[] = $err;
        }
    }
    return $errors;
}

/** Turns the posted interests[] checkboxes into "Coding,Sports" (only allowed values). */
function interests_to_string(array $p): string
{
    $picked = array_intersect($p['interests'] ?? [], INTERESTS);
    return implode(',', $picked);
}

/**
 * Prints the student form fields.
 * $d    = current values (from DB or from a failed POST, so the user does not retype everything)
 * $opts = 'show_credentials' => show email + password inputs (signup)
 *         'lock_name'        => name shown but not editable (admin edit)
 *         'lock_email'       => email shown but not editable (edit pages)
 *         'file_required'    => file input is required (signup)
 */
function student_form_fields(array $d, array $opts = []): void
{
    $lockName  = $opts['lock_name'] ?? false;
    $lockEmail = $opts['lock_email'] ?? false;
    $showPass  = $opts['show_credentials'] ?? false;
    $fileReq   = $opts['file_required'] ?? false;
    // Interests come as an array after a failed POST, or as "Coding,Sports" from the database
    $raw         = $d['interests'] ?? '';
    $myInterests = is_array($raw) ? $raw : array_filter(explode(',', $raw));
    ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="Full Name"
                   value="<?= e($d['name'] ?? '') ?>" <?= $lockName ? 'readonly' : 'required' ?>>
        </div>
        <div class="col-md-4">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="Email Address (email)"
                   value="<?= e($d['email'] ?? '') ?>" <?= $lockEmail ? 'readonly' : 'required' ?>>
        </div>
        <?php if ($showPass): ?>
        <div class="col-md-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Password (min 6 characters)" required>
        </div>
        <?php endif; ?>

        <div class="col-md-4">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" class="form-control" max="<?= date('Y-m-d') ?>"
                   value="<?= e($d['dob'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label d-block">Gender</label>
            <?php foreach (GENDERS as $g): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gender" id="g<?= $g ?>" value="<?= $g ?>"
                        <?= ($d['gender'] ?? '') === $g ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="g<?= $g ?>"><?= $g ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Qualification</label>
            <select name="qualification" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach (QUALIFICATIONS as $q): ?>
                    <option value="<?= e($q) ?>" <?= ($d['qualification'] ?? '') === $q ? 'selected' : '' ?>><?= e($q) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label d-block">Interests</label>
            <?php foreach (INTERESTS as $i): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="interests[]" id="i<?= $i ?>" value="<?= $i ?>"
                        <?= in_array($i, $myInterests, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="i<?= $i ?>"><?= $i ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label">Class</label>
            <input type="text" name="class" class="form-control" placeholder="e.g. 10-A" value="<?= e($d['class'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" placeholder="e.g. Maths" value="<?= e($d['subject'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Marks</label>
            <input type="number" name="marks" min="0" class="form-control" placeholder="No. (number)" value="<?= e($d['marks'] ?? '') ?>" required>
        </div>

        <div class="col-md-8">
            <label class="form-label">Aadhaar Document Upload</label>
            <input type="file" name="aadhaar" class="form-control" accept="application/pdf,.pdf" <?= $fileReq ? 'required' : '' ?>>
            <div class="form-text">
                Upload PDF only (max <?= MAX_PDF_MB ?> MB).
                <?php if (!empty($d['aadhaar_file'])): ?>
                    Current file: <a href="uploads/<?= e($d['aadhaar_file']) ?>" target="_blank">view</a> - choose a new file only if you want to replace it.
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
