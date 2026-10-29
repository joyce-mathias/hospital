<?php
require_once('include/config.php');
if (!empty($_POST['emailid'])) {
    $email = hms_post('emailid');
    $count = (int) hms_scalar($con, 'SELECT COUNT(*) FROM doctors WHERE docEmail = ?', 's', array($email));
    if ($count > 0) {
        echo "<span style='color:red'> Email already exists.</span>";
        echo "<script>$('#submit').prop('disabled',true);</script>";
    } else {
        echo "<span style='color:green'> Email available for registration.</span>";
        echo "<script>$('#submit').prop('disabled',false);</script>";
    }
}
?>
