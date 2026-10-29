<?php
session_start();
include 'include/config.php';
hms_require_role('admin', 'logout.php');

hms_layout_header('Between Dates Report', 'admin', 'reports');
?>
<div class="hms-page-title">
    <div>
        <h1>Between Dates Report</h1>
        <p class="text-muted mb-0">Generate a patient registration report for a selected date range.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 col-xl-6">
        <div class="hms-card p-4">
            <form method="post" action="betweendates-detailsreports.php" class="row g-3">
                <?php echo hms_csrf_field(); ?>
                <div class="col-md-6">
                    <label class="form-label" for="fromdate">From date</label>
                    <input type="date" class="form-control" id="fromdate" name="fromdate" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="todate">To date</label>
                    <input type="date" class="form-control" id="todate" name="todate" required>
                </div>
                <div class="col-12">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text me-1"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php hms_layout_footer(); ?>
