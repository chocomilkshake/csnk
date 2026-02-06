<?php
// Quick tester for includes/get_applicants.php
$_GET = [];
$_GET['page'] = 1;
$_GET['per_page'] = 5;
$_GET['q'] = 'dixon';
//$_GET['location'] = 'manila';
// Example: multiple specializations (as array)
// $_GET['specializations'] = ['Childcare & Maternity (Yaya)'];
include __DIR__ . '/includes/get_applicants.php';
