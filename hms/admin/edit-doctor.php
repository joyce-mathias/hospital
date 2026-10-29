<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

$doctorId = hms_int(hms_get('id'));
$doctor = hms_fetch_one($con, 'SELECT * FROM doctors WHERE id = ?', 'i', array($doctorId));

if (!$doctor) {
    hms_flash('danger', 'Doctor not found.');
    hms_redirect('manage-doctors.php');
}

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $specialization = hms_post('Doctorspecialization');
    $name = hms_post('docname');
    $address = hms_post('clinicaddress');
    $fees = hms_post('docfees');
    $contact = hms_post('doccontact');
    $email = hms_post('docemail');

    if ($specialization === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hms_flash('danger', 'Please complete required doctor details.');
    } else {
        $updated = hms_execute(
            $con,
            'UPDATE doctors SET specilization = ?, doctorName = ?, address = ?, docFees = ?, contactno = ?, docEmail = ?, updationDate = NOW() WHERE id = ?',
            'ssssssi',
            array($specialization, $name, $address, $fees, $contact, $email, $doctorId)
        );
        if ($updated !== false) {
            hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'doctor_updated', 'doctors', $doctorId, 'Doctor updated: ' . $name);
            hms_flash('success', 'Doctor details updated successfully.');
            hms_redirect('manage-doctors.php');
        }
        hms_flash('danger', 'Unable to update doctor.');
    }
    $doctor = hms_fetch_one($con, 'SELECT * FROM doctors WHERE id = ?', 'i', array($doctorId));
}

$specializations = hms_fetch_all($con, 'SELECT specilization FROM doctorspecilization ORDER BY specilization ASC');

hms_layout_header('Edit Doctor', 'admin', 'doctors');
?>
<div class="hms-page-title">
    <div>
        <h1>Edit Doctor</h1>
        <p class="text-muted mb-0">Update doctor profile, contact, and fee details.</p>
    </div>
    <a class="btn btn-light" href="manage-doctors.php">Back to Doctors</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="hms-card p-4">
            <form method="post">
                <?php echo hms_csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="Doctorspecialization">Specialization</label>
                        <select class="form-select" id="Doctorspecialization" name="Doctorspecialization" required>
                            <option value="<?php echo hms_e($doctor['specilization']); ?>"><?php echo hms_e($doctor['specilization']); ?></option>
                            <?php foreach ($specializations as $row) { ?>
                                <option value="<?php echo hms_e($row['specilization']); ?>"><?php echo hms_e($row['specilization']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docname">Doctor name</label>
                        <input type="text" class="form-control" id="docname" name="docname" value="<?php echo hms_e($doctor['doctorName']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docfees">Consultancy fees</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="docfees" name="docfees" value="<?php echo hms_e($doctor['docFees']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="doccontact">Contact number</label>
                        <input type="text" class="form-control" id="doccontact" name="doccontact" value="<?php echo hms_e($doctor['contactno']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docemail">Email</label>
                        <input type="email" class="form-control" id="docemail" name="docemail" value="<?php echo hms_e($doctor['docEmail']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="clinicaddress">Clinic address</label>
                        <textarea class="form-control" id="clinicaddress" name="clinicaddress" rows="4"><?php echo hms_e($doctor['address']); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn btn-primary mt-4">
                    <i class="bi bi-save me-1"></i>Update Doctor
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hms-card p-4">
            <h2 class="h5">Profile Activity</h2>
            <dl class="mb-0">
                <dt class="text-muted small mt-3">Created</dt>
                <dd><?php echo hms_e($doctor['creationDate']); ?></dd>
                <dt class="text-muted small mt-3">Last updated</dt>
                <dd><?php echo hms_e($doctor['updationDate'] ?: 'Not updated'); ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
