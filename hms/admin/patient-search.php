<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

$search = '';
$patients = array();
$searched = false;

if (isset($_POST['search'])) {
    hms_verify_csrf();
    $searched = true;
    $search = hms_post('searchdata');
    $like = '%' . $search . '%';
    $patients = hms_fetch_all(
        $con,
        'SELECT * FROM tblpatient WHERE PatientName LIKE ? OR PatientContno LIKE ? ORDER BY ID DESC',
        'ss',
        array($like, $like)
    );
}

hms_layout_header('Patient Search', 'admin', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Patient Search</h1>
        <p class="text-muted mb-0">Find patient records by name or mobile number.</p>
    </div>
</div>

<div class="hms-card p-4 mb-4">
    <form method="post" class="row g-3 align-items-end">
        <?php echo hms_csrf_field(); ?>
        <div class="col-md-8">
            <label class="form-label" for="searchdata">Name or mobile number</label>
            <input type="text" class="form-control" id="searchdata" name="searchdata" value="<?php echo hms_e($search); ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" name="search" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
        </div>
    </form>
</div>

<?php if ($searched) { ?>
    <div class="hms-card p-4">
        <h2 class="h5 mb-3">Results for "<?php echo hms_e($search); ?>"</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle" data-sortable>
                <thead>
                <tr>
                    <th data-sort>#</th>
                    <th data-sort>Patient</th>
                    <th data-sort>Contact</th>
                    <th data-sort>Gender</th>
                    <th data-sort>Created</th>
                    <th data-sort>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $index => $patient) { ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td class="fw-semibold"><?php echo hms_e($patient['PatientName']); ?></td>
                        <td><?php echo hms_e($patient['PatientContno']); ?></td>
                        <td><?php echo hms_e($patient['PatientGender']); ?></td>
                        <td><?php echo hms_e($patient['CreationDate']); ?></td>
                        <td><?php echo hms_e($patient['UpdationDate'] ?: 'Not updated'); ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="view-patient.php?viewid=<?php echo (int) $patient['ID']; ?>"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$patients) { ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No patient matched this search.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>
<?php hms_layout_footer(); ?>
