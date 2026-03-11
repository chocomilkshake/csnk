<?php
/**
 * Migration Runner - Fix applicants table AUTO_INCREMENT
 * Run this script to fix the duplicate key error on applicants table
 */

$conn = new mysqli('localhost', 'root', '', 'csnk');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Starting migration to fix applicants AUTO_INCREMENT...\n";

// First, check the current table structure
$result = $conn->query("SHOW CREATE TABLE applicants");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nCurrent table structure:\n";
    echo $row['Create Table'] . "\n";
}

// Get current AUTO_INCREMENT
$result = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'csnk' AND TABLE_NAME = 'applicants'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "\nCurrent AUTO_INCREMENT value: " . $row['AUTO_INCREMENT'] . "\n";
}

// Get max id
$result = $conn->query("SELECT MAX(id) as max_id FROM applicants");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Current MAX(id): " . ($row['max_id'] ?? 'NULL') . "\n";
}

// Modify the id column to be AUTO_INCREMENT
$sql = "ALTER TABLE `applicants` MODIFY COLUMN `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT";

if ($conn->query($sql) === TRUE) {
    echo "\n✅ Successfully modified applicants table to use AUTO_INCREMENT!\n";
} else {
    echo "\n❌ Error modifying table: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

// Verify the change
$result = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'csnk' AND TABLE_NAME = 'applicants'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "New AUTO_INCREMENT value: " . $row['AUTO_INCREMENT'] . "\n";
}

$conn->close();
echo "\nMigration completed successfully!\n";

