<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $specialization = hms_post('doctorspecilization');
    if ($specialization === '') {
        hms_flash('danger', 'Please enter a specialization.');
    } else {
        hms_execute($con, 'INSERT INTO doctorSpecilization(specilization) VALUES(?)', 's', array($specialization));
        hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'specialization_created', 'doctorSpecilization', mysqli_insert_id($con), 'Specialization created: ' . $specialization);
        hms_flash('success', 'Doctor specialization added successfully.');
        hms_redirect('doctor-specilization.php');
    }
}

if (isset($_POST['delete'])) {
    hms_verify_csrf();
    $id = hms_int(hms_post('id'));
    if ($id > 0) {
        hms_execute($con, 'DELETE FROM doctorSpecilization WHERE id = ?', 'i', array($id));
        hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'specialization_deleted', 'doctorSpecilization', $id, 'Specialization deleted.');
        hms_flash('success', 'Specialization deleted successfully.');
    }
    hms_redirect('doctor-specilization.php');
}

$specializations = hms_fetch_all($con, 'SELECT * FROM doctorSpecilization ORDER BY id DESC');

hms_layout_header('Doctor Specialization', 'admin', 'doctors');
?>
<div class="hms-page-title">
    <div>
        <h1>Doctor Specialization</h1>
        <p class="text-muted mb-0">Maintain the list of services used by doctor profiles.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="hms-card p-4">
            <h2 class="h5 mb-3">Add Specialization</h2>
            <form method="post">
                <?php echo hms_csrf_field(); ?>
                <label class="form-label" for="doctorspecilization">Specialization name</label>
                <input type="text" class="form-control" id="doctorspecilization" name="doctorspecilization" required>
                <button type="submit" name="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-1"></i>Add
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="hms-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <h2 class="h5 mb-0">Specializations</h2>
                <input type="search" class="form-control" style="max-width: 280px;" placeholder="Search..." data-table-search="#specTable">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="specTable" data-sortable>
                    <thead>
                    <tr>
                        <th data-sort>#</th>
                        <th data-sort>Specialization</th>
                        <th data-sort>Created</th>
                        <th data-sort>Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($specializations as $index => $row) { ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="fw-semibold"><?php echo hms_e($row['specilization']); ?></td>
                            <td><?php echo hms_e($row['creationDate']); ?></td>
                            <td><?php echo hms_e($row['updationDate'] ?: 'Not updated'); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="edit-doctor-specialization.php?id=<?php echo (int) $row['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this specialization?');">
                                    <?php echo hms_csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$specializations) { ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No specializations found.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
