<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['delete'])) {
    hms_verify_csrf();
    $doctorId = hms_int(hms_post('id'));
    if ($doctorId > 0) {
        hms_execute($con, 'DELETE FROM doctors WHERE id = ?', 'i', array($doctorId));
        hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'doctor_deleted', 'doctors', $doctorId, 'Doctor deleted.');
        hms_flash('success', 'Doctor deleted successfully.');
    }
    hms_redirect('manage-doctors.php');
}

$doctors = hms_fetch_all($con, 'SELECT * FROM doctors ORDER BY id DESC');

hms_layout_header('Manage Doctors', 'admin', 'doctors');
?>
<div class="hms-page-title">
    <div>
        <h1>Manage Doctors</h1>
        <p class="text-muted mb-0">Search, update, and manage registered doctors.</p>
    </div>
    <a class="btn btn-primary" href="add-doctor.php"><i class="bi bi-person-plus me-1"></i>Add Doctor</a>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Doctors</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search doctors..." data-table-search="#doctorsTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="doctorsTable" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Specialization</th>
                <th data-sort>Doctor</th>
                <th data-sort>Email</th>
                <th data-sort>Created</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($doctors as $index => $doctor) { ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo hms_e($doctor['specilization']); ?></td>
                    <td class="fw-semibold"><?php echo hms_e($doctor['doctorName']); ?></td>
                    <td><?php echo hms_e($doctor['docEmail']); ?></td>
                    <td><?php echo hms_e($doctor['creationDate']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="edit-doctor.php?id=<?php echo (int) $doctor['id']; ?>"><i class="bi bi-pencil-square"></i></a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this doctor?');">
                            <?php echo hms_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $doctor['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$doctors) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No doctors found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
