<?php
require_once 'includes/functions.php';

// Clear session
session_destroy();

// Clear remember me cookie
clearUserCookie();

// Redirect to login page
header('Location: inedx.php');
exit();
?> 