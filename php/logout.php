<?php
/**
 * Logout Handler
 * SummitSphere Retail Management System
 */

require_once 'config/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit;
