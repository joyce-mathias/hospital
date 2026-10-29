<?php
session_start();
include('include/config.php');

hms_require_role('admin', 'logout.php');

$adminId = (int) $_SESSION['id'];
$statusExpr = hms_appointment_status_expr($con, 'a');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    $appointmentId = hms_int(hms_post('appointment_id'));
    $status = hms_post('status');

    $appointment = hms_fetch_one(
        $con,
        'SELECT id, userId, doctorId FROM appointment WHERE id = ?',
        'i',
        array($appointmentId)
    );

    if ($appointment && hms_set_appointment_status($con, $appointmentId, $status, 'admin', $adminId)) {
        hms_notify($con, 'patient', (int) $appointment['userId'], 'Appointment status updated', 'Your appointment is now ' . $status . '.');
        hms_notify($con, 'doctor', (int) $appointment['doctorId'], 'Appointment status updated', 'Appointment #' . $appointmentId . ' is now ' . $status . '.');
        hms_flash('success', 'Appointment status updated.');
    } else {
        hms_flash('error', 'Unable to update appointment status.');
    }
    hms_redirect('appointment-history.php');
}

$q = hms_get('q');
$status = hms_get('status');
$from = hms_get('from');
$to = hms_get('to');
$doctor = hms_int(hms_get('doctor'));
$page = max(1, hms_int(hms_get('page')) ?: 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = array();
$types = '';
$params = array();
if ($q !== '') {
    $where[] = '(u.fullName LIKE ? OR d.doctorName LIKE ? OR a.doctorSpecialization LIKE ?)';
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
if ($from !== '') {
    $where[] = 'a.appointmentDate >= ?';
    $types .= 's';
    $params[] = $from;
}
if ($to !== '') {
    $where[] = 'a.appointmentDate <= ?';
    $types .= 's';
    $params[] = $to;
}
if ($doctor > 0) {
    $where[] = 'a.doctorId = ?';
    $types .= 'i';
    $params[] = $doctor;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) hms_scalar(
    $con,
    'SELECT COUNT(*) FROM appointment a JOIN doctors d ON d.id = a.doctorId JOIN users u ON u.id = a.userId' . $whereSql,
    $types,
    $params
);

$rows = hms_fetch_all(
    $con,
    'SELECT a.id, a.doctorSpecialization, a.consultancyFees, a.appointmentDate, a.appointmentTime, a.postingDate, ' . $statusExpr . ' AS status_name, d.doctorName, u.fullName
     FROM appointment a
     JOIN doctors d ON d.id = a.doctorId
     JOIN users u ON u.id = a.userId' . $whereSql . '
     ORDER BY a.appointmentDate DESC, a.id DESC
     LIMIT ? OFFSET ?',
    $types . 'ii',
    array_merge($params, array($perPage, $offset))
);

$doctors = hms_fetch_all($con, 'SELECT id, doctorName FROM doctors ORDER BY doctorName ASC');
$pages = max(1, (int) ceil($total / $perPage));
$queryString = http_build_query(array_filter(array(
    'q' => $q,
    'status' => $status,
    'from' => $from,
    'to' => $to,
    'doctor' => $doctor ?: null
)));

hms_layout_header('Appointment Management', 'admin', 'appointments');
?>
<div class="hms-page-title">
    <div>
        <h1>Appointment Management</h1>
        <div class="text-muted">Approve, complete, cancel, filter, and export appointments.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="export-appointments.php?format=csv&<?php echo hms_e($queryString); ?>"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a class="btn btn-outline-secondary" href="export-appointments.php?format=pdf&<?php echo hms_e($queryString); ?>"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
    </div>
</div>

<div class="hms-card p-3 mb-3">
    <form class="row g-3 align-items-end" method="get">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?php echo hms_e($q); ?>" placeholder="Patient, doctor, specialization">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <?php foreach (array('Pending', 'Approved', 'Completed', 'Cancelled') as $option) { ?>
                    <option value="<?php echo hms_e($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo hms_e($option); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input class="form-control" type="date" name="from" value="<?php echo hms_e($from); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input class="form-control" type="date" name="to" value="<?php echo hms_e($to); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Doctor</label>
            <select class="form-select" name="doctor">
                <option value="">All</option>
                <?php foreach ($doctors as $doctorRow) { ?>
                    <option value="<?php echo hms_e($doctorRow['id']); ?>" <?php echo $doctor === (int) $doctorRow['id'] ? 'selected' : ''; ?>><?php echo hms_e($doctorRow['doctorName']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<div class="hms-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Appointments</h2>
        <span class="text-muted small"><?php echo hms_e($total); ?> result(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Patient</th>
                <th data-sort>Doctor</th>
                <th data-sort>Date / Time</th>
                <th data-sort>Status</th>
                <th class="text-end">Manage</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td><?php echo hms_e($row['id']); ?></td>
                    <td><?php echo hms_e($row['fullName']); ?></td>
                    <td>
                        <div class="fw-semibold"><?php echo hms_e($row['doctorName']); ?></div>
                        <div class="small text-muted"><?php echo hms_e($row['doctorSpecialization']); ?></div>
                    </td>
                    <td><?php echo hms_e($row['appointmentDate']); ?> <?php echo hms_e($row['appointmentTime']); ?></td>
                    <td><?php echo hms_status_badge($row['status_name']); ?></td>
                    <td class="text-end">
                        <form method="post" class="d-inline-flex gap-2 align-items-center">
                            <?php echo hms_csrf_field(); ?>
                            <input type="hidden" name="appointment_id" value="<?php echo hms_e($row['id']); ?>">
                            <select class="form-select form-select-sm" name="status" aria-label="Appointment status">
                                <?php foreach (array('Pending', 'Approved', 'Completed', 'Cancelled') as $option) { ?>
                                    <option value="<?php echo hms_e($option); ?>" <?php echo $row['status_name'] === $option ? 'selected' : ''; ?>><?php echo hms_e($option); ?></option>
                                <?php } ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$rows) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No appointments match the selected filters.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1) { ?>
        <nav class="mt-3" aria-label="Appointment pages">
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pages; $i++) {
                    $pageQuery = $queryString ? $queryString . '&page=' . $i : 'page=' . $i;
                    ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo hms_e($pageQuery); ?>"><?php echo hms_e($i); ?></a></li>
                <?php } ?>
            </ul>
        </nav>
    <?php } ?>
</div>
<?php hms_layout_footer(); ?>
