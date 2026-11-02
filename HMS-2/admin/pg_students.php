<?php
require_once("../Includes/config.php");
require_once("../Includes/session.php");
require_role('admin', '../admin_login.php');

if (empty($_SESSION['pg_logged']) || $_SESSION['pg_logged'] !== true || ($_SESSION['pg_role'] ?? '') !== 'admin') {
    header("Location: ../pg/login.php");
    exit;
}

header("Location: ../pg/admin/students.php");
exit;
