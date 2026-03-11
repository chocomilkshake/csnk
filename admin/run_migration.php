<?php
/**
 * Migration Runner - Create csnk_branches table
 */

$conn = new mysqli('localhost', 'root', '', 'csnk');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create branches table
$sql = "CREATE TABLE IF NOT EXISTS csnk_branches (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50) NOT NULL UNIQUE,
    name            VARCHAR(255) NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      VARCHAR(100) NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by      VARCHAR(100) NULL,
    INDEX idx_csnk_branches_status (status),
    INDEX idx_csnk_branches_sort (sort_order),
    INDEX idx_csnk_branches_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table csnk_branches created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert default branch if empty
$result = $conn->query('SELECT COUNT(*) as cnt FROM csnk_branches');
$row = $result->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO csnk_branches (code, name, status, is_default, sort_order, created_by) VALUES ('CSNK-PH', 'CSNK Philippines', 'ACTIVE', 1, 0, 'system')");
    echo "Default branch inserted\n";
}

$conn->close();
echo "Migration completed!\n";

