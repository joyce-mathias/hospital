<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

$queries = hms_fetch_all($con, 'SELECT * FROM tblcontactus WHERE IsRead IS NULL ORDER BY id DESC');

hms_layout_header('Unread Queries', 'admin', 'notifications');
?>
<div class="hms-page-title">
    <div>
        <h1>Unread Queries</h1>
        <p class="text-muted mb-0">Review new public contact form submissions.</p>
    </div>
    <a class="btn btn-light" href="read-query.php">Read Queries</a>
</div>

<div class="hms-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h2 class="h5 mb-0">New Queries</h2>
        <input type="search" class="form-control" style="max-width: 320px;" placeholder="Search queries..." data-table-search="#queriesTable">
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="queriesTable" data-sortable>
            <thead>
            <tr>
                <th data-sort>#</th>
                <th data-sort>Name</th>
                <th data-sort>Email</th>
                <th data-sort>Contact</th>
                <th data-sort>Message</th>
                <th data-sort>Date</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($queries as $index => $query) { ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td class="fw-semibold"><?php echo hms_e($query['fullname']); ?></td>
                    <td><?php echo hms_e($query['email']); ?></td>
                    <td><?php echo hms_e($query['contactno']); ?></td>
                    <td><?php echo hms_e($query['message']); ?></td>
                    <td><?php echo hms_e($query['PostingDate']); ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="query-details.php?id=<?php echo (int) $query['id']; ?>"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$queries) { ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No unread queries.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php hms_layout_footer(); ?>
