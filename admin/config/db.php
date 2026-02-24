<?php
$mysqli = mysqli_connect('localhost', 'root', '', 'csnk');
if (!$mysqli) {
    die('DB connect error: ' . mysqli_connect_error());
}
mysqli_set_charset($mysqli, 'utf8mb4');

session_start();