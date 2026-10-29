<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

if (isset($_POST['submit'])) {
    hms_verify_csrf();
    $specialization = hms_post('Doctorspecialization');
    $name = hms_post('docname');
    $address = hms_post('clinicaddress');
    $fees = hms_post('docfees');
    $contact = hms_post('doccontact');
    $email = hms_post('docemail');
    $password = isset($_POST['npass']) ? (string) $_POST['npass'] : '';
    $confirm = isset($_POST['cfpass']) ? (string) $_POST['cfpass'] : '';

    if ($specialization === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        hms_flash('danger', 'Please complete required doctor details.');
    } elseif ($password === '' || $password !== $confirm) {
        hms_flash('danger', 'Password and confirm password must match.');
    } elseif ((int) hms_scalar($con, 'SELECT COUNT(*) FROM doctors WHERE docEmail = ?', 's', array($email)) > 0) {
        hms_flash('danger', 'This doctor email is already registered.');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $created = hms_execute(
            $con,
            'INSERT INTO doctors(specilization, doctorName, address, docFees, contactno, docEmail, password) VALUES(?, ?, ?, ?, ?, ?, ?)',
            'sssssss',
            array($specialization, $name, $address, $fees, $contact, $email, $hash)
        );
        if ($created !== false) {
            hms_log_audit($con, 'admin', (int) $_SESSION['id'], 'doctor_created', 'doctors', mysqli_insert_id($con), 'Doctor created: ' . $name);
            hms_flash('success', 'Doctor added successfully.');
            hms_redirect('manage-doctors.php');
        }
        hms_flash('danger', 'Unable to add doctor.');
    }
}

$specializations = hms_fetch_all($con, 'SELECT specilization FROM doctorspecilization ORDER BY specilization ASC');

hms_layout_header('Add Doctor', 'admin', 'doctors');
?>
<div class="hms-page-title">
    <div>
        <h1>Add Doctor</h1>
        <p class="text-muted mb-0">Create a doctor login and profile.</p>
    </div>
    <a class="btn btn-light" href="manage-doctors.php">Back to Doctors</a>
</div>

<div class="hms-card p-4">
    <form method="post">
        <?php echo hms_csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="Doctorspecialization">Specialization</label>
                <select class="form-select" id="Doctorspecialization" name="Doctorspecialization" required>
                    <option value="">Select specialization</option>
                    <?php foreach ($specializations as $row) { ?>
                        <option value="<?php echo hms_e($row['specilization']); ?>"><?php echo hms_e($row['specilization']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="docname">Doctor name</label>
                <input type="text" class="form-control" id="docname" name="docname" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="docfees">Consultancy fees</label>
                <input type="number" min="0" step="0.01" class="form-control" id="docfees" name="docfees" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="doccontact">Contact number</label>
                <input type="text" class="form-control" id="doccontact" name="doccontact" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="docemail">Email</label>
                <input type="email" class="form-control" id="docemail" name="docemail" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="npass">Password</label>
                <input type="password" class="form-control" id="npass" name="npass" minlength="6" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cfpass">Confirm password</label>
                <input type="password" class="form-control" id="cfpass" name="cfpass" minlength="6" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="clinicaddress">Clinic address</label>
                <textarea class="form-control" id="clinicaddress" name="clinicaddress" rows="4" required></textarea>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary mt-4">
            <i class="bi bi-person-plus me-1"></i>Add Doctor
        </button>
    </form>
</div>
<?php hms_layout_footer(); ?>
