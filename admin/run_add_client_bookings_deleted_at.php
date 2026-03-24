<?php
/**
 * Migration: Add deleted_at column + index to client_bookings table for soft deletes
 * Usage: php admin/run_add_client_bookings_deleted_at.php
 */

echo "=== Client Bookings Soft Delete Migration ===\n";

$conn = new mysqli('localhost', 'root', '', 'csnk');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM client_bookings LIKE 'deleted_at'");
if ($result && $result->num_rows > 0) {
    echo "✓ deleted_at column already exists. Skipping ALTER.\n";
} else {
    // Add deleted_at column
    $sql = "ALTER TABLE client_bookings 
            ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
            ADD INDEX `idx_client_bookings_deleted_at` (`deleted_at`)";

    if ($conn->query($sql) === TRUE) {
        echo "✓ Added deleted_at column and index successfully.\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
        exit(1);
    }
}

// Verify
$count = $conn->query("SELECT COUNT(*) as cnt FROM client_bookings WHERE deleted_at IS NULL")->fetch_assoc()['cnt'];
echo "✓ Verified: {$count} active (non-deleted) bookings found.\n";

$conn->close();
echo "Migration completed successfully!\n";
?>