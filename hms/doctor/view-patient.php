<?php
session_start();
include('include/config.php');
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$patientId = hms_int(hms_get('viewid'));
$patient = hms_fetch_one($con, 'SELECT * FROM tblpatient WHERE ID = ? AND Docid = ?', 'ii', array($patientId, $doctorId));
if (!$patient) {
    hms_flash('error', 'Patient record not found.');
    hms_redirect('manage-patient.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    $bp = hms_post('bp');
    $bs = hms_post('bs');
    $weight = hms_post('weight');
    $temp = hms_post('temp');
    $pres = hms_post('pres');
    hms_execute(
        $con,
        'INSERT INTO tblmedicalhistory(PatientID, BloodPressure, BloodSugar, Weight, Temperature, MedicalPres) VALUES(?, ?, ?, ?, ?, ?)',
        'isssss',
        array($patientId, $bp, $bs, $weight, $temp, $pres)
    );
    hms_log_audit($con, 'doctor', $doctorId, 'medical_history_added', 'tblpatient', $patientId, 'Medical history added.');
    hms_flash('success', 'Medical history added.');
    hms_redirect('view-patient.php?viewid=' . $patientId);
}

$history = hms_fetch_all($con, 'SELECT * FROM tblmedicalhistory WHERE PatientID = ? ORDER BY CreationDate DESC, ID DESC', 'i', array($patientId));
hms_layout_header('Patient Details', 'doctor', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Patient Details</h1>
        <div class="text-muted">Diagnosis, treatment notes, and medical history timeline.</div>
    </div>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#historyModal">Add History</button>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3"><?php echo hms_e($patient['PatientName']); ?></h2>
            <div class="mb-2"><span class="text-muted">Email</span><div><?php echo hms_e($patient['PatientEmail']); ?></div></div>
            <div class="mb-2"><span class="text-muted">Contact</span><div><?php echo hms_e($patient['PatientContno']); ?></div></div>
            <div class="mb-2"><span class="text-muted">Gender / Age</span><div><?php echo hms_e($patient['PatientGender']); ?> / <?php echo hms_e($patient['PatientAge']); ?></div></div>
            <div class="mb-2"><span class="text-muted">Address</span><div><?php echo hms_e($patient['PatientAdd']); ?></div></div>
            <div><span class="text-muted">Initial History</span><div><?php echo nl2br(hms_e($patient['PatientMedhis'])); ?></div></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">Medical History</h2>
            <div class="timeline">
                <?php foreach ($history as $item) { ?>
                    <div class="timeline-item">
                        <div class="fw-semibold"><?php echo hms_e($item['CreationDate']); ?></div>
                        <div class="small text-muted">BP: <?php echo hms_e($item['BloodPressure']); ?> | Sugar: <?php echo hms_e($item['BloodSugar']); ?> | Weight: <?php echo hms_e($item['Weight']); ?> | Temp: <?php echo hms_e($item['Temperature']); ?></div>
                        <div><?php echo nl2br(hms_e($item['MedicalPres'])); ?></div>
                    </div>
                <?php } ?>
                <?php if (!$history) { ?>
                    <div class="text-muted">No medical history added yet.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <h5 class="modal-title">Add Medical History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo hms_csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label">Blood Pressure</label><input class="form-control" name="bp" required></div>
                    <div class="col-sm-6"><label class="form-label">Blood Sugar</label><input class="form-control" name="bs" required></div>
                    <div class="col-sm-6"><label class="form-label">Weight</label><input class="form-control" name="weight" required></div>
                    <div class="col-sm-6"><label class="form-label">Temperature</label><input class="form-control" name="temp" required></div>
                </div>
                <div class="mt-3"><label class="form-label">Prescription / Notes</label><textarea class="form-control" name="pres" rows="5" required></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
<?php hms_layout_footer(); ?>
