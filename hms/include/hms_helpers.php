<?php
if (!defined('HMS_HELPERS_LOADED')) {
    define('HMS_HELPERS_LOADED', true);

    function hms_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    function hms_post($key, $default = '')
    {
        return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
    }

    function hms_get($key, $default = '')
    {
        return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
    }

    function hms_int($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : 0;
    }

    function hms_redirect($location)
    {
        header('Location: ' . $location);
        exit;
    }

    function hms_require_role($role, $redirect = 'logout.php')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['id'])) {
            hms_redirect($redirect);
        }
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] !== $role) {
                hms_redirect($redirect);
            }
            return true;
        }

        $path = isset($_SERVER['PHP_SELF']) ? str_replace('\\', '/', $_SERVER['PHP_SELF']) : '';
        $legacyOk = false;
        if ($role === 'doctor') {
            $legacyOk = !empty($_SESSION['dlogin']) && strpos($path, '/doctor/') !== false;
        } elseif ($role === 'admin') {
            $legacyOk = !empty($_SESSION['login']) && strpos($path, '/admin/') !== false;
        } elseif ($role === 'patient') {
            $legacyOk = !empty($_SESSION['login']) && strpos($path, '/admin/') === false && strpos($path, '/doctor/') === false;
        }
        if (!$legacyOk) {
            hms_redirect($redirect);
        }
        $_SESSION['role'] = $role;
        return true;
    }

    function hms_flash($type, $message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['hms_flash'] = array('type' => $type, 'message' => $message);
    }

    function hms_get_flash()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flash = isset($_SESSION['hms_flash']) ? $_SESSION['hms_flash'] : null;
        unset($_SESSION['hms_flash']);
        return $flash;
    }

    function hms_csrf_token()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function hms_csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . hms_e(hms_csrf_token()) . '">';
    }

    function hms_verify_csrf()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('Invalid request token.');
        }
    }

    function hms_stmt($con, $sql, $types = '', $params = array())
    {
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            throw new RuntimeException('Database statement failed.');
        }

        if ($types !== '' && !empty($params)) {
            $bind = array_merge(array($types), $params);
            $refs = array();
            foreach ($bind as $key => $value) {
                $refs[$key] = &$bind[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }

        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Database query failed.');
        }

        return $stmt;
    }

    function hms_fetch_all($con, $sql, $types = '', $params = array())
    {
        $stmt = hms_stmt($con, $sql, $types, $params);
        $result = mysqli_stmt_get_result($stmt);
        $rows = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    function hms_result($con, $sql, $types = '', $params = array())
    {
        $stmt = hms_stmt($con, $sql, $types, $params);
        return mysqli_stmt_get_result($stmt);
    }

    function hms_fetch_one($con, $sql, $types = '', $params = array())
    {
        $rows = hms_fetch_all($con, $sql, $types, $params);
        return isset($rows[0]) ? $rows[0] : null;
    }

    function hms_scalar($con, $sql, $types = '', $params = array())
    {
        $row = hms_fetch_one($con, $sql, $types, $params);
        if (!$row) {
            return 0;
        }
        $values = array_values($row);
        return isset($values[0]) ? $values[0] : 0;
    }

    function hms_execute($con, $sql, $types = '', $params = array())
    {
        $stmt = hms_stmt($con, $sql, $types, $params);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }

    function hms_table_exists($con, $table)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }
        return (int) hms_scalar(
            $con,
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            's',
            array($table)
        ) > 0;
    }

    function hms_column_exists($con, $table, $column)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }
        return (int) hms_scalar(
            $con,
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            'ss',
            array($table, $column)
        ) > 0;
    }

    function hms_appointment_status_expr($con, $alias = 'appointment')
    {
        $prefix = $alias ? '`' . $alias . '`.' : '';
        if (hms_column_exists($con, 'appointment', 'status')) {
            return 'COALESCE(NULLIF(' . $prefix . '`status`, ""), CASE WHEN ' . $prefix . '`userStatus` = 0 OR ' . $prefix . '`doctorStatus` = 0 THEN "Cancelled" ELSE "Pending" END)';
        }
        return 'CASE WHEN ' . $prefix . '`userStatus` = 0 OR ' . $prefix . '`doctorStatus` = 0 THEN "Cancelled" ELSE "Pending" END';
    }

    function hms_status_from_row($row)
    {
        if (isset($row['status']) && $row['status'] !== '') {
            return $row['status'];
        }
        if ((isset($row['userStatus']) && (int) $row['userStatus'] === 0) || (isset($row['doctorStatus']) && (int) $row['doctorStatus'] === 0)) {
            return 'Cancelled';
        }
        return 'Pending';
    }

    function hms_status_badge($status)
    {
        $status = ucfirst(strtolower((string) $status));
        $classes = array(
            'Pending' => 'text-bg-warning',
            'Approved' => 'text-bg-primary',
            'Completed' => 'text-bg-success',
            'Cancelled' => 'text-bg-secondary'
        );
        $class = isset($classes[$status]) ? $classes[$status] : 'text-bg-light';
        return '<span class="badge rounded-pill ' . $class . '">' . hms_e($status) . '</span>';
    }

    function hms_valid_status($status)
    {
        return in_array($status, array('Pending', 'Approved', 'Completed', 'Cancelled'), true);
    }

    function hms_set_appointment_status($con, $appointmentId, $status, $actorType, $actorId)
    {
        if (!hms_valid_status($status)) {
            return false;
        }

        $userStatus = 1;
        $doctorStatus = 1;
        if ($status === 'Cancelled') {
            if ($actorType === 'patient') {
                $userStatus = 0;
            } else {
                $doctorStatus = 0;
            }
        }

        $fields = array();
        $types = '';
        $params = array();

        if (hms_column_exists($con, 'appointment', 'status')) {
            $fields[] = 'status = ?';
            $types .= 's';
            $params[] = $status;
        }
        $fields[] = 'userStatus = ?';
        $fields[] = 'doctorStatus = ?';
        $types .= 'ii';
        $params[] = $userStatus;
        $params[] = $doctorStatus;

        if (hms_column_exists($con, 'appointment', 'status_updated_at')) {
            $fields[] = 'status_updated_at = NOW()';
        }
        if (hms_column_exists($con, 'appointment', 'status_updated_by')) {
            $fields[] = 'status_updated_by = ?';
            $types .= 's';
            $params[] = $actorType . ':' . (int) $actorId;
        }

        $types .= 'i';
        $params[] = (int) $appointmentId;
        hms_execute($con, 'UPDATE appointment SET ' . implode(', ', $fields) . ' WHERE id = ?', $types, $params);
        hms_log_audit($con, $actorType, $actorId, 'appointment_status_changed', 'appointment', $appointmentId, 'Status changed to ' . $status);
        return true;
    }

    function hms_log_audit($con, $actorType, $actorId, $action, $entityType, $entityId = null, $details = '')
    {
        if (!hms_table_exists($con, 'audit_logs')) {
            return false;
        }
        hms_execute(
            $con,
            'INSERT INTO audit_logs(actor_type, actor_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES(?, ?, ?, ?, ?, ?, ?, NOW())',
            'sississ',
            array((string) $actorType, (int) $actorId, (string) $action, (string) $entityType, $entityId ? (int) $entityId : null, (string) $details, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')
        );
        return true;
    }

    function hms_notify($con, $userType, $userId, $title, $message)
    {
        if (!hms_table_exists($con, 'notifications')) {
            return false;
        }
        hms_execute(
            $con,
            'INSERT INTO notifications(user_type, user_id, title, message, is_read, created_at) VALUES(?, ?, ?, ?, 0, NOW())',
            'siss',
            array((string) $userType, $userId === null ? null : (int) $userId, (string) $title, (string) $message)
        );
        return true;
    }

    function hms_password_matches($plain, $stored)
    {
        if ($stored === null || $stored === '') {
            return false;
        }
        if (password_get_info($stored)['algo']) {
            return password_verify($plain, $stored);
        }
        if (hash_equals((string) $stored, md5($plain))) {
            return true;
        }
        return hash_equals((string) $stored, (string) $plain);
    }

    function hms_password_needs_upgrade($stored)
    {
        return !password_get_info((string) $stored)['algo'] || password_needs_rehash((string) $stored, PASSWORD_DEFAULT);
    }

    function hms_upgrade_password($con, $table, $idColumn, $id, $plain)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $idColumn)) {
            return false;
        }
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        hms_execute($con, 'UPDATE `' . $table . '` SET password = ? WHERE `' . $idColumn . '` = ?', 'si', array($hash, (int) $id));
        return true;
    }

    function hms_normalize_time($time)
    {
        $timestamp = strtotime((string) $time);
        if ($timestamp === false) {
            return null;
        }
        return date('H:i:s', $timestamp);
    }

    function hms_is_valid_future_date($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return false;
        }
        $today = new DateTime('today');
        return $parsed >= $today;
    }

    function hms_doctor_available($con, $doctorId, $date, $time, &$reason = '', $excludeAppointmentId = 0)
    {
        $doctorId = (int) $doctorId;
        $time = hms_normalize_time($time);
        if (!$doctorId || !hms_is_valid_future_date($date) || !$time) {
            $reason = 'Please choose a valid future date and time.';
            return false;
        }

        $statusExpr = hms_appointment_status_expr($con, 'appointment');
        $conflictSql = 'SELECT COUNT(*) FROM appointment WHERE doctorId = ? AND appointmentDate = ? AND appointmentTime = ? AND ' . $statusExpr . ' <> "Cancelled"';
        $conflictTypes = 'iss';
        $conflictParams = array($doctorId, $date, $time);
        if ((int) $excludeAppointmentId > 0) {
            $conflictSql .= ' AND id <> ?';
            $conflictTypes .= 'i';
            $conflictParams[] = (int) $excludeAppointmentId;
        }
        $conflict = (int) hms_scalar($con, $conflictSql, $conflictTypes, $conflictParams);

        if ($conflict > 0) {
            $reason = 'This doctor already has an appointment at that time.';
            return false;
        }

        if (!hms_table_exists($con, 'doctor_availability')) {
            return true;
        }

        $day = (int) (new DateTime($date))->format('w');
        $slot = hms_fetch_one(
            $con,
            'SELECT * FROM doctor_availability WHERE doctor_id = ? AND is_active = 1 AND (available_date = ? OR (available_date IS NULL AND day_of_week = ?)) AND start_time <= ? AND end_time > ? ORDER BY available_date DESC, start_time ASC LIMIT 1',
            'isiss',
            array($doctorId, $date, $day, $time, $time)
        );

        if (!$slot) {
            $reason = 'The doctor is not available at the selected date and time.';
            return false;
        }

        $max = isset($slot['max_appointments']) ? (int) $slot['max_appointments'] : 1;
        if ($max < 1) {
            $reason = 'The selected slot is closed.';
            return false;
        }

        return true;
    }

    function hms_availability_summary($con, $doctorId)
    {
        if (!hms_table_exists($con, 'doctor_availability')) {
            return 'Availability schedule has not been configured yet.';
        }
        $rows = hms_fetch_all(
            $con,
            'SELECT * FROM doctor_availability WHERE doctor_id = ? AND is_active = 1 ORDER BY available_date DESC, day_of_week ASC, start_time ASC LIMIT 8',
            'i',
            array((int) $doctorId)
        );
        if (!$rows) {
            return 'No active availability slots.';
        }
        $days = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        $parts = array();
        foreach ($rows as $row) {
            $label = $row['available_date'] ? $row['available_date'] : $days[(int) $row['day_of_week']];
            $parts[] = $label . ' ' . substr($row['start_time'], 0, 5) . '-' . substr($row['end_time'], 0, 5);
        }
        return implode(', ', $parts);
    }

    function hms_send_csv($filename, $headers, $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    function hms_pdf_escape($text)
    {
        return str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), (string) $text);
    }

    function hms_send_basic_pdf($filename, $title, $lines)
    {
        $content = "BT\n/F1 16 Tf\n50 790 Td\n(" . hms_pdf_escape($title) . ") Tj\n/F1 10 Tf\n0 -24 Td\n";
        foreach ($lines as $line) {
            $content .= '(' . hms_pdf_escape(substr((string) $line, 0, 115)) . ") Tj\n0 -14 Td\n";
        }
        $content .= "ET";

        $objects = array();
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = array(0);
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
        exit;
    }

    function hms_current_user_name($con, $role)
    {
        $id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
        if ($role === 'patient') {
            $row = hms_fetch_one($con, 'SELECT fullName FROM users WHERE id = ?', 'i', array($id));
            return $row ? $row['fullName'] : 'Patient';
        }
        if ($role === 'doctor') {
            $row = hms_fetch_one($con, 'SELECT doctorName FROM doctors WHERE id = ?', 'i', array($id));
            return $row ? $row['doctorName'] : 'Doctor';
        }
        return 'Admin';
    }

    function hms_nav_items($role)
    {
        if ($role === 'admin') {
            return array(
                array('dashboard', 'Dashboard', 'dashboard.php', 'bi-speedometer2'),
                array('appointments', 'Appointments', 'appointment-history.php', 'bi-calendar-check'),
                array('availability', 'Availability', 'doctor-availability.php', 'bi-clock-history'),
                array('notifications', 'Notifications', 'notifications.php', 'bi-bell'),
                array('audit', 'Audit Logs', 'audit-logs.php', 'bi-shield-check'),
                array('doctors', 'Doctors', 'manage-doctors.php', 'bi-person-badge'),
                array('patients', 'Patients', 'manage-patient.php', 'bi-people'),
                array('reports', 'Reports', 'between-dates-reports.php', 'bi-file-earmark-text')
            );
        }
        if ($role === 'doctor') {
            return array(
                array('dashboard', 'Dashboard', 'dashboard.php', 'bi-speedometer2'),
                array('appointments', 'Appointments', 'appointment-history.php', 'bi-calendar-check'),
                array('availability', 'Availability', 'availability.php', 'bi-clock-history'),
                array('prescriptions', 'Prescriptions', 'prescriptions.php', 'bi-file-medical'),
                array('patients', 'Patients', 'manage-patient.php', 'bi-people'),
                array('search', 'Search', 'search.php', 'bi-search')
            );
        }
        return array(
            array('dashboard', 'Dashboard', 'dashboard.php', 'bi-speedometer2'),
            array('book', 'Book Appointment', 'book-appointment.php', 'bi-calendar-plus'),
            array('appointments', 'Appointments', 'appointment-history.php', 'bi-calendar-check'),
            array('history', 'Medical History', 'manage-medhistory.php', 'bi-journal-medical'),
            array('profile', 'Profile', 'edit-profile.php', 'bi-person')
        );
    }

    function hms_auth_header($title, $heading, $subtitle = '', $homeHref = '../index.php')
    {
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo hms_e($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --auth-bg: #f5f8fb;
            --auth-ink: #1f2937;
            --auth-muted: #667085;
            --auth-line: #d9e2ec;
            --auth-teal: #127c8a;
            --auth-navy: #17324d;
            --auth-amber: #b7791f;
        }
        body {
            min-height: 100vh;
            margin: 0;
            background:
                linear-gradient(135deg, rgba(18, 124, 138, .11), rgba(183, 121, 31, .08)),
                var(--auth-bg);
            color: var(--auth-ink);
            letter-spacing: 0;
        }
        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(280px, 480px) minmax(0, 1fr);
        }
        .auth-panel {
            background: #fff;
            border-right: 1px solid var(--auth-line);
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-card {
            border: 1px solid var(--auth-line);
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(16, 24, 40, .08);
            padding: 28px;
        }
        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--auth-navy);
            font-weight: 750;
            margin-bottom: 24px;
        }
        .auth-brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: var(--auth-teal);
        }
        .auth-card h1 {
            font-size: 26px;
            line-height: 1.2;
            margin-bottom: 8px;
            font-weight: 750;
        }
        .auth-muted { color: var(--auth-muted); }
        .auth-side {
            padding: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-side-inner {
            max-width: 560px;
        }
        .auth-metric {
            background: rgba(255, 255, 255, .76);
            border: 1px solid rgba(217, 226, 236, .85);
            border-radius: 8px;
            padding: 18px;
            min-height: 120px;
        }
        .form-control, .form-select, .btn { border-radius: 7px; }
        .btn-primary {
            background: var(--auth-teal);
            border-color: var(--auth-teal);
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #0f6671;
            border-color: #0f6671;
        }
        a { color: var(--auth-teal); text-decoration: none; }
        a:hover { color: #0f6671; text-decoration: underline; }
        @media (max-width: 991.98px) {
            .auth-shell { display: block; }
            .auth-side { display: none; }
            .auth-panel {
                min-height: 100vh;
                border-right: 0;
                padding: 20px;
            }
            .auth-card { padding: 22px; }
        }
    </style>
</head>
<body>
<div class="auth-shell">
    <section class="auth-panel">
        <div class="auth-card">
            <a class="auth-brand" href="<?php echo hms_e($homeHref); ?>">
                <span class="auth-brand-icon"><i class="bi bi-hospital"></i></span>
                <span>Hospital Management System</span>
            </a>
            <h1><?php echo hms_e($heading); ?></h1>
            <?php if ($subtitle !== '') { ?><p class="auth-muted mb-4"><?php echo hms_e($subtitle); ?></p><?php } ?>
            <?php
            $flash = hms_get_flash();
            if ($flash) {
                $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
                echo '<div class="alert alert-' . hms_e($type) . ' alert-dismissible fade show" role="alert">' . hms_e($flash['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
    }

    function hms_auth_footer($extraScripts = '')
    {
        ?>
        </div>
    </section>
    <aside class="auth-side">
        <div class="auth-side-inner">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 bg-white border mb-4">
                <i class="bi bi-shield-check text-success"></i>
                <span class="small fw-semibold">Secure access for hospital teams</span>
            </div>
            <h2 class="display-6 fw-bold mb-3">Manage care workflows with clarity.</h2>
            <p class="auth-muted fs-5 mb-4">Appointments, patient records, prescriptions, and doctor availability stay organized in one responsive system.</p>
            <div class="row g-3">
                <div class="col-sm-4"><div class="auth-metric"><i class="bi bi-calendar-check fs-3 text-success"></i><div class="fw-bold mt-3">Appointments</div><div class="small auth-muted">Track every status</div></div></div>
                <div class="col-sm-4"><div class="auth-metric"><i class="bi bi-file-medical fs-3 text-primary"></i><div class="fw-bold mt-3">Prescriptions</div><div class="small auth-muted">Downloadable records</div></div></div>
                <div class="col-sm-4"><div class="auth-metric"><i class="bi bi-clock-history fs-3" style="color: var(--auth-amber);"></i><div class="fw-bold mt-3">Availability</div><div class="small auth-muted">Doctor schedules</div></div></div>
            </div>
        </div>
    </aside>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php echo $extraScripts; ?>
</body>
</html>
<?php
    }

    function hms_layout_header($title, $role, $active = 'dashboard')
    {
        global $con;
        $name = hms_current_user_name($con, $role);
        $items = hms_nav_items($role);
        $logout = $role === 'admin' ? 'logout.php' : 'logout.php';
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo hms_e($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --hms-ink: #1f2937;
            --hms-muted: #667085;
            --hms-line: #d9e2ec;
            --hms-bg: #f6f8fb;
            --hms-sidebar: #17324d;
            --hms-sidebar-active: #256f7a;
            --hms-teal: #127c8a;
            --hms-amber: #b7791f;
        }
        body { background: var(--hms-bg); color: var(--hms-ink); font-size: 14px; letter-spacing: 0; }
        a { text-decoration: none; }
        .hms-shell { min-height: 100vh; display: grid; grid-template-columns: 260px minmax(0, 1fr); }
        .hms-sidebar { background: var(--hms-sidebar); color: #fff; padding: 18px 14px; position: sticky; top: 0; height: 100vh; }
        .hms-brand { display: flex; align-items: center; gap: 10px; padding: 8px 10px 20px; font-weight: 700; }
        .hms-brand-icon { width: 34px; height: 34px; border-radius: 8px; background: #e6fffb; color: var(--hms-teal); display: inline-flex; align-items: center; justify-content: center; }
        .hms-nav a { color: #dbeafe; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; margin-bottom: 4px; }
        .hms-nav a.active, .hms-nav a:hover { background: var(--hms-sidebar-active); color: #fff; }
        .hms-main { min-width: 0; }
        .hms-topbar { background: #fff; border-bottom: 1px solid var(--hms-line); min-height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 10; }
        .hms-content { padding: 24px; }
        .hms-page-title { display: flex; justify-content: space-between; gap: 16px; align-items: center; margin-bottom: 20px; }
        .hms-page-title h1 { font-size: 26px; margin: 0; font-weight: 700; }
        .hms-card { background: #fff; border: 1px solid var(--hms-line); border-radius: 8px; box-shadow: 0 8px 24px rgba(16, 24, 40, .04); }
        .hms-stat { padding: 18px; min-height: 118px; }
        .hms-stat .icon { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #e6fffb; color: var(--hms-teal); font-size: 20px; }
        .hms-stat .value { font-size: 28px; font-weight: 750; margin-top: 12px; }
        .hms-stat .label { color: var(--hms-muted); }
        .table thead th { color: #475467; background: #f8fafc; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        .table td, .table th { vertical-align: middle; }
        .btn { border-radius: 7px; }
        .form-control, .form-select { border-radius: 7px; }
        .timeline { position: relative; padding-left: 28px; }
        .timeline:before { content: ""; position: absolute; left: 9px; top: 4px; bottom: 4px; width: 2px; background: var(--hms-line); }
        .timeline-item { position: relative; padding-bottom: 18px; }
        .timeline-item:before { content: ""; position: absolute; left: -23px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--hms-teal); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--hms-line); }
        .mobile-menu-btn { display: none; }
        @media (max-width: 991.98px) {
            .hms-shell { display: block; }
            .hms-sidebar { display: none; }
            .mobile-menu-btn { display: inline-flex; }
            .hms-content { padding: 16px; }
            .hms-page-title { align-items: flex-start; flex-direction: column; }
        }
        @media (max-width: 575.98px) {
            .hms-topbar { padding: 0 14px; }
            .hms-page-title h1 { font-size: 22px; }
            .hms-stat .value { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="hms-shell">
    <aside class="hms-sidebar">
        <div class="hms-brand"><span class="hms-brand-icon"><i class="bi bi-hospital"></i></span><span>HMS</span></div>
        <nav class="hms-nav">
            <?php foreach ($items as $item) { ?>
                <a class="<?php echo $active === $item[0] ? 'active' : ''; ?>" href="<?php echo hms_e($item[2]); ?>">
                    <i class="bi <?php echo hms_e($item[3]); ?>"></i><span><?php echo hms_e($item[1]); ?></span>
                </a>
            <?php } ?>
        </nav>
    </aside>
    <div class="hms-main">
        <header class="hms-topbar">
            <button class="btn btn-outline-secondary mobile-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Open navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="fw-semibold">Hospital Management System</div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i><?php echo hms_e($name); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($role !== 'admin') { ?><li><a class="dropdown-item" href="edit-profile.php">Profile</a></li><?php } ?>
                    <li><a class="dropdown-item" href="change-password.php">Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo hms_e($logout); ?>">Log Out</a></li>
                </ul>
            </div>
        </header>
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">HMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <nav class="list-group">
                    <?php foreach ($items as $item) { ?>
                        <a class="list-group-item list-group-item-action <?php echo $active === $item[0] ? 'active' : ''; ?>" href="<?php echo hms_e($item[2]); ?>">
                            <i class="bi <?php echo hms_e($item[3]); ?> me-2"></i><?php echo hms_e($item[1]); ?>
                        </a>
                    <?php } ?>
                </nav>
            </div>
        </div>
        <main class="hms-content">
<?php
        $flash = hms_get_flash();
        if ($flash) {
            $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
            echo '<div class="alert alert-' . hms_e($type) . ' alert-dismissible fade show" role="alert">' . hms_e($flash['message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    }

    function hms_layout_footer($extraScripts = '')
    {
        ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-table-search]').forEach(function (input) {
    input.addEventListener('input', function () {
        var selector = input.getAttribute('data-table-search');
        var table = document.querySelector(selector);
        if (!table) return;
        var query = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.hidden = row.textContent.toLowerCase().indexOf(query) === -1;
        });
    });
});
document.querySelectorAll('table[data-sortable] th[data-sort]').forEach(function (th, index) {
    th.style.cursor = 'pointer';
    th.addEventListener('click', function () {
        var table = th.closest('table');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var direction = th.dataset.direction === 'asc' ? 'desc' : 'asc';
        th.dataset.direction = direction;
        rows.sort(function (a, b) {
            var av = a.children[index].textContent.trim().toLowerCase();
            var bv = b.children[index].textContent.trim().toLowerCase();
            return direction === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(function (row) { tbody.appendChild(row); });
    });
});
</script>
<?php echo $extraScripts; ?>
</body>
</html>
<?php
    }
}
?>
