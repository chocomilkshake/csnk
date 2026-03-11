<?php
// Run branch migration manually
$conn = new mysqli('localhost', 'root', '', 'csnk');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if column exists
$result = $conn->query('SHOW COLUMNS FROM applicants LIKE "branch_id"');
if ($result->num_rows == 0) {
    // Add column
    $conn->query('ALTER TABLE `applicants` ADD COLUMN `branch_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `business_unit_id`');
    echo 'Column branch_id added successfully<br>';

    // Add foreign key (ignore if fails)
    @$conn->query('ALTER TABLE `applicants` ADD CONSTRAINT `fk_applicants_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `csnk_branches`(`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    echo 'Foreign key added (or already exists)<br>';

    // Add index
    $conn->query('ALTER TABLE `applicants` ADD INDEX `idx_applicants_branch_id` (`branch_id`)');
    echo 'Index added (or already exists)<br>';
} else {
    echo 'Column branch_id already exists';
}

$conn->close();
echo '<br>Done! <a href="index.php">Go to Dashboard</a>';

