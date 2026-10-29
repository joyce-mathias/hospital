<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

$logs = hms_fetch_all($con, 'SELECT * FROM doctorslog ORDER BY id DESC');

hms_layout_header('Doctor Session Logs', 'admin', 'audit');
?>
<div class="hms-page-title">
    <div>
        <h1>Doctor Session Logs</h1>
        <p class="text-muted mb-0">Track doctor login attempts and logout timestamps.</p>
    </div>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">Doctor Login Logs</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search logs..." data-table-search="#doctorLogsTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="doctorLogsTable" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Doctor ID</th>
                <th data-sort>Username</th>
                <th data-sort>IP Address</th>
                <th data-sort>Login Time</th>
                <th data-sort>Logout Time</th>
                <th data-sort>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $index => $log) { ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo hms_e($log['uid']); ?></td>
                    <td><?php echo hms_e($log['username']); ?></td>
                    <td><?php echo hms_e($log['userip']); ?></td>
                    <td><?php echo hms_e($log['loginTime']); ?></td>
                    <td><?php echo hms_e($log['logout'] ?: 'Active / not recorded'); ?></td>
                    <td><span class="badge text-bg-<?php echo (int) $log['status'] === 1 ? 'success' : 'danger'; ?>"><?php echo (int) $log['status'] === 1 ? 'Success' : 'Failed'; ?></span></td>
                </tr>
            <?php } ?>
            <?php if (!$logs) { ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No doctor logs found.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
