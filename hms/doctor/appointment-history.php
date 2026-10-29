<?php
session_start();
include('include/config.php');

hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$statusExpr = hms_appointment_status_expr($con, 'a');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    $appointmentId = hms_int(hms_post('appointment_id'));
    $status = hms_post('status');
    $appointment = hms_fetch_one($con, 'SELECT id, userId FROM appointment WHERE id = ? AND doctorId = ?', 'ii', array($appointmentId, $doctorId));

    if ($appointment && hms_set_appointment_status($con, $appointmentId, $status, 'doctor', $doctorId)) {
        hms_notify($con, 'patient', (int) $appointment['userId'], 'Appointment status updated', 'Your appointment is now ' . $status . '.');
        hms_notify($con, 'admin', null, 'Appointment status updated', 'Doctor updated appointment #' . $appointmentId . ' to ' . $status . '.');
        hms_flash('success', 'Appointment status updated.');
    } else {
        hms_flash('error', 'Unable to update appointment status.');
    }
    hms_redirect('appointment-history.php');
}

$q = hms_get('q');
$status = hms_get('status');
$date = hms_get('date');
$where = array('a.doctorId = ?');
$types = 'i';
$params = array($doctorId);

if ($q !== '') {
    $where[] = '(u.fullName LIKE ? OR u.email LIKE ? OR a.doctorSpecialization LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (hms_valid_status($status)) {
    $where[] = $statusExpr . ' = ?';
    $types .= 's';
    $params[] = $status;
}
if ($date !== '') {
    $where[] = 'a.appointmentDate = ?';
    $types .= 's';
    $params[] = $date;
}

$appointments = hms_fetch_all(
    $con,
    'SELECT a.id, a.doctorSpecialization, a.consultancyFees, a.appointmentDate, a.appointmentTime, a.postingDate, ' . $statusExpr . ' AS status_name, u.fullName, u.email
     FROM appointment a
     JOIN users u ON u.id = a.userId
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY a.appointmentDate DESC, a.appointmentTime DESC',
    $types,
    $params
);

hms_layout_header('Doctor Appointments', 'doctor', 'appointments');
?>
<div class="hms-page-title">
    <div>
        <h1>Appointments</h1>
        <div class="text-muted">Review today’s visits, approve pending requests, complete visits, and open prescriptions.</div>
    </div>
    <a class="btn btn-primary" href="prescriptions.php"><i class="bi bi-file-medical me-1"></i>Prescription</a>
</div>

<div class="hms-card p-3 mb-3">
    <form class="row g-3 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?php echo hms_e($q); ?>" placeholder="Patient, email, specialization">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <?php foreach (array('Pending', 'Approved', 'Completed', 'Cancelled') as $option) { ?>
                    <option value="<?php echo hms_e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo hms_e($option); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input class="form-control" type="date" name="date" value="<?php echo hms_e($date); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="hms-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Patient</th>
                <th data-sort>Date / Time</th>
                <th data-sort>Status</th>
                <th class="text-end">Manage</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment) { ?>
                <tr>
                    <td><?php echo hms_e($appointment['id']); ?></td>
                    <td>
                        <div class="fw-semibold"><?php echo hms_e($appointment['fullName']); ?></div>
                        <div class="small text-muted"><?php echo hms_e($appointment['email']); ?></div>
                    </td>
                    <td><?php echo hms_e($appointment['appointmentDate']); ?> <?php echo hms_e($appointment['appointmentTime']); ?></td>
                    <td><?php echo hms_status_badge($appointment['status_name']); ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-success" href="prescriptions.php?appointment_id=<?php echo hms_e($appointment['id']); ?>">Record</a>
                            <form method="post" class="d-inline-flex gap-2 align-items-center">
                                <?php echo hms_csrf_field(); ?>
                                <input type="hidden" name="appointment_id" value="<?php echo hms_e($appointment['id']); ?>">
                                <select class="form-select form-select-sm" name="status">
                                    <?php foreach (array('Pending', 'Approved', 'Completed', 'Cancelled') as $option) { ?>
                                        <option value="<?php echo hms_e($option); ?>" <?php echo $appointment['status_name'] === $option ? 'selected' : ''; ?>><?php echo hms_e($option); ?></option>
                                    <?php } ?>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$appointments) { ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No appointments found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
