<?php
session_start();
include 'include/config.php';
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];

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
        $created = hms_execute(
            $con,
            'INSERT INTO tblpatient(Docid, PatientName, PatientContno, PatientEmail, PatientGender, PatientAdd, PatientAge, PatientMedhis) VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
            'isssssss',
            array($doctorId, $name, $contact, $email, $gender, $address, $age, $history)
        );
        if ($created !== false) {
            hms_log_audit($con, 'doctor', $doctorId, 'patient_created', 'tblpatient', mysqli_insert_id($con), 'Patient created: ' . $name);
            hms_flash('success', 'Patient added successfully.');
            hms_redirect('manage-patient.php');
        }
        hms_flash('danger', 'Unable to add patient.');
    }
}

hms_layout_header('Add Patient', 'doctor', 'patients');
?>
<div class="hms-page-title">
    <div>
        <h1>Add Patient</h1>
        <p class="text-muted mb-0">Create a patient record under your doctor account.</p>
    </div>
    <a class="btn btn-light" href="manage-patient.php">Back to Patients</a>
</div>

<div class="hms-card p-4">
    <form method="post">
        <?php echo hms_csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="patname">Patient name</label>
                <input type="text" class="form-control" id="patname" name="patname" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="patcontact">Contact number</label>
                <input type="text" class="form-control" id="patcontact" name="patcontact" maxlength="15" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="patemail">Email</label>
                <input type="email" class="form-control" id="patemail" name="patemail" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="gender">Gender</label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="">Select</option>
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="patage">Age</label>
                <input type="number" min="0" max="130" class="form-control" id="patage" name="patage" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="pataddress">Address</label>
                <textarea class="form-control" id="pataddress" name="pataddress" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="medhis">Medical history</label>
                <textarea class="form-control" id="medhis" name="medhis" rows="4" required></textarea>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary mt-4">
            <i class="bi bi-person-plus me-1"></i>Add Patient
        </button>
    </form>
</div>
<?php hms_layout_footer(); ?>
