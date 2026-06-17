<?php
require_once 'config.php';
requireFacultyLogin();

$page_title = 'My Students';

$faculty = currentFaculty($conn);
$fid = $faculty['faculty_id'];

$filter_class = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$filter_subj  = trim($_GET['subj'] ?? '');

/*
|--------------------------------------------------------------------------
| Faculty Assignments
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        fa.class_id,
        fa.subject_code,
        s.subject_desc,
        c.class_name,
        c.section,
        c.year_level
    FROM faculty_assignments fa
    JOIN subjects s
        ON s.subject_code = fa.subject_code
    JOIN classes c
        ON c.id = fa.class_id
    WHERE fa.faculty_id = ?
    ORDER BY c.class_name, c.section
");
$stmt->bind_param('s', $fid);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/*
|--------------------------------------------------------------------------
| Students Query
|--------------------------------------------------------------------------
*/
if ($filter_class && $filter_subj) {

    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.*,
            c.class_name,
            c.section,
            c.year_level,
            fa.subject_code,
            sub.subject_desc,

            g.prelim,
            g.midterm,
            g.finals,
            g.final_grade,
            g.remarks

        FROM enrollments e

        JOIN students s
            ON s.id_number = e.student_id

        JOIN classes c
            ON c.id = e.class_id

        JOIN faculty_assignments fa
            ON fa.class_id = e.class_id
            AND fa.subject_code = e.subject_code
            AND fa.faculty_id = ?

        JOIN subjects sub
            ON sub.subject_code = e.subject_code

        LEFT JOIN grades g
            ON g.student_id = e.student_id
            AND g.subject_code = e.subject_code
            AND g.class_id = e.class_id

        WHERE e.class_id = ?
        AND e.subject_code = ?

        ORDER BY s.last_name, s.first_name
    ");

    $stmt->bind_param('sis', $fid, $filter_class, $filter_subj);

} else {

    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.*,
            c.class_name,
            c.section,
            c.year_level,
            fa.subject_code,
            sub.subject_desc,

            g.prelim,
            g.midterm,
            g.finals,
            g.final_grade,
            g.remarks

        FROM enrollments e

        JOIN students s
            ON s.id_number = e.student_id

        JOIN classes c
            ON c.id = e.class_id

        JOIN faculty_assignments fa
            ON fa.class_id = e.class_id
            AND fa.subject_code = e.subject_code
            AND fa.faculty_id = ?

        JOIN subjects sub
            ON sub.subject_code = fa.subject_code

        LEFT JOIN grades g
            ON g.student_id = e.student_id
            AND g.subject_code = fa.subject_code
            AND g.class_id = fa.class_id

        ORDER BY s.last_name, s.first_name
    ");

    $stmt->bind_param('s', $fid);
}

$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Students</h1>
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a> /
            <span>Students</span>
        </div>
    </div>

    <span style="
        background:var(--green-bg);
        color:var(--green-text);
        padding:6px 16px;
        border-radius:20px;
        font-size:13px;
        font-weight:700;
    ">
        <?= count($students) ?> Student<?= count($students) != 1 ? 's' : '' ?>
    </span>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label class="form-label">Filter by Class & Subject</label>

            <select name="class" class="form-control" onchange="this.form.submit()">
                <option value="">— All My Students —</option>

                <?php foreach ($assignments as $a): ?>
                    <option
                        value="<?= $a['class_id'] ?>"
                        data-subj="<?= htmlspecialchars($a['subject_code']) ?>"
                        <?= $a['class_id'] == $filter_class ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $a['class_name'] .
                            ' ' .
                            $a['section'] .
                            ' — ' .
                            $a['subject_code']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <input
            type="hidden"
            name="subj"
            id="subjHidden2"
            value="<?= htmlspecialchars($filter_subj) ?>"
        >

        <?php if ($filter_class || $filter_subj): ?>
            <a href="students.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear Filter
            </a>
        <?php endif; ?>

    </form>
</div>

<div class="table-card">

    <div class="table-toolbar">
        <div class="search-wrap" style="flex:1;">
            <i class="fas fa-search"></i>
            <input
                type="text"
                id="studSearch"
                class="search-input"
                placeholder="Search by ID or Name..."
            >
        </div>
    </div>

    <table id="studTable">

        <thead>
            <tr>
                <th>ID Number</th>
                <th>Name</th>
                <th>Class</th>
                <th>Subject</th>
                <th style="text-align:center;">Prelim</th>
                <th style="text-align:center;">Midterm</th>
                <th style="text-align:center;">Finals</th>
                <th style="text-align:center;">Final Grade</th>
                <th style="text-align:center;">Remarks</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>

        <tbody>

        <?php if (empty($students)): ?>

            <tr>
                <td colspan="10" style="text-align:center;padding:30px;">
                    No students found.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($students as $s): ?>

                <?php
                $prelim = $s['prelim'] ?? null;
                $midterm = $s['midterm'] ?? null;
                $finals = $s['finals'] ?? null;
                $fg = $s['final_grade'] ?? null;

                [$ltr, $clr] = $fg !== null
                    ? gradeLetter((float)$fg)
                    : ['—', '#6c757d'];

                $rem = $s['remarks']
                    ?? ($fg !== null
                        ? ($fg >= 75 ? 'Passed' : 'Failed')
                        : '—');

                $rem_cls = $rem === 'Passed'
                    ? 'badge-active'
                    : ($rem === 'Failed'
                        ? 'badge-inactive'
                        : '');
                ?>

                <tr>

                    <td>
                        <strong><?= htmlspecialchars($s['id_number']) ?></strong>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $s['last_name'] . ', ' . $s['first_name']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $s['class_name'] . ' ' . $s['section']
                        ) ?>
                    </td>

                    <td>
                        <strong><?= htmlspecialchars($s['subject_code']) ?></strong>
                        <br>
                        <small style="color:#666;">
                            <?= htmlspecialchars(substr($s['subject_desc'], 0, 30)) ?>
                        </small>
                    </td>

                    <td style="text-align:center;">
                        <?= $prelim !== null
                            ? number_format((float)$prelim, 1)
                            : '<span style="color:#ccc;">—</span>' ?>
                    </td>

                    <td style="text-align:center;">
                        <?= $midterm !== null
                            ? number_format((float)$midterm, 1)
                            : '<span style="color:#ccc;">—</span>' ?>
                    </td>

                    <td style="text-align:center;">
                        <?= $finals !== null
                            ? number_format((float)$finals, 1)
                            : '<span style="color:#ccc;">—</span>' ?>
                    </td>

                    <td style="text-align:center;">
                        <?php if ($fg !== null): ?>
                            <span style="font-weight:800;color:<?= $clr ?>">
                                <?= number_format((float)$fg, 2) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#ccc;">—</span>
                        <?php endif; ?>
                    </td>

                    <td style="text-align:center;">
                        <span class="badge <?= $rem_cls ?>">
                            <?= htmlspecialchars($rem) ?>
                        </span>
                    </td>

                    <td style="text-align:center;">
                        <a
                            href="grade_entry.php?class=<?= urlencode($filter_class ?: '') ?>&subj=<?= urlencode($s['subject_code']) ?>"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="fas fa-star"></i> Grades
                        </a>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

    <div class="pagination-wrap">
        Showing <?= count($students) ?>
        student<?= count($students) != 1 ? 's' : '' ?>
    </div>

</div>

<script>
initSearch('studSearch', 'studTable', [0,1]);

document.querySelector('select[name="class"]')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('subjHidden2').value = opt.dataset.subj || '';
});
</script>

<?php require_once 'footer.php'; ?>