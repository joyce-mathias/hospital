<?php
session_start();
include('include/config.php');

hms_require_role('admin', 'logout.php');

$hasTable = hms_table_exists($con, 'audit_logs');
$actor = hms_get('actor');
$action = hms_get('action');
$where = array();
$types = '';
$params = array();
if ($actor !== '') {
    $where[] = 'actor_type = ?';
    $types .= 's';
    $params[] = $actor;
}
if ($action !== '') {
    $where[] = 'action LIKE ?';
    $types .= 's';
    $params[] = '%' . $action . '%';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$logs = $hasTable
    ? hms_fetch_all($con, 'SELECT * FROM audit_logs' . $whereSql . ' ORDER BY id DESC LIMIT 200', $types, $params)
    : array();

hms_layout_header('Audit Logs', 'admin', 'audit');
?>
<div class="hms-page-title">
    <div>
        <h1>Audit Logs</h1>
        <div class="text-muted">Track sensitive workflow changes and security-relevant actions.</div>
    </div>
</div>

<?php if (!$hasTable) { ?>
    <div class="alert alert-warning">The audit_logs table is not installed yet. Apply <code>database_improvements.sql</code> first.</div>
<?php } ?>

<div class="hms-card p-3 mb-3">
    <form class="row g-3 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">Actor</label>
            <select class="form-select" name="actor">
                <option value="">All</option>
                <?php foreach (array('admin', 'doctor', 'patient') as $option) { ?>
                    <option value="<?php echo hms_e($option); ?>" <?php echo $actor === $option ? 'selected' : ''; ?>><?php echo hms_e(ucfirst($option)); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Action</label>
            <input class="form-control" name="action" value="<?php echo hms_e($action); ?>" placeholder="appointment, prescription, availability">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="hms-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-sortable>
            <thead>
            <tr>
                <th data-sort>Date</th>
                <th data-sort>Actor</th>
                <th data-sort>Action</th>
                <th data-sort>Entity</th>
                <th>Details</th>
                <th data-sort>IP</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log) { ?>
                <tr>
                    <td><?php echo hms_e($log['created_at']); ?></td>
                    <td><?php echo hms_e($log['actor_type']); ?> #<?php echo hms_e($log['actor_id']); ?></td>
                    <td><?php echo hms_e($log['action']); ?></td>
                    <td><?php echo hms_e($log['entity_type']); ?> #<?php echo hms_e($log['entity_id']); ?></td>
                    <td><?php echo hms_e($log['details']); ?></td>
                    <td><?php echo hms_e($log['ip_address']); ?></td>
                </tr>
            <?php } ?>
            <?php if (!$logs) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No audit events found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
