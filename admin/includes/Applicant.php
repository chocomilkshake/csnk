<?php
class Applicant
{
    /** @var mysqli */
    private $db;

    /** CSNK agency code used for scoping */
    private const CSNK_AGENCY_CODE = 'csnk';

    public function __construct($database)
    {
        $this->db = $database->getConnection();
    }

    /* ============================================================
     * BU (Business Unit/Country) Helpers
     * ============================================================ */

    public function getAllBusinessUnits(bool $activeOnly = true): array
    {
        $rows = [];
        $where = $activeOnly ? " WHERE bu.active = 1 " : "";
        $sql = "
        SELECT 
            bu.id,
            bu.code,
            bu.name         AS bu_name,
            c.id            AS country_id,
            c.name          AS country_name,
            CONCAT(bu.code, ' — ', c.name) AS label
        FROM business_units bu
        JOIN countries c ON c.id = bu.country_id
        {$where}
        ORDER BY bu.code
    ";

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Get all CSNK branches (for branch selection in applicant forms)
     * Only returns active branches ordered by sort_order and name
     */
    public function getAllBranches(bool $activeOnly = true): array
    {
        $rows = [];
        $where = $activeOnly ? " WHERE status = 'ACTIVE' " : "";
        $sql = "SELECT id, code, name, is_default FROM csnk_branches {$where} ORDER BY sort_order ASC, name ASC";

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function isApplicantInBusinessUnit(int $applicantId, int $businessUnitId): bool
    {
        $stmt = $this->db->prepare("SELECT business_unit_id FROM applicants WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row && isset($row['business_unit_id']) && (int) $row['business_unit_id'] === $businessUnitId;
    }

    public function updateBusinessUnit(int $applicantId, int $businessUnitId): bool
    {
        $stmt = $this->db->prepare("UPDATE applicants SET business_unit_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $businessUnitId, $applicantId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get single CSNK branch details by ID (for agency scope display)
     */
    public function getBranchDetails(int $branchId): ?array
    {
        if ($branchId <= 0) {
            return null;
        }
        $sql = "SELECT id, code, name, is_default FROM csnk_branches WHERE id = ? AND status = 'ACTIVE' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getBranchDetails prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    /**
     * Get business unit details with country info by BU ID (for agency scope)
     */
    public function getBusinessUnitCountry(int $businessUnitId): ?array
    {
        if ($businessUnitId <= 0) {
            return null;
        }
        $sql = "
            SELECT 
                bu.id, bu.code, bu.name AS bu_name,
                c.id AS country_id, c.name AS country_name, c.iso2
            FROM business_units bu
            JOIN countries c ON c.id = bu.country_id
            WHERE bu.id = ? AND bu.active = 1
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getBusinessUnitCountry prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $businessUnitId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '|' . $column;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $tableEsc = str_replace('`', '``', $table);
        $columnEsc = $this->db->real_escape_string($column);
        $sql = "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'";

        $exists = false;
        try {
            if ($res = $this->db->query($sql)) {
                $exists = $res->num_rows > 0;
                $res->close();
            }
        } catch (\Throwable $e) {
            $exists = false;
        }

        $cache[$key] = $exists;
        return $exists;
    }

    private function resolveBusinessUnitIdByCode(string $code): ?int
    {
        $sql = "SELECT id FROM business_units WHERE code = ? AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return isset($row['id']) ? (int) $row['id'] : null;
    }

    private function getSessionScope(): array
    {
        $role = strtolower((string) ($_SESSION['admin_role'] ?? $_SESSION['role'] ?? ''));
        $agency = strtolower((string) ($_SESSION['agency'] ?? ''));
        $currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);
        $currentBranchId = (int) ($_SESSION['current_branch_id'] ?? 0);

        $scope = [
            'role' => $role,
            'agency' => in_array($agency, ['csnk', 'smc'], true) ? $agency : null,
            'is_employee' => $role === 'employee',
            'business_unit_id' => $currentBuId > 0 ? $currentBuId : null,
            'branch_id' => $currentBranchId > 0 ? $currentBranchId : null,
        ];

        if (!$scope['is_employee']) {
            return $scope;
        }

        if ($scope['agency'] === self::CSNK_AGENCY_CODE) {
            $csnkBusinessUnitId = $this->resolveBusinessUnitIdByCode('CSNK-PH');
            if ($csnkBusinessUnitId !== null) {
                $scope['business_unit_id'] = $csnkBusinessUnitId;
            }

            if (($scope['branch_id'] ?? 0) <= 0 && $currentBuId > 0 && $currentBuId !== (int) ($scope['business_unit_id'] ?? 0)) {
                $scope['branch_id'] = $currentBuId;
            }
        }

        return $scope;
    }

    private function applyAgencyBusinessUnitBranchScope(array &$where, string &$types, array &$params, ?int $businessUnitId = null, ?string $agency = null, ?int $branchId = null): void
    {
        $normalizedAgency = ($agency !== null && in_array($agency, ['csnk', 'smc'], true)) ? $agency : null;
        $scope = $this->getSessionScope();
        $hasBranchColumn = $this->tableHasColumn('applicants', 'branch_id');

        if ($scope['is_employee']) {
            if ($scope['agency'] !== null) {
                $where[] = " EXISTS (
                    SELECT 1 FROM business_units bu
                    JOIN agencies a ON a.id = bu.agency_id
                    WHERE bu.id = applicants.business_unit_id AND a.code = ?
                ) ";
                $types .= "s";
                $params[] = $scope['agency'];
            }

            $scopeBuId = (int) ($scope['business_unit_id'] ?? 0);
            if ($scopeBuId > 0) {
                if ($businessUnitId !== null && $businessUnitId > 0 && $businessUnitId !== $scopeBuId) {
                    $where[] = "1 = 0";
                    return;
                }

                $where[] = "applicants.business_unit_id = ?";
                $types .= "i";
                $params[] = $scopeBuId;
            }

            $scopeBranchId = (int) ($scope['branch_id'] ?? 0);
            if ($scope['agency'] === self::CSNK_AGENCY_CODE && $hasBranchColumn && $scopeBranchId > 0) {
                if ($branchId !== null && $branchId > 0 && $branchId !== $scopeBranchId) {
                    $where[] = "1 = 0";
                    return;
                }

                $where[] = "applicants.branch_id = ?";
                $types .= "i";
                $params[] = $scopeBranchId;
            }

            return;
        }

        if ($normalizedAgency !== null) {
            $where[] = " EXISTS (
                SELECT 1 FROM business_units bu
                JOIN agencies a ON a.id = bu.agency_id
                WHERE bu.id = applicants.business_unit_id AND a.code = ?
            ) ";
            $types .= "s";
            $params[] = $normalizedAgency;
        }

        if ($businessUnitId !== null && $businessUnitId > 0) {
            $where[] = "applicants.business_unit_id = ?";
            $types .= "i";
            $params[] = $businessUnitId;
        }

        if ($hasBranchColumn && $branchId !== null && $branchId > 0) {
            $where[] = "applicants.branch_id = ?";
            $types .= "i";
            $params[] = $branchId;
        }
    }

    private function getApplicantByIdRaw(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE id = ? LIMIT 1");
        if (!$stmt) {
            error_log('getApplicantByIdRaw prepare failed: ' . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    /* ============================================================
     * EXISTING METHODS (kept)
     * ============================================================ */

    public function getAll($status = null, ?int $businessUnitId = null, ?string $agency = null, ?int $branchId = null): array
    {
        $where = [];
        $types = '';
        $params = [];

        $this->applyAgencyBusinessUnitBranchScope($where, $types, $params, $businessUnitId, $agency, $branchId);

        $where[] = "applicants.deleted_at IS NULL";
        $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = applicants.id AND b.is_active = 1
    )";

        if ($status !== null) {
            $where[] = "applicants.status = ?";
            $types .= "s";
            $params[] = $status;
        }

        $sql = "SELECT * FROM applicants WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (getAll): ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        } else {
            $res = $this->db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
    }

    public function getAllForPublic(): array
    {
        $sql = "SELECT *
                FROM applicants
                WHERE deleted_at IS NULL
                  AND status IN ('pending','on_process')
                  AND NOT EXISTS (
                    SELECT 1 FROM blacklisted_applicants b
                    WHERE b.applicant_id = applicants.id AND b.is_active = 1
                  )
                ORDER BY created_at DESC";
        $res = $this->db->query($sql);
        if (!$res)
            return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeleted(?int $branchId = null, ?int $businessUnitId = null, ?string $agency = null)
    {
        $where = ["deleted_at IS NOT NULL"];
        $params = [];
        $types = '';

        $this->applyAgencyBusinessUnitBranchScope($where, $types, $params, $businessUnitId, $agency, $branchId);

        $where[] = "NOT EXISTS (
            SELECT 1 FROM blacklisted_applicants b
            WHERE b.applicant_id = applicants.id AND b.is_active = 1
        )";

        $sql = "SELECT * FROM applicants WHERE " . implode(" AND ", $where) . " ORDER BY deleted_at DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('getDeleted prepare failed: ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        }

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById($id)
    {
        $where = ["id = ?"];
        $types = "i";
        $params = [(int) $id];

        $this->applyAgencyBusinessUnitBranchScope($where, $types, $params);

        $stmt = $this->db->prepare("SELECT * FROM applicants WHERE " . implode(" AND ", $where) . " LIMIT 1");
        if (!$stmt) {
            error_log('getById prepare failed: ' . $this->db->error);
            return null;
        }

        $this->bindByRef($stmt, $types, $params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    public function create($data)
    {
        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        }

        $first = $data['first_name'] ?? null;
        $middle = $data['middle_name'] ?? null;
        $last = $data['last_name'] ?? null;
        $suffix = $data['suffix'] ?? null;
        $phone = $data['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $dob = $data['date_of_birth'] ?? null;
        $addr = $data['address'] ?? null;
        $educA = $data['educational_attainment'] ?? null;
        $workH = $data['work_history'] ?? null;
        $pref = $data['preferred_location'] ?? null;
        $langs = $data['languages'] ?? null;
        $skills = $data['specialization_skills'] ?? null;
        $pic = $data['picture'] ?? null;
        $status = $data['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : 0;
        $createdBy = isset($data['created_by']) ? (int) $data['created_by'] : 0;

        $scope = $this->getSessionScope();
        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;
        $branchId = isset($data['branch_id']) && $data['branch_id'] !== '' ? (int) $data['branch_id'] : null;

        if ($scope['is_employee']) {
            $scopeBuId = (int) ($scope['business_unit_id'] ?? 0);
            if ($scopeBuId > 0) {
                $businessUnitId = $scopeBuId;
            }

            if (($scope['agency'] ?? null) === self::CSNK_AGENCY_CODE) {
                $branchId = (int) ($scope['branch_id'] ?? 0) ?: null;
            } else {
                $branchId = null;
            }
        }

        if ($dailyRate === null) {
            $sql = "INSERT INTO applicants (
                    first_name, middle_name, last_name, suffix,
                    phone_number, alt_phone_number, email, date_of_birth, address,
                    educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                    picture, status, employment_type, education_level, years_experience, created_by, business_unit_id, country_id, branch_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create NULL rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "ssssssssssssssssssiiiii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $createdBy,
                $businessUnitId,
                $countryId,
                $branchId
            );
        } else {
            $sql = "INSERT INTO applicants (
                    first_name, middle_name, last_name, suffix,
                    phone_number, alt_phone_number, email, date_of_birth, address,
                    educational_attainment, work_history, daily_rate, preferred_location, languages, specialization_skills,
                    picture, status, employment_type, education_level, years_experience, created_by, business_unit_id, country_id, branch_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (create with rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "iiiii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $dailyRate,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $createdBy,
                $businessUnitId,
                $countryId,
                $branchId
            );
        }

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed (create): ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $newId = $this->db->insert_id;
        $stmt->close();
        return $newId ?: false;
    }

    public function update($id, $data)
    {
        $existing = $this->getById((int) $id);
        if (!$existing) {
            return false;
        }

        $dailyRate = null;
        if (isset($data['daily_rate']) && $data['daily_rate'] !== '') {
            $dailyRate = round((float) $data['daily_rate'], 2);
        } elseif (array_key_exists('daily_rate', $data)) {
            $dailyRate = null;
        } elseif ($existing['daily_rate'] !== null && $existing['daily_rate'] !== '') {
            $dailyRate = round((float) $existing['daily_rate'], 2);
        }

        $first = $data['first_name'] ?? $existing['first_name'] ?? null;
        $middle = $data['middle_name'] ?? $existing['middle_name'] ?? null;
        $last = $data['last_name'] ?? $existing['last_name'] ?? null;
        $suffix = $data['suffix'] ?? $existing['suffix'] ?? null;
        $phone = $data['phone_number'] ?? $existing['phone_number'] ?? null;
        $alt = $data['alt_phone_number'] ?? $existing['alt_phone_number'] ?? null;
        $email = $data['email'] ?? $existing['email'] ?? null;
        $dob = $data['date_of_birth'] ?? $existing['date_of_birth'] ?? null;
        $addr = $data['address'] ?? $existing['address'] ?? null;
        $educA = $data['educational_attainment'] ?? $existing['educational_attainment'] ?? null;
        $workH = $data['work_history'] ?? $existing['work_history'] ?? null;
        $pref = $data['preferred_location'] ?? $existing['preferred_location'] ?? null;
        $langs = $data['languages'] ?? $existing['languages'] ?? null;
        $skills = $data['specialization_skills'] ?? $existing['specialization_skills'] ?? null;
        $pic = array_key_exists('picture', $data) ? $data['picture'] : ($existing['picture'] ?? null);
        $status = $data['status'] ?? $existing['status'] ?? 'pending';
        $empTy = $data['employment_type'] ?? $existing['employment_type'] ?? null;
        $eduLv = $data['education_level'] ?? $existing['education_level'] ?? null;
        $years = isset($data['years_experience']) ? (int) $data['years_experience'] : (int) ($existing['years_experience'] ?? 0);

        $businessUnitId = isset($data['business_unit_id']) && $data['business_unit_id'] !== '' ? (int) $data['business_unit_id'] : (isset($existing['business_unit_id']) ? (int) $existing['business_unit_id'] : null);
        $countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int) $data['country_id'] : (isset($existing['country_id']) ? (int) $existing['country_id'] : null);
        $branchId = isset($data['branch_id']) && $data['branch_id'] !== '' ? (int) $data['branch_id'] : (isset($existing['branch_id']) && $existing['branch_id'] !== null ? (int) $existing['branch_id'] : null);

        $scope = $this->getSessionScope();
        if ($scope['is_employee']) {
            $scopeBuId = (int) ($scope['business_unit_id'] ?? 0);
            if ($scopeBuId > 0) {
                $businessUnitId = $scopeBuId;
            }

            if (($scope['agency'] ?? null) === self::CSNK_AGENCY_CODE) {
                $branchId = (int) ($scope['branch_id'] ?? 0) ?: null;
            } else {
                $branchId = null;
            }
        }

        if ($dailyRate === null) {
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = NULL, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?, business_unit_id = ?, country_id = ?, branch_id = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update NULL rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "ssssssssssssssssssiiiii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $businessUnitId,
                $countryId,
                $branchId,
                $id
            );
        } else {
            $sql = "UPDATE applicants SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                    phone_number = ?, alt_phone_number = ?, email = ?, date_of_birth = ?, address = ?,
                    educational_attainment = ?, work_history = ?, daily_rate = ?, preferred_location = ?, languages = ?, specialization_skills = ?,
                    picture = ?, status = ?, employment_type = ?, education_level = ?, years_experience = ?, business_unit_id = ?, country_id = ?, branch_id = ?
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('Prepare failed (update with rate): ' . $this->db->error);
                return false;
            }
            $stmt->bind_param(
                "sssssssssss" . "d" . "sssssss" . "iiiii",
                $first,
                $middle,
                $last,
                $suffix,
                $phone,
                $alt,
                $email,
                $dob,
                $addr,
                $educA,
                $workH,
                $dailyRate,
                $pref,
                $langs,
                $skills,
                $pic,
                $status,
                $empTy,
                $eduLv,
                $years,
                $businessUnitId,
                $countryId,
                $branchId,
                $id
            );
        }

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Execute failed (update): ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    public function updateVideoFields($id, $videoData)
    {
        $sql = "UPDATE applicants SET
                    video_url = ?, video_provider = ?, video_type = ?, 
                    video_title = ?, video_thumbnail_url = ?, video_duration_seconds = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssssii",
            $videoData['video_url'],
            $videoData['video_provider'],
            $videoData['video_type'],
            $videoData['video_title'],
            $videoData['video_thumbnail_url'],
            $videoData['video_duration_seconds'],
            $id
        );

        return $stmt->execute();
    }

    public function softDelete($id)
    {
        if (!$this->getById((int) $id)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NOW(), status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function restore($id)
    {
        if (!$this->getApplicantByIdRaw((int) $id) || !$this->getById((int) $id)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE applicants SET deleted_at = NULL, status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function permanentDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateStatus($id, $status)
    {
        if (!$this->getById((int) $id)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function markOnProcess(int $id): bool
    {
        if (!$this->getById($id)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getDocuments($applicantId)
    {
        $stmt = $this->db->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
        if (!$stmt) {
            error_log('getDocuments prepare failed: ' . $this->db->error);
            return [];
        }
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public function addDocument($applicantId, $documentType, $filePath)
    {
        $businessUnitId = (int) ($_SESSION['current_bu_id'] ?? 0);

        $sql = "
            INSERT INTO applicant_documents
                (applicant_id, business_unit_id, document_type, file_path)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                uploaded_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('addDocument prepare failed: ' . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iiss",
            $applicantId,
            $businessUnitId,
            $documentType,
            $filePath
        );

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteDocument($documentId)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteDocumentsByType($applicantId, $documentType)
    {
        $stmt = $this->db->prepare("DELETE FROM applicant_documents WHERE applicant_id = ? AND document_type = ?");
        $stmt->bind_param("is", $applicantId, $documentType);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getStatistics(?int $branchId = null)
    {
        $stats = [];
        $branchCond = $branchId > 0 ? "a.branch_id = $branchId AND " : '';

        $result = $this->db->query("
            SELECT COUNT(*) as total
            FROM applicants a
            WHERE {$branchCond} a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['total'] = $result->fetch_assoc()['total'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as pending
            FROM applicants a
            WHERE {$branchCond} a.status = 'pending'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['pending'] = $result->fetch_assoc()['pending'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as on_process
            FROM applicants a
            WHERE {$branchCond} a.status = 'on_process'
              AND a.deleted_at IS NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['on_process'] = $result->fetch_assoc()['on_process'] ?? 0;

        $result = $this->db->query("
            SELECT COUNT(*) as deleted
            FROM applicants a
            WHERE {$branchCond} a.deleted_at IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ");
        $stats['deleted'] = $result->fetch_assoc()['deleted'] ?? 0;

        return $stats;
    }

    public function getOnProcessWithLatestBooking(?int $branchId = null): array
    {
        $branchCond = $branchId > 0 ? "a.branch_id = $branchId AND " : '';

        $sql = "
            SELECT
                a.*,
                cb.id                AS booking_id,
                cb.appointment_type,
                cb.appointment_date,
                cb.appointment_time,
                cb.client_first_name,
                cb.client_middle_name,
                cb.client_last_name,
                cb.client_phone,
                cb.client_email,
                cb.client_address,
                cb.status            AS booking_status,
                cb.created_at        AS booking_created_at
            FROM applicants a
            LEFT JOIN (
                SELECT c1.*
                FROM client_bookings c1
                INNER JOIN (
                    SELECT applicant_id, MAX(created_at) AS max_created
                    FROM client_bookings
                    GROUP BY applicant_id
                ) t ON t.applicant_id = c1.applicant_id AND t.max_created = c1.created_at
            ) cb ON cb.applicant_id = a.id
            WHERE {$branchCond} a.deleted_at IS NULL
              AND a.status = 'on_process'
              AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
              )
            ORDER BY a.created_at DESC
        ";

        $res = $this->db->query($sql);
        if (!$res)
            return [];
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /* ============================================================
     * REPLACEMENT FEATURE — NEW METHODS
     * ============================================================ */

    private function ensureApplicantReplacementsTable(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS `applicant_replacements` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `business_unit_id` INT(10) UNSIGNED DEFAULT NULL,
          `original_applicant_id` INT(10) UNSIGNED NOT NULL,
          `replacement_applicant_id` INT(10) UNSIGNED DEFAULT NULL,
          `client_booking_id` INT(10) UNSIGNED DEFAULT NULL,
          `reason` ENUM('AWOL','Client Left','Not Finished Contract','Performance Issue','Other') NOT NULL,
          `report_text` TEXT NOT NULL,
          `attachments_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_json`)),
          `status` ENUM('selection','assigned','cancelled') NOT NULL DEFAULT 'selection',
          `created_by` INT(10) UNSIGNED DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `assigned_at` DATETIME DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_ar_original_applicant_id` (`original_applicant_id`),
          KEY `idx_ar_replacement_applicant_id` (`replacement_applicant_id`),
          KEY `idx_ar_client_booking_id` (`client_booking_id`),
          KEY `idx_ar_status` (`status`),
          KEY `idx_ar_business_unit_id` (`business_unit_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        try {
            $this->db->query($sql);
        } catch (\Throwable $e) { /* silent */
        }
    }

    /** Safe JSON decode */
    private function decodeJsonArray($val): array
    {
        if ($val === null || $val === '' || $val === '[]')
            return [];
        $arr = json_decode((string) $val, true);
        return is_array($arr) ? $arr : [];
    }

    private function normalizeStringArray(array $arr): array
    {
        $out = [];
        foreach ($arr as $v) {
            $s = strtolower(trim((string) $v));
            if ($s !== '' && !in_array($s, $out, true)) {
                $out[] = $s;
            }
        }
        return $out;
    }

    private function overlapCount(array $a, array $b): int
    {
        $a = $this->normalizeStringArray($a);
        $b = $this->normalizeStringArray($b);
        return count(array_intersect($a, $b));
    }

    private function bindByRef(mysqli_stmt $stmt, string $types, array $values): void
    {
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $v) {
            $values[$k] = $v;
            $refs[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    private function getAgencyCodeByApplicantId(int $applicantId): ?string
    {
        $sql = "
            SELECT ag.code AS agency_code
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            JOIN agencies ag ON ag.id = bu.agency_id
            WHERE a.id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getAgencyCodeByApplicantId prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param('i', $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row['agency_code'] ?? null;
    }

    public function getLatestBookingIdForApplicant(int $applicantId): ?int
    {
        $sql = "SELECT id FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('getLatestBookingIdForApplicant prepare failed: ' . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int) $row['id'] : null;
    }

    /** Map education level labels to ranks (higher is “better”) */
    private function getEducationRank(?string $label): int
    {
        if (!$label)
            return 0;
        static $map = [
        'Elementary Graduate' => 1,
        'Secondary Level (Attended High School)' => 2,
        'Secondary Graduate (Junior High School / Old Curriculum)' => 3,
        'Senior High School Graduate (K-12 Curriculum)' => 4,
        'Technical-Vocational / TESDA Graduate' => 5,
        'Tertiary Level (College Undergraduate)' => 6,
        'Tertiary Graduate (Bachelor’s Degree)' => 7,
        ];
        return $map[$label] ?? 0;
        // Note: keep in sync with enum values in DB
    }

    /**
     * Compute improved similarity score for candidate vs original.
     * Weights:
     *  - skills overlap:         x4
     *  - preferred cities:       x2
     *  - languages overlap:      x1
     *  - employment type match: +2
     *  - education >= original: +1
     *  - experience bonus:      + floor(years/2)  (0..6 for 0..12 yrs)
     *  - required docs complete: + min(completed, 3) (0..3)
     */
    private function computeSimilarityScore(array $original, array $candidate, int $docsCompleted): int
    {
        $origSkills = $this->decodeJsonArray($original['specialization_skills'] ?? '[]');
        $origCities = $this->decodeJsonArray($original['preferred_location'] ?? '[]');
        $origLangs = $this->decodeJsonArray($original['languages'] ?? '[]');
        $candSkills = $this->decodeJsonArray($candidate['specialization_skills'] ?? '[]');
        $candCities = $this->decodeJsonArray($candidate['preferred_location'] ?? '[]');
        $candLangs = $this->decodeJsonArray($candidate['languages'] ?? '[]');

        $skillOverlap = $this->overlapCount($origSkills, $candSkills);
        $cityOverlap = $this->overlapCount($origCities, $candCities);
        $langOverlap = $this->overlapCount($origLangs, $candLangs);

        $score = 0;
        $score += $skillOverlap * 4;
        $score += $cityOverlap * 2;
        $score += $langOverlap * 1;

        $origEmp = strtolower(trim((string) ($original['employment_type'] ?? '')));
        $candEmp = strtolower(trim((string) ($candidate['employment_type'] ?? '')));
        if ($origEmp !== '' && $origEmp === $candEmp)
            $score += 2;

        $origEduRank = $this->getEducationRank($original['education_level'] ?? null);
        $candEduRank = $this->getEducationRank($candidate['education_level'] ?? null);
        if ($candEduRank >= $origEduRank && $candEduRank > 0)
            $score += 1;

        $years = (int) ($candidate['years_experience'] ?? 0);
        $score += intdiv(max(0, min($years, 12)), 2); // 0..6

        $score += min(max(0, (int) $docsCompleted), 3); // reward up to 3

        return (int) $score;
    }

    /**
     * Return BEST pending candidates for replacement (CSNK-only), prioritizing same BU.
     * - Filters:
     *   status='pending', not deleted, not blacklisted, agency = CSNK
     * - Phase 1: same BU as original
     * - Phase 2 (fallback): any CSNK BU (if Phase 1 results < $limit)
     * - Includes required document completeness (for original’s BU country)
     * - Sorted by score DESC, docs_completed DESC, years_experience DESC, created_at ASC
     */
    public function searchPendingCandidatesForReplacement(int $originalApplicantId, int $limit = 50, ?int $branchId = null): array
    {
        $scope = $this->getSessionScope();
        $sessionBranchId = $scope['is_employee'] && $scope['agency'] === self::CSNK_AGENCY_CODE ? (int) $scope['branch_id'] : null;
        $effectiveBranchId = $branchId ?? $sessionBranchId;

        $original = $this->getById($originalApplicantId);
        if (!$original)
            return [];

        // Resolve BU + country + orig branch
        $origBuId = null;
        $origCountryId = null;
        $stmt = $this->db->prepare("
            SELECT bu.id AS bu_id, bu.country_id AS country_id, a.branch_id
            FROM applicants a JOIN business_units bu ON bu.id = a.business_unit_id
            WHERE a.id = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('i', $originalApplicantId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $origBuId = (int) $row['bu_id'];
                $origCountryId = (int) $row['country_id'];
            }
            $stmt->close();
        }
        if (!$origBuId)
            $origBuId = (int) ($original['business_unit_id'] ?? 0);
        if (!$origCountryId)
            $origCountryId = (int) ($original['country_id'] ?? 0);

        // Helper to fetch with docs + optional branch/BU filter
        $fetchCandidates = function (?int $buFilter, ?int $branchFilter, int $maxRows) use ($origCountryId) {
            $rows = [];
            $sql = "
                SELECT a.*, (
                    SELECT COUNT(*)
                    FROM applicant_documents ad
                    JOIN document_types dt ON dt.code COLLATE utf8mb4_general_ci = ad.document_type
                    WHERE ad.applicant_id = a.id AND dt.is_required = 1 AND dt.country_id = ?
                ) AS docs_completed
                FROM applicants a JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.status = 'pending' AND a.deleted_at IS NULL AND ag.code = ?
                  AND NOT EXISTS (SELECT 1 FROM blacklisted_applicants b WHERE b.applicant_id = a.id AND b.is_active = 1)
            ";
            $types = "is";
            $params = [$origCountryId, self::CSNK_AGENCY_CODE];

            if ($branchFilter && $this->tableHasColumn('applicants', 'branch_id')) {
                $sql .= " AND a.branch_id = ?";
                $types .= "i";
                $params[] = $branchFilter;
            }
            if ($buFilter > 0) {
                $sql .= " AND a.business_unit_id = ?";
                $types .= "i";
                $params[] = $buFilter;
            }
            $sql .= " ORDER BY a.created_at DESC LIMIT ?";
            $types .= "i";
            $params[] = $maxRows;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log('searchPendingCandidatesForReplacement prepare failed: ' . $this->db->error);
                return [];
            }
            $this->bindByRef($stmt, $types, $params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close();
            return $rows;
        };

        // STRICT branch-only query - NO Phase 2/3 fallbacks
        $size = max(300, $limit * 6);
        $rows = $fetchCandidates(null, $effectiveBranchId, $size);

        // Score candidates
        foreach ($rows as &$r) {
            $docsCompleted = (int) ($r['docs_completed'] ?? 0);
            $originalId = (int) $rep['original_applicant_id'];
            $repBuId = isset($rep['business_unit_id']) ? (int) $rep['business_unit_id'] : null;
            if ($originalId <= 0)
                throw new \RuntimeException('Invalid original applicant link.');

            $origAgency = $this->getAgencyCodeByApplicantId($originalId);
            $st->execute();
            $ro = $st->get_result();
            $origRow = $ro ? $ro->fetch_assoc() : null;
            $st->close();
            if (!$origRow)
            if (!$stmt)
                throw new \RuntimeException('Failed to prepare assignment update.');
            $stmt->bind_param('ii', $replacementApplicantId, $replaceId);
            $stmt->execute(
            $affectedAssign = $stmt->affected_rows;
            $stmt->close();
            if ($affectedAssign !== 1)
                throw new \RuntimeException('Failed to assign (already assigned?).');

            // Candidate -> on_process (+ report)
            if (in_array($candStatus, ['pending', 'approved'], true)) {
                $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $replacementApplicantId);
                    $stmt->execute();
                    $stmt->close();
                    $reportText = "Replacement assignment — moved from {$candStatus} to on_process.";
                    $stmt2 = $this->db->prepare("
                        INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                        VALUES (?, ?, ?, 'on_process', ?, ?)
                    ");
                    if ($stmt2) {
                        $stmt2->bind_param('iissi', $replacementApplicantId, $candBuId, $candStatus, $reportText, $adminId);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                }
            }

            // Original -> on_hold (+ status + note)
            $fromOriginal = $origStatus ?: 'approved';
            $stmt = $this->db->prepare("UPDATE applicants SET status = 'on_hold', updated_at = NOW() WHERE id = ? AND status <> 'on_hold' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $originalId);
                $stmt->execute();
                $stmt->close();
            }

            $origReport = "Replaced by Applicant ID {$replacementApplicantId}. Original moved to on_hold.";
            $stmt2 = $this->db->prepare("
                INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                VALUES (?, ?, ?, 'on_hold', ?, ?)
            ");
            if ($stmt2) {
                $stmt2->bind_param('iissi', $originalId, $origBuId, $fromOriginal, $origReport, $adminId);
                $stmt2->execute();
                $stmt2->close();
            }

            $stmt3 = $this->db->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
            if ($stmt3) {
                $note = "Replaced by Applicant ID {$replacementApplicantId}. Status moved to On Hold.";
                $stmt3->bind_param('iiis', $originalId, $origBuId, $adminId, $note);
                $stmt3->execute();
                $stmt3->close();
            }

            // Activity log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $action = 'Assign Replacement';
            $desc = "Assigned Applicant ID {$replacementApplicantId} as replacement for Original ID {$originalId}; original set to On Hold";
            $stmt = $this->db->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isss", $adminId, $action, $desc, $ip);
                $stmt->execute();
                $stmt->close();
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('assignReplacement error: ' . $e->getMessage());
            return false;
        }
    }

    /* ============================================================
     * COUNTRY FILTERING (for SMC international applicants)
     * ============================================================ */

    public function getCountriesWithCounts(?int $businessUnitId = null, ?int $branchId = null): array
    {
        $sql = "
            SELECT 
                c.id,
                c.name AS country_name,
                c.iso2
            FROM countries c
            WHERE c.active = 1
              AND c.id != 1
            ORDER BY c.name ASC
        ";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        $countries = $res->fetch_all(MYSQLI_ASSOC);

        $branchCond = $branchId > 0 ? "a.branch_id = $branchId AND " : '';
        $countSql = "
            SELECT 
                bu.country_id,
                COUNT(a.id) AS applicant_count
            FROM applicants a
            JOIN business_units bu ON bu.id = a.business_unit_id
            WHERE {$branchCond} a.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM blacklisted_applicants b
                  WHERE b.applicant_id = a.id AND b.is_active = 1
              )
        ";

        if ($businessUnitId !== null && $businessUnitId > 0) {
            $countSql .= " AND a.business_unit_id = " . (int) $businessUnitId;
        }

        $countSql .= " GROUP BY bu.country_id";

        $countRes = $this->db->query($countSql);
        $counts = [];
        if ($countRes) {
            while ($row = $countRes->fetch_assoc()) {
                $counts[(int) $row['country_id']] = (int) $row['applicant_count'];
            }
        }

        $result = [];
        foreach ($countries as $c) {
            $result[] = [
                'id' => (int) $c['id'],
                'name' => $c['country_name'],
                'iso2' => $c['iso2'],
                'count' => $counts[$c['id']] ?? 0
            ];
        }

        return $result;
    }

    public function getBusinessUnitsByCountry(?int $countryId = null): array
    {
        $sql = "
            SELECT 
                bu.id AS business_unit_id,
                bu.code,
                bu.name AS bu_name,
                c.id AS country_id,
                c.name AS country_name,
                c.iso2
            FROM business_units bu
            JOIN countries c ON c.id = bu.country_id
            WHERE bu.active = 1
              AND c.active = 1
              AND c.id != 1
        ";

        if ($countryId !== null && $countryId > 0) {
            $sql .= " AND c.id = " . (int) $countryId;
        }

        $sql .= " ORDER BY c.name ASC, bu.code ASC";

        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }

        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
