<?php
require_once 'admin/includes/config.php';
require_once 'admin/includes/Database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tableName = 'admin_users';
$query = "DESCRIBE $tableName";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo implode("\t", $row) . PHP_EOL;
    }
} else {
    echo "Error describing table: " . $conn->error . PHP_EOL;
}

$conn->close();
?>
