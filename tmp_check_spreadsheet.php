<?php
require __DIR__ . '/vendor/autoload.php';
echo 'CacheInterface: ' . (interface_exists('Psr\\SimpleCache\\CacheInterface') ? 'yes' : 'no') . PHP_EOL;
echo 'Spreadsheet: ' . (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? 'yes' : 'no') . PHP_EOL;
