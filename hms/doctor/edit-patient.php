<?php
session_start();
include 'include/config.php';
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$patientId = hms_int(hms_get('editid'));
$patient = hms_fetch_one($con, 'SELECT * FROM tblpatient WHERE ID = ? AND Docid = ?', 'ii', array($patientId, $doctorId));

if (!$patient) {
    hms_flash('danger', 'Patient record not found.');
    hms_redirect('manage-patient.php');
}

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $name = hms_post('patname');
    $contact = hms_post('patcontact');
    $email = hms_post('patemail');
    $gender = hms_post('gender');
    $address = hms_post('pataddress');
    $age = hms_post('patage');
    $history = hms_post('medhis');

    if ($name === '' || $contact === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hms_flash('danger', 'Please enter patient name, contact number, and a valid email.');
    } else {
        $updated = hms_execute(
            $con,
            'UPDATE tblpatient SET PatientName = ?, PatientContno = ?, PatientEmail = ?, PatientGender = ?, PatientAdd = ?, PatientAge = ?, PatientMedhis = ?, UpdationDate = NOW() WHERE ID = ? AND Docid = ?',
            'sssssssii',
            array($name, $contact, $email, $gender, $address, $age, $history, $patientId, $doctorId)
        );
        if ($updated !== false) {
            hms_log_audit($con, 'doctor', $doctorId, 'patient_updated', 'tblpatient', $patientId, 'Patient updated: ' . $name);
            hms_flash('success', 'Patient updated successfully.');
            hms_redirect('manage-patient.php');
        }
        hms_flash('danger', 'Unable to update patient.');
    }
    $patient = hms_fetch_one($con, 'SELECT * FROM tblpatient WHERE ID = ? AND Docid = ?', 'ii', array($patientId, $doctorId));
}

hms_layout_header('Edit Patient', 'doctor', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Edit Patient</h1>
        <p class="text-muted mb-0">Update patient profile and baseline medical history.</p>
    </div>
    <a class="btn btn-light" href="manage-patient.php">Back to Patients</a>
</div>

<div class="hms-card p-4">
    <form method="post">
        <?php echo hms_csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="patname">Patient name</label>
                <input type="text" class="form-control" id="patname" name="patname" value="<?php echo hms_e($patient['PatientName']); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="patcontact">Contact number</label>
                <input type="text" class="form-control" id="patcontact" name="patcontact" value="<?php echo hms_e($patient['PatientContno']); ?>" maxlength="15" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="patemail">Email</label>
                <input type="email" class="form-control" id="patemail" name="patemail" value="<?php echo hms_e($patient['PatientEmail']); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="gender">Gender</label>
                <select class="form-select" id="gender" name="gender" required>
                    <?php foreach (array('Female', 'Male', 'Other') as $gender) { ?>
                        <option value="<?php echo hms_e($gender); ?>" <?php echo strcasecmp($patient['PatientGender'], $gender) === 0 ? 'selected' : ''; ?>><?php echo hms_e($gender); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="patage">Age</label>
                <input type="number" min="0" max="130" class="form-control" id="patage" name="patage" value="<?php echo hms_e($patient['PatientAge']); ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="pataddress">Address</label>
                <textarea class="form-control" id="pataddress" name="pataddress" rows="3" required><?php echo hms_e($patient['PatientAdd']); ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="medhis">Medical history</label>
                <textarea class="form-control" id="medhis" name="medhis" rows="4" required><?php echo hms_e($patient['PatientMedhis']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Created</label>
                <input type="text" class="form-control" value="<?php echo hms_e($patient['CreationDate']); ?>" readonly>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary mt-4">
            <i class="bi bi-save me-1"></i>Update Patient
        </button>
    </form>
</div>
<?php hms_layout_footer(); ?>
