<?php
session_start();
include('include/config.php');

hms_require_role('admin', 'logout.php');

$hasTable = hms_table_exists($con, 'notifications');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hms_verify_csrf();
    if ($hasTable) {
        $id = hms_int(hms_post('id'));
        if ($id > 0) {
            hms_execute($con, 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_type = "admin"', 'i', array($id));
        } else {
            hms_execute($con, 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_type = "admin"');
        }
        hms_flash('success', 'Notifications updated.');
    }
    hms_redirect('notifications.php');
}

$notifications = $hasTable
    ? hms_fetch_all($con, 'SELECT * FROM notifications WHERE user_type = "admin" ORDER BY is_read ASC, created_at DESC LIMIT 100')
    : array();
$queries = hms_fetch_all($con, 'SELECT id, fullname, email, contactno, message FROM tblcontactus WHERE IsRead IS NULL ORDER BY id DESC LIMIT 10');

hms_layout_header('Notifications', 'admin', 'notifications');
?>
<div class="hms-page-title">
    <div>
        <h1>Notifications</h1>
        <div class="text-muted">New appointment requests, contact queries, and workflow alerts.</div>
    </div>
    <?php if ($hasTable && $notifications) { ?>
        <form method="post">
            <?php echo hms_csrf_field(); ?>
            <button class="btn btn-outline-primary" type="submit">Mark All Read</button>
        </form>
    <?php } ?>
</div>

<?php if (!$hasTable) { ?>
    <div class="alert alert-warning">The notifications table is not installed yet. Apply <code>database_improvements.sql</code> first.</div>
<?php } ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="hms-card p-3">
            <h2 class="h5 mb-3">System Notifications</h2>
            <?php if ($notifications) { ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification) { ?>
                        <div class="list-group-item px-0 d-flex gap-3 justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold"><?php echo hms_e($notification['title']); ?> <?php echo ((int) $notification['is_read'] === 0) ? '<span class="badge text-bg-primary">New</span>' : ''; ?></div>
                                <div class="text-muted small"><?php echo hms_e($notification['created_at']); ?></div>
                                <div><?php echo hms_e($notification['message']); ?></div>
                            </div>
                            <?php if ((int) $notification['is_read'] === 0) { ?>
                                <form method="post">
                                    <?php echo hms_csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo hms_e($notification['id']); ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Read</button>
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="text-muted">No system notifications yet.</div>
            <?php } ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="hms-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Unread Queries</h2>
                <a class="btn btn-sm btn-outline-primary" href="unread-queries.php">Open Queries</a>
            </div>
            <?php if ($queries) { ?>
                <div class="timeline">
                    <?php foreach ($queries as $query) { ?>
                        <div class="timeline-item">
                            <div class="fw-semibold"><?php echo hms_e($query['fullname']); ?></div>
                            <div class="small text-muted"><?php echo hms_e($query['email']); ?> | <?php echo hms_e($query['contactno']); ?></div>
                            <div class="small"><?php echo hms_e($query['message']); ?></div>
                            <a class="small" href="query-details.php?id=<?php echo hms_e($query['id']); ?>">View query</a>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="text-muted">No unread contact queries.</div>
            <?php } ?>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
