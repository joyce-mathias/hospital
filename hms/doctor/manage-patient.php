<?php
session_start();
include 'include/config.php';
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$patients = hms_fetch_all($con, 'SELECT * FROM tblpatient WHERE Docid = ? ORDER BY ID DESC', 'i', array($doctorId));

hms_layout_header('Manage Patients', 'doctor', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Manage Patients</h1>
        <p class="text-muted mb-0">View, update, and review patients assigned to you.</p>
    </div>
    <a class="btn btn-primary" href="add-patient.php"><i class="bi bi-person-plus me-1"></i>Add Patient</a>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Patient List</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search patients..." data-table-search="#patientsTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="patientsTable" data-sortable>
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
                        <a class="btn btn-sm btn-outline-primary" href="edit-patient.php?editid=<?php echo (int) $patient['ID']; ?>"><i class="bi bi-pencil-square"></i></a>
                        <a class="btn btn-sm btn-outline-secondary" href="view-patient.php?viewid=<?php echo (int) $patient['ID']; ?>"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$patients) { ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No patients found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
