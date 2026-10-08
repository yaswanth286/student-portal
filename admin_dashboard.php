<?php
/** admin_dashboard.php - protected page: list, filter, open files, edit and delete students. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_role('admin');

$students = db()->query("SELECT * FROM users WHERE role = 'student' ORDER BY id DESC")->fetchAll();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3">Registered Students (<span id="count"><?= count($students) ?></span>)</h4>

<!-- Filters: the table updates while you type (no page reload) -->
<div class="row g-2 mb-3">
    <div class="col-md-3"><input type="text" id="fName" class="form-control" placeholder="Filter by name"></div>
    <div class="col-md-3"><input type="text" id="fClass" class="form-control" placeholder="Filter by class"></div>
    <div class="col-md-2"><input type="number" id="fMinAge" class="form-control" placeholder="Min age" min="0"></div>
    <div class="col-md-2"><input type="number" id="fMaxAge" class="form-control" placeholder="Max age" min="0"></div>
    <div class="col-md-2"><button type="button" id="fClear" class="btn btn-outline-secondary w-100">Clear</button></div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-hover bg-white" id="studentTable">
    <thead class="table-light">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Gender</th><th>Qualification</th>
            <th>Class</th><th>Subject</th><th>Marks</th><th>Aadhaar</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($students as $s): $age = calc_age($s['dob']); ?>
        <tr data-name="<?= e(strtolower($s['name'])) ?>" data-class="<?= e(strtolower($s['class'])) ?>" data-age="<?= $age ?>">
            <td><?= e(student_code((int)$s['id'])) ?></td>
            <td><?= e($s['name']) ?></td>
            <td><?= e($s['email']) ?></td>
            <td><?= $age ?></td>
            <td><?= e($s['gender']) ?></td>
            <td><?= e($s['qualification']) ?></td>
            <td><?= e($s['class']) ?></td>
            <td><?= e($s['subject']) ?></td>
            <td><?= e($s['marks']) ?></td>
            <td><a href="uploads/<?= e($s['aadhaar_file']) ?>" target="_blank">Open PDF</a></td>
            <td class="text-nowrap">
                <a href="admin_edit.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="admin_delete.php" class="d-inline"
                      onsubmit="return confirm('Delete <?= e(addslashes($s['name'])) ?>? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p id="noRows" class="text-center text-muted d-none">No students match your filters.</p>

<script>
// Instant filtering: every keystroke re-checks each table row.
const rows     = document.querySelectorAll('#studentTable tbody tr');
const inputs   = ['fName', 'fClass', 'fMinAge', 'fMaxAge'].map(id => document.getElementById(id));

function applyFilters() {
    const name   = inputs[0].value.trim().toLowerCase();
    const cls    = inputs[1].value.trim().toLowerCase();
    const minAge = inputs[2].value === '' ? null : Number(inputs[2].value);
    const maxAge = inputs[3].value === '' ? null : Number(inputs[3].value);
    let visible = 0;

    rows.forEach(row => {
        const age = Number(row.dataset.age);
        const show =
            row.dataset.name.includes(name) &&
            row.dataset.class.includes(cls) &&
            (minAge === null || age >= minAge) &&
            (maxAge === null || age <= maxAge);
        row.classList.toggle('d-none', !show);
        if (show) visible++;
    });
    document.getElementById('count').textContent = visible;
    document.getElementById('noRows').classList.toggle('d-none', visible > 0);
}

inputs.forEach(input => input.addEventListener('input', applyFilters));
document.getElementById('fClear').addEventListener('click', () => {
    inputs.forEach(input => input.value = '');
    applyFilters();
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
