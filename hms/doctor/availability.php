<?php
session_start();
include('include/config.php');

hms_require_role('doctor', 'logout.php');

$doctorId = (int) $_SESSION['id'];
$hasTable = hms_table_exists($con, 'doctor_availability');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    if (!$hasTable) {
        hms_flash('error', 'Run the SQL migration before managing availability.');
        hms_redirect('availability.php');
    }

    $action = hms_post('action');
    if ($action === 'delete') {
        $id = hms_int(hms_post('id'));
        hms_execute($con, 'DELETE FROM doctor_availability WHERE id = ? AND doctor_id = ?', 'ii', array($id, $doctorId));
        hms_log_audit($con, 'doctor', $doctorId, 'availability_deleted', 'doctor_availability', $id, '');
        hms_flash('success', 'Availability slot removed.');
        hms_redirect('availability.php');
    }

    $day = hms_int(hms_post('day_of_week'));
    $date = hms_post('available_date') !== '' ? hms_post('available_date') : null;
    $start = hms_normalize_time(hms_post('start_time'));
    $end = hms_normalize_time(hms_post('end_time'));
    $duration = max(5, hms_int(hms_post('slot_duration_minutes')));
    $maxAppointments = max(1, hms_int(hms_post('max_appointments')));

    if ($day < 0 || $day > 6 || !$start || !$end || $start >= $end) {
        hms_flash('error', 'Please enter a valid day and time range.');
        hms_redirect('availability.php');
    }

    hms_execute(
        $con,
        'INSERT INTO doctor_availability(doctor_id, day_of_week, available_date, start_time, end_time, slot_duration_minutes, max_appointments, is_active, created_at)
         VALUES(?, ?, ?, ?, ?, ?, ?, 1, NOW())',
        'iisssii',
        array($doctorId, $day, $date, $start, $end, $duration, $maxAppointments)
    );
    hms_log_audit($con, 'doctor', $doctorId, 'availability_created', 'doctor_availability', mysqli_insert_id($con), 'Created availability slot.');
    hms_flash('success', 'Availability slot saved.');
    hms_redirect('availability.php');
}

$slots = $hasTable
    ? hms_fetch_all($con, 'SELECT * FROM doctor_availability WHERE doctor_id = ? ORDER BY is_active DESC, available_date DESC, day_of_week ASC, start_time ASC', 'i', array($doctorId))
    : array();
$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

hms_layout_header('Doctor Availability', 'doctor', 'availability');
?>
<div class="hms-page-title">
    <div>
        <h1>Availability</h1>
        <div class="text-muted">Set weekly or date-specific slots that patients can book.</div>
    </div>
</div>

<?php if (!$hasTable) { ?>
    <div class="alert alert-warning">The doctor availability table is not installed yet. Apply <code>database_improvements.sql</code> first.</div>
<?php } ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">Add Slot</h2>
            <form method="post">
                <?php echo hms_csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Weekly Day</label>
                    <select class="form-select" name="day_of_week" required>
                        <?php foreach ($days as $index => $dayName) { ?>
                            <option value="<?php echo hms_e($index); ?>"><?php echo hms_e($dayName); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specific Date</label>
                    <input class="form-control" type="date" name="available_date">
                    <div class="form-text">Optional. Use it for one-day overrides.</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Start</label>
                        <input class="form-control" type="time" name="start_time" step="1800" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">End</label>
                        <input class="form-control" type="time" name="end_time" step="1800" required>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <label class="form-label">Slot Minutes</label>
                        <input class="form-control" type="number" name="slot_duration_minutes" min="5" value="30" required>
                    </div>
                    <!-- <div class="col-6">
                        <label class="form-label">Max Bookings</label>
                        <input class="form-control" type="number" name="max_appointments" min="1" value="1" required>
                    </div> -->
                </div>
                <button class="btn btn-primary w-100 mt-4" type="submit" <?php echo !$hasTable ? 'disabled' : ''; ?>>Save Slot</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">Current Slots</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-sortable>
                    <thead>
                    <tr>
                        <th data-sort>Day / Date</th>
                        <th data-sort>Time</th>
                        <th data-sort>Slot</th>
                        <th data-sort>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($slots as $slot) { ?>
                        <tr>
                            <td><?php echo hms_e($slot['available_date'] ?: $days[(int) $slot['day_of_week']]); ?></td>
                            <td><?php echo hms_e(substr($slot['start_time'], 0, 5)); ?> - <?php echo hms_e(substr($slot['end_time'], 0, 5)); ?></td>
                            <td><?php echo hms_e($slot['slot_duration_minutes']); ?> min</td>
                            <td><?php echo ((int) $slot['is_active'] === 1) ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>'; ?></td>
                            <td class="text-end">
                                <form method="post" onsubmit="return confirm('Remove this slot?');">
                                    <?php echo hms_csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo hms_e($slot['id']); ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$slots) { ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No availability slots found.</td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
