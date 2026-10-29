<?php
session_start();
include('include/config.php');
$_SESSION['dlogin']=="";
date_default_timezone_set('Asia/Kolkata');
$ldate=date( 'd-m-Y h:i:s A', time () );
$did=$_SESSION['id'];
if (!empty($did)) {
	hms_execute($con,"UPDATE doctorslog SET logout = ? WHERE uid = ? ORDER BY id DESC LIMIT 1", 'si', array($ldate, (int) $did));
}
session_unset();
//session_destroy();
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
document.location="../../index.php";
</script>
