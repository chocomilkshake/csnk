<?php
/**
 * Repair script for broken client_bookings IDs.
 *
 * What it does:
 * - Reassigns any client_bookings rows with id = 0 to fresh unique IDs
 * - Updates applicant_replacements.client_booking_id for matching broken references
 * - Converts client_bookings.id into a PRIMARY KEY AUTO_INCREMENT column
 *
 * Usage:
 *   php admin/run_fix_client_bookings_ids.php
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';

$database = new Database();
$conn = $database->getConnection();

if (!($conn instanceof mysqli)) {
    fwrite(STDERR, "Database connection is not available.\n");
    exit(1);
}

$conn->set_charset('utf8mb4');

function fail_and_exit(mysqli $conn, string $message): void
{
    if ($conn->errno) {
        $message .= ' MySQL: ' . $conn->error;
    }
    fwrite(STDERR, $message . "\n");
    $conn->rollback();
    exit(1);
}

$summary = [
    'fixed_bookings' => 0,
    'fixed_replacements' => 0,
    'made_primary_key' => false,
    'made_auto_increment' => false,
];

try {
    $conn->begin_transaction();

    $schemaResult = $conn->query("
        SELECT COLUMN_KEY, EXTRA
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'client_bookings'
          AND COLUMN_NAME = 'id'
        LIMIT 1
    ");
    if (!$schemaResult) {
        fail_and_exit($conn, 'Unable to inspect client_bookings.id schema.');
    }
    $schema = $schemaResult->fetch_assoc() ?: ['COLUMN_KEY' => '', 'EXTRA' => ''];
    $schemaResult->close();

    $maxIdResult = $conn->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM client_bookings");
    if (!$maxIdResult) {
        fail_and_exit($conn, 'Unable to read current max client booking ID.');
    }
    $nextId = ((int) ($maxIdResult->fetch_assoc()['max_id'] ?? 0)) + 1;
    $maxIdResult->close();

    $brokenRowsSql = "
        SELECT id, applicant_id, business_unit_id, client_first_name, client_last_name, created_at
        FROM client_bookings
        WHERE id = 0
        ORDER BY created_at ASC, applicant_id ASC, business_unit_id ASC
    ";
    $brokenRowsResult = $conn->query($brokenRowsSql);
    if (!$brokenRowsResult) {
        fail_and_exit($conn, 'Unable to load broken client booking rows.');
    }

    $brokenRows = [];
    while ($row = $brokenRowsResult->fetch_assoc()) {
        $brokenRows[] = $row;
    }
    $brokenRowsResult->close();

    foreach ($brokenRows as $row) {
        $applicantId = (int) $row['applicant_id'];
        $businessUnitId = (int) $row['business_unit_id'];
        $createdAt = (string) $row['created_at'];
        $newId = $nextId++;

        $updateBooking = $conn->prepare("
            UPDATE client_bookings
            SET id = ?
            WHERE id = 0
              AND applicant_id = ?
              AND business_unit_id = ?
              AND created_at = ?
            LIMIT 1
        ");
        if (!$updateBooking) {
            fail_and_exit($conn, 'Unable to prepare client booking ID repair query.');
        }
        $updateBooking->bind_param('iiis', $newId, $applicantId, $businessUnitId, $createdAt);
        if (!$updateBooking->execute()) {
            $updateBooking->close();
            fail_and_exit($conn, 'Failed while updating a broken client booking ID.');
        }
        if ($updateBooking->affected_rows !== 1) {
            $updateBooking->close();
            fail_and_exit($conn, "Expected to fix exactly 1 booking row for applicant {$applicantId} at {$createdAt}, but did not.");
        }
        $updateBooking->close();
        $summary['fixed_bookings']++;

        $updateReplacements = $conn->prepare("
            UPDATE applicant_replacements
            SET client_booking_id = ?
            WHERE client_booking_id = 0
              AND original_applicant_id = ?
              AND (
                    business_unit_id IS NULL
                    OR business_unit_id = ?
                  )
        ");
        if (!$updateReplacements) {
            fail_and_exit($conn, 'Unable to prepare applicant replacement repair query.');
        }
        $updateReplacements->bind_param('iii', $newId, $applicantId, $businessUnitId);
        if (!$updateReplacements->execute()) {
            $updateReplacements->close();
            fail_and_exit($conn, 'Failed while updating broken applicant replacement booking references.');
        }
        $summary['fixed_replacements'] += max(0, (int) $updateReplacements->affected_rows);
        $updateReplacements->close();
    }

    if ($schema['COLUMN_KEY'] !== 'PRI') {
        if (!$conn->query("ALTER TABLE client_bookings ADD PRIMARY KEY (id)")) {
            fail_and_exit($conn, 'Failed to add PRIMARY KEY to client_bookings.id.');
        }
        $summary['made_primary_key'] = true;
    }

    $isAutoIncrement = stripos((string) $schema['EXTRA'], 'auto_increment') !== false;
    if (!$isAutoIncrement) {
        if (!$conn->query("ALTER TABLE client_bookings MODIFY id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT")) {
            fail_and_exit($conn, 'Failed to convert client_bookings.id to AUTO_INCREMENT.');
        }
        $summary['made_auto_increment'] = true;
    }

    if (!$conn->query("ALTER TABLE client_bookings AUTO_INCREMENT = {$nextId}")) {
        fail_and_exit($conn, 'Failed to move client_bookings AUTO_INCREMENT forward.');
    }

    $conn->commit();

    echo "Client booking ID repair completed successfully.\n";
    echo "- Fixed booking rows: {$summary['fixed_bookings']}\n";
    echo "- Fixed replacement references: {$summary['fixed_replacements']}\n";
    echo "- Added primary key: " . ($summary['made_primary_key'] ? 'yes' : 'already present') . "\n";
    echo "- Added auto increment: " . ($summary['made_auto_increment'] ? 'yes' : 'already present') . "\n";
    echo "- Next AUTO_INCREMENT value: {$nextId}\n";
} catch (Throwable $e) {
    fail_and_exit($conn, 'Repair aborted: ' . $e->getMessage());
}
