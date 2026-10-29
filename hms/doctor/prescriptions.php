<?php
session_start();
include('include/config.php');

hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$hasTable = hms_table_exists($con, 'prescriptions');
$statusExpr = hms_appointment_status_expr($con, 'a');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    if (!$hasTable) {
        hms_flash('error', 'Run the SQL migration before adding prescriptions.');
        hms_redirect('prescriptions.php');
    }

    $appointmentId = hms_int(hms_post('appointment_id'));
    $appointment = hms_fetch_one(
        $con,
        'SELECT a.id, a.userId, u.fullName, u.email FROM appointment a JOIN users u ON u.id = a.userId WHERE a.id = ? AND a.doctorId = ?',
        'ii',
        array($appointmentId, $doctorId)
    );
    if (!$appointment) {
        hms_flash('error', 'Appointment not found.');
        hms_redirect('prescriptions.php');
    }

    $diagnosis = hms_post('diagnosis');
    $treatment = hms_post('treatment');
    $medicines = hms_post('medicines');
    $notes = hms_post('notes');
    $followup = hms_post('followup_date') !== '' ? hms_post('followup_date') : null;

    if ($diagnosis === '' || $treatment === '' || $medicines === '') {
        hms_flash('error', 'Diagnosis, treatment, and medicines are required.');
        hms_redirect('prescriptions.php?appointment_id=' . $appointmentId);
    }

    hms_execute(
        $con,
        'INSERT INTO prescriptions(appointment_id, doctor_id, patient_user_id, diagnosis, treatment, medicines, notes, followup_date, created_at, updated_at)
         VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE diagnosis = VALUES(diagnosis), treatment = VALUES(treatment), medicines = VALUES(medicines), notes = VALUES(notes), followup_date = VALUES(followup_date), updated_at = NOW()',
        'iiisssss',
        array($appointmentId, $doctorId, (int) $appointment['userId'], $diagnosis, $treatment, $medicines, $notes, $followup)
    );

    if (hms_column_exists($con, 'appointment', 'status')) {
        hms_set_appointment_status($con, $appointmentId, 'Completed', 'doctor', $doctorId);
    }

    $patientRecord = hms_fetch_one($con, 'SELECT ID FROM tblpatient WHERE PatientEmail = ? ORDER BY ID DESC LIMIT 1', 's', array($appointment['email']));
    if ($patientRecord) {
        hms_execute(
            $con,
            'INSERT INTO tblmedicalhistory(PatientID, BloodPressure, BloodSugar, Weight, Temperature, MedicalPres) VALUES(?, ?, ?, ?, ?, ?)',
            'isssss',
            array((int) $patientRecord['ID'], '', '', '', '', $diagnosis . "\n" . $treatment . "\n" . $medicines)
        );
    }

    hms_log_audit($con, 'doctor', $doctorId, 'prescription_saved', 'appointment', $appointmentId, 'Prescription saved for ' . $appointment['fullName']);
    hms_notify($con, 'patient', (int) $appointment['userId'], 'Prescription available', 'Your prescription for appointment #' . $appointmentId . ' is ready to download.');
    hms_flash('success', 'Prescription saved.');
    hms_redirect('prescriptions.php?appointment_id=' . $appointmentId);
}

$appointments = hms_fetch_all(
    $con,
    'SELECT a.id, a.appointmentDate, a.appointmentTime, ' . $statusExpr . ' AS status_name, u.fullName
     FROM appointment a
     JOIN users u ON u.id = a.userId
     WHERE a.doctorId = ? AND ' . $statusExpr . ' <> "Cancelled"
     ORDER BY a.appointmentDate DESC, a.id DESC
     LIMIT 100',
    'i',
    array($doctorId)
);

$selectedAppointmentId = hms_int(hms_get('appointment_id'));
$selectedPrescription = null;
if ($selectedAppointmentId && $hasTable) {
    $selectedPrescription = hms_fetch_one($con, 'SELECT * FROM prescriptions WHERE appointment_id = ? AND doctor_id = ?', 'ii', array($selectedAppointmentId, $doctorId));
}

$recentPrescriptions = $hasTable
    ? hms_fetch_all(
        $con,
        'SELECT p.id, p.appointment_id, p.diagnosis, p.followup_date, p.created_at, u.fullName
         FROM prescriptions p
         JOIN users u ON u.id = p.patient_user_id
         WHERE p.doctor_id = ?
         ORDER BY p.updated_at DESC, p.id DESC
         LIMIT 10',
        'i',
        array($doctorId)
    )
    : array();

hms_layout_header('Prescription Management', 'doctor', 'prescriptions');
?>
<div class="hms-page-title">
    <div>
        <h1>Prescriptions</h1>
        <div class="text-muted">Record diagnosis, treatment, medicines, and follow-up dates.</div>
    </div>
    <a class="btn btn-outline-secondary" href="appointment-history.php"><i class="bi bi-calendar-check me-1"></i>Appointments</a>
</div>

<?php if (!$hasTable) { ?>
    <div class="alert alert-warning">The prescriptions table is not installed yet. Apply <code>database_improvements.sql</code> first.</div>
<?php } ?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">Prescription Details</h2>
            <form method="post">
                <?php echo hms_csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Appointment</label>
                    <select class="form-select" name="appointment_id" required onchange="if(this.value){window.location='prescriptions.php?appointment_id='+this.value;}">
                        <option value="">Select appointment</option>
                        <?php foreach ($appointments as $appointment) { ?>
                            <option value="<?php echo hms_e($appointment['id']); ?>" <?php echo $selectedAppointmentId === (int) $appointment['id'] ? 'selected' : ''; ?>>
                                #<?php echo hms_e($appointment['id']); ?> - <?php echo hms_e($appointment['fullName']); ?> - <?php echo hms_e($appointment['appointmentDate']); ?> <?php echo hms_e($appointment['appointmentTime']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea class="form-control" name="diagnosis" rows="3" required><?php echo hms_e($selectedPrescription['diagnosis'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Treatment</label>
                    <textarea class="form-control" name="treatment" rows="3" required><?php echo hms_e($selectedPrescription['treatment'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Medicines</label>
                    <textarea class="form-control" name="medicines" rows="4" required><?php echo hms_e($selectedPrescription['medicines'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"><?php echo hms_e($selectedPrescription['notes'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Follow-up Date</label>
                    <input class="form-control" type="date" name="followup_date" value="<?php echo hms_e($selectedPrescription['followup_date'] ?? ''); ?>">
                </div>
                <button class="btn btn-primary w-100" type="submit" <?php echo !$hasTable ? 'disabled' : ''; ?>>Save Prescription</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="hms-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Recent Records</h2>
                <input class="form-control form-control-sm" style="max-width:240px" placeholder="Search" data-table-search="#prescriptionTable">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="prescriptionTable" data-sortable>
                    <thead>
                    <tr>
                        <th data-sort>Patient</th>
                        <th data-sort>Diagnosis</th>
                        <th data-sort>Follow-up</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentPrescriptions as $prescription) { ?>
                        <tr>
                            <td><?php echo hms_e($prescription['fullName']); ?></td>
                            <td><?php echo hms_e($prescription['diagnosis']); ?></td>
                            <td><?php echo hms_e($prescription['followup_date'] ?: 'Not set'); ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="prescriptions.php?appointment_id=<?php echo hms_e($prescription['appointment_id']); ?>">Edit</a></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$recentPrescriptions) { ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No prescriptions saved yet.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
