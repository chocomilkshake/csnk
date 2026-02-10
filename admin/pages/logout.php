<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
$auth = new Auth($database);

$auth->logout();

header('Location: login.php');
exit();
