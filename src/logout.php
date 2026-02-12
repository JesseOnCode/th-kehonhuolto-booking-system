<?php
/**
 * LOGOUT.PHP
 */
session_start();
session_unset();
session_destroy();

header("Location: admin_login.php?msg=logged_out");
exit;
?>