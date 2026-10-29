<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hms_redirect('between-dates-reports.php');
}

hms_verify_csrf();
$fromDate = hms_post('fromdate');
$toDate = hms_post('todate');
$patients = array();

if ($fromDate !== '' && $toDate !== '') {
    $patients = hms_fetch_all(
        $con,
        'SELECT * FROM tblpatient WHERE DATE(CreationDate) BETWEEN ? AND ? ORDER BY CreationDate DESC',
        'ss',
        array($fromDate, $toDate)
    );
}

hms_layout_header('Patient Date Report', 'admin', 'reports');
?>
<div class="hms-page-title">
    <div>
        <h1>Patient Date Report</h1>
        <p class="text-muted mb-0">Report from <?php echo hms_e($fromDate); ?> to <?php echo hms_e($toDate); ?>.</p>
    </div>
    <a class="btn btn-light" href="between-dates-reports.php">Change Dates</a>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Patients</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search report..." data-table-search="#reportTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="reportTable" data-sortable>
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
                <tr><td colspan="7" class="text-center text-muted py-4">No patients found for this date range.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
