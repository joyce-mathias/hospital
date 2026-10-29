<?php
session_start();
include 'include/config.php';
hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $specialization = hms_post('Doctorspecialization');
    $name = hms_post('docname');
    $address = hms_post('clinicaddress');
    $fees = hms_post('docfees');
    $contact = hms_post('doccontact');

    if ($specialization === '' || $name === '' || $contact === '') {
        hms_flash('danger', 'Please complete the required profile fields.');
    } else {
        $updated = hms_execute(
            $con,
            'UPDATE doctors SET specilization = ?, doctorName = ?, address = ?, docFees = ?, contactno = ?, updationDate = NOW() WHERE id = ?',
            'sssssi',
            array($specialization, $name, $address, $fees, $contact, $doctorId)
        );
        hms_flash($updated !== false ? 'success' : 'danger', $updated !== false ? 'Doctor profile updated successfully.' : 'Unable to update profile.');
        hms_redirect('edit-profile.php');
    }
}

$doctor = hms_fetch_one($con, 'SELECT * FROM doctors WHERE id = ?', 'i', array($doctorId));
$specializations = hms_fetch_all($con, 'SELECT specilization FROM doctorspecilization ORDER BY specilization ASC');

hms_layout_header('Edit Doctor Profile', 'doctor', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>Edit Profile</h1>
        <p class="text-muted mb-0">Update your specialization, clinic address, and contact details.</p>
    </div>
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
                            <option value="<?php echo hms_e($doctor['specilization'] ?? ''); ?>"><?php echo hms_e($doctor['specilization'] ?? 'Select specialization'); ?></option>
                            <?php foreach ($specializations as $row) { ?>
                                <option value="<?php echo hms_e($row['specilization']); ?>"><?php echo hms_e($row['specilization']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docname">Doctor name</label>
                        <input type="text" class="form-control" id="docname" name="docname" value="<?php echo hms_e($doctor['doctorName'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docfees">Consultancy fees</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="docfees" name="docfees" value="<?php echo hms_e($doctor['docFees'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="doccontact">Contact number</label>
                        <input type="text" class="form-control" id="doccontact" name="doccontact" value="<?php echo hms_e($doctor['contactno'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="docemail">Email</label>
                        <input type="email" class="form-control" id="docemail" value="<?php echo hms_e($doctor['docEmail'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="clinicaddress">Clinic address</label>
                        <textarea class="form-control" id="clinicaddress" name="clinicaddress" rows="4"><?php echo hms_e($doctor['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="submit" class="btn btn-primary mt-4">
                    <i class="bi bi-save me-1"></i>Save Profile
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hms-card p-4">
            <h2 class="h5">Profile Activity</h2>
            <dl class="mb-0">
                <dt class="text-muted small mt-3">Registered</dt>
                <dd><?php echo hms_e($doctor['creationDate'] ?? 'Not available'); ?></dd>
                <dt class="text-muted small mt-3">Last updated</dt>
                <dd><?php echo hms_e(!empty($doctor['updationDate']) ? $doctor['updationDate'] : 'Not updated yet'); ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
