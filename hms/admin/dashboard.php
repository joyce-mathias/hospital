<?php
session_start();
include('include/config.php');

hms_require_role('admin', 'logout.php');

$statusExpr = hms_appointment_status_expr($con, 'a');
$stats = array(
    'users' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM users'),
    'doctors' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM doctors'),
    'patients' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM tblpatient'),
    'appointments' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM appointment'),
    'new_queries' => (int) hms_scalar($con, 'SELECT COUNT(*) FROM tblcontactus WHERE IsRead IS NULL')
);

$statusRows = hms_fetch_all(
    $con,
    'SELECT ' . $statusExpr . ' AS status_name, COUNT(*) AS total FROM appointment a GROUP BY status_name ORDER BY total DESC'
);
$statusLabels = array();
$statusTotals = array();
foreach ($statusRows as $row) {
    $statusLabels[] = $row['status_name'];
    $statusTotals[] = (int) $row['total'];
}

$recentAppointments = hms_fetch_all(
    $con,
    'SELECT a.id, a.doctorSpecialization, a.consultancyFees, a.appointmentDate, a.appointmentTime, a.postingDate, ' . $statusExpr . ' AS status_name, d.doctorName, u.fullName
     FROM appointment a
     JOIN doctors d ON d.id = a.doctorId
     JOIN users u ON u.id = a.userId
     ORDER BY a.id DESC
     LIMIT 6'
);

$recentActivities = array();
if (hms_table_exists($con, 'audit_logs')) {
    $recentActivities = hms_fetch_all(
        $con,
        'SELECT actor_type, action, entity_type, entity_id, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 6'
    );
}

$unreadNotifications = hms_table_exists($con, 'notifications')
    ? (int) hms_scalar($con, 'SELECT COUNT(*) FROM notifications WHERE user_type = "admin" AND is_read = 0')
    : 0;

hms_layout_header('Admin Dashboard', 'admin', 'dashboard');
?>
<div class="hms-page-title">
    <div>
        <h1>Admin Dashboard</h1>
        <div class="text-muted">Operational overview, appointment flow, and recent activity.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="appointment-history.php"><i class="bi bi-calendar-check me-1"></i>Manage Appointments</a>
        <a class="btn btn-primary" href="doctor-availability.php"><i class="bi bi-clock-history me-1"></i>Doctor Availability</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="manage-users.php">
            <span class="icon"><i class="bi bi-people"></i></span>
            <div class="value"><?php echo hms_e($stats['users']); ?></div>
            <div class="label">Registered Users</div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="manage-doctors.php">
            <span class="icon"><i class="bi bi-person-badge"></i></span>
            <div class="value"><?php echo hms_e($stats['doctors']); ?></div>
            <div class="label">Doctors</div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="manage-patient.php">
            <span class="icon"><i class="bi bi-person-vcard"></i></span>
            <div class="value"><?php echo hms_e($stats['patients']); ?></div>
            <div class="label">Patient Records</div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a class="hms-card hms-stat d-block text-reset" href="unread-queries.php">
            <span class="icon"><i class="bi bi-chat-left-text"></i></span>
            <div class="value"><?php echo hms_e($stats['new_queries']); ?></div>
            <div class="label">New Queries</div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="hms-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">Appointment Status</h2>
                    <div class="text-muted small">Pending, approved, completed, and cancelled appointments.</div>
                </div>
                <span class="badge text-bg-light"><?php echo hms_e($stats['appointments']); ?> total</span>
            </div>
            <canvas id="appointmentStatusChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hms-card p-3 h-100">
            <h2 class="h5 mb-3">Signals</h2>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Unread notifications</span>
                <strong><?php echo hms_e($unreadNotifications); ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">New contact queries</span>
                <strong><?php echo hms_e($stats['new_queries']); ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span class="text-muted">Audit logging</span>
                <strong><?php echo hms_table_exists($con, 'audit_logs') ? 'Enabled' : 'Migration needed'; ?></strong>
            </div>
            <div class="mt-3">
                <a class="btn btn-outline-secondary w-100" href="notifications.php"><i class="bi bi-bell me-1"></i>View Notifications</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="hms-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Recent Appointments</h2>
                <a class="btn btn-sm btn-outline-primary" href="appointment-history.php">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-sortable>
                    <thead>
                    <tr>
                        <th data-sort>Patient</th>
                        <th data-sort>Doctor</th>
                        <th data-sort>Date</th>
                        <th data-sort>Status</th>
                        <th class="text-end">Fee</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentAppointments as $appointment) { ?>
                        <tr>
                            <td><?php echo hms_e($appointment['fullName']); ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo hms_e($appointment['doctorName']); ?></div>
                                <div class="small text-muted"><?php echo hms_e($appointment['doctorSpecialization']); ?></div>
                            </td>
                            <td><?php echo hms_e($appointment['appointmentDate']); ?> <?php echo hms_e($appointment['appointmentTime']); ?></td>
                            <td><?php echo hms_status_badge($appointment['status_name']); ?></td>
                            <td class="text-end"><?php echo hms_e($appointment['consultancyFees']); ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$recentAppointments) { ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No appointments yet.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">Recent Activity</h2>
            <?php if ($recentActivities) { ?>
                <div class="timeline">
                    <?php foreach ($recentActivities as $activity) { ?>
                        <div class="timeline-item">
                            <div class="fw-semibold"><?php echo hms_e(ucwords(str_replace('_', ' ', $activity['action']))); ?></div>
                            <div class="small text-muted"><?php echo hms_e($activity['actor_type']); ?> on <?php echo hms_e($activity['entity_type']); ?> #<?php echo hms_e($activity['entity_id']); ?></div>
                            <div class="small"><?php echo hms_e($activity['details']); ?></div>
                            <div class="small text-muted"><?php echo hms_e($activity['created_at']); ?></div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="text-muted">Apply the SQL migration to start recording audit activity.</div>
            <?php } ?>
        </div>
    </div>
</div>
<?php
$chartScript = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById("appointmentStatusChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode($statusLabels) . ',
        datasets: [{
            label: "Appointments",
            data: ' . json_encode($statusTotals) . ',
            backgroundColor: ["#f59e0b", "#127c8a", "#16a34a", "#64748b"],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>';
hms_layout_footer($chartScript);
?>
