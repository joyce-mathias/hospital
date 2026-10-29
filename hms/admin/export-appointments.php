<?php
session_start();
include('include/config.php');

hms_require_role('admin', 'logout.php');

$statusExpr = hms_appointment_status_expr($con, 'a');
$q = hms_get('q');
$status = hms_get('status');
$from = hms_get('from');
$to = hms_get('to');
$doctor = hms_int(hms_get('doctor'));
$format = strtolower(hms_get('format', 'csv'));

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

$rows = hms_fetch_all(
    $con,
    'SELECT a.id, u.fullName, d.doctorName, a.doctorSpecialization, a.consultancyFees, a.appointmentDate, a.appointmentTime, ' . $statusExpr . ' AS status_name, a.postingDate
     FROM appointment a
     JOIN doctors d ON d.id = a.doctorId
     JOIN users u ON u.id = a.userId' . $whereSql . '
     ORDER BY a.appointmentDate DESC, a.id DESC',
    $types,
    $params
);

if ($format === 'pdf') {
    $lines = array('ID | Patient | Doctor | Date | Time | Status | Fee');
    foreach ($rows as $row) {
        $lines[] = $row['id'] . ' | ' . $row['fullName'] . ' | ' . $row['doctorName'] . ' | ' . $row['appointmentDate'] . ' | ' . $row['appointmentTime'] . ' | ' . $row['status_name'] . ' | ' . $row['consultancyFees'];
    }
    hms_send_basic_pdf('appointments-report.pdf', 'Appointment Report', $lines);
}

$csvRows = array();
foreach ($rows as $row) {
    $csvRows[] = array(
        $row['id'],
        $row['fullName'],
        $row['doctorName'],
        $row['doctorSpecialization'],
        $row['consultancyFees'],
        $row['appointmentDate'],
        $row['appointmentTime'],
        $row['status_name'],
        $row['postingDate']
    );
}
hms_send_csv(
    'appointments-report.csv',
    array('ID', 'Patient', 'Doctor', 'Specialization', 'Fee', 'Date', 'Time', 'Status', 'Created At'),
    $csvRows
);
?>
