<?php
session_start();
include('include/config.php');

hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$statusExpr = hms_appointment_status_expr($con, 'a');
$stats = array(
    'today' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM appointment WHERE doctorId = ? AND appointmentDate = CURDATE()', 'i', array($doctorId)),
    'pending' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM appointment a WHERE a.doctorId = ? AND ' . $statusExpr . ' = "Pending"', 'i', array($doctorId)),
    'completed' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM appointment a WHERE a.doctorId = ? AND ' . $statusExpr . ' = "Completed"', 'i', array($doctorId)),
    'patients' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM tblpatient WHERE Docid = ?', 'i', array($doctorId))
);
$stats['followups'] = hms_table_exists($con, 'prescriptions')
    ? (int) hms_scalar($con, 'SELECT COUNT(*) FROM prescriptions WHERE doctor_id = ? AND followup_date >= CURDATE()', 'i', array($doctorId))
    : 0;

$todaysAppointments = hms_fetch_all(
    $con,
    'SELECT a.id, a.appointmentDate, a.appointmentTime, a.doctorSpecialization, a.consultancyFees, ' . $statusExpr . ' AS status_name, u.fullName, u.email
     FROM appointment a
     JOIN users u ON u.id = a.userId
     WHERE a.doctorId = ? AND a.appointmentDate = CURDATE()
     ORDER BY a.appointmentTime ASC, a.id ASC',
    'i',
    array($doctorId)
);

$followups = array();
if (hms_table_exists($con, 'prescriptions')) {
    $followups = hms_fetch_all(
        $con,
        'SELECT p.followup_date, p.diagnosis, u.fullName
         FROM prescriptions p
         JOIN users u ON u.id = p.patient_user_id
         WHERE p.doctor_id = ? AND p.followup_date >= CURDATE()
         ORDER BY p.followup_date ASC
         LIMIT 5',
        'i',
        array($doctorId)
    );
}

hms_layout_header('Doctor Dashboard', 'doctor', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>Doctor Dashboard</h1>
        <div class="text-muted">Today’s schedule, prescription work, and follow-up planning.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="availability.php"><i class="bi bi-clock-history me-1"></i>Availability</a>
        <a class="btn btn-primary" href="prescriptions.php"><i class="bi bi-file-medical me-1"></i>Prescription</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="appointment-history.php?date=<?php echo date('Y-m-d'); ?>">
            <span class="icon"><i class="bi bi-calendar-day"></i></span>
            <div class="value"><?php echo hms_e($stats['today']); ?></div>
            <div class="label">Today</div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="hms-card hms-stat">
            <span class="icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="value"><?php echo hms_e($stats['pending']); ?></div>
            <div class="label">Pending</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="hms-card hms-stat">
            <span class="icon"><i class="bi bi-check2-circle"></i></span>
            <div class="value"><?php echo hms_e($stats['completed']); ?></div>
            <div class="label">Completed</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="manage-patient.php">
            <span class="icon"><i class="bi bi-people"></i></span>
            <div class="value"><?php echo hms_e($stats['patients']); ?></div>
            <div class="label">Patients</div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="hms-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Today’s Appointments</h2>
                <input class="form-control form-control-sm" style="max-width:240px" placeholder="Search" data-table-search="#todayTable">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="todayTable" data-sortable>
                    <thead>
                    <tr>
                        <th data-sort>Patient</th>
                        <th data-sort>Time</th>
                        <th data-sort>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($todaysAppointments as $appointment) { ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo hms_e($appointment['fullName']); ?></div>
                                <div class="small text-muted"><?php echo hms_e($appointment['email']); ?></div>
                            </td>
                            <td><?php echo hms_e($appointment['appointmentTime']); ?></td>
                            <td><?php echo hms_status_badge($appointment['status_name']); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="prescriptions.php?appointment_id=<?php echo hms_e($appointment['id']); ?>">Record</a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$todaysAppointments) { ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No appointments scheduled for today.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="hms-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Follow-ups</h2>
                <span class="badge text-bg-light"><?php echo hms_e($stats['followups']); ?></span>
            </div>
            <?php if ($followups) { ?>
                <div class="timeline">
                    <?php foreach ($followups as $followup) { ?>
                        <div class="timeline-item">
                            <div class="fw-semibold"><?php echo hms_e($followup['fullName']); ?></div>
                            <div class="small text-muted"><?php echo hms_e($followup['followup_date']); ?></div>
                            <div class="small"><?php echo hms_e($followup['diagnosis']); ?></div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="text-muted">No follow-up appointments recorded yet.</div>
            <?php } ?>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
