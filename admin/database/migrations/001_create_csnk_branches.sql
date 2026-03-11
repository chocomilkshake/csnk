-- Migration: Create csnk_branches table
-- Version: 001
-- Description: Adds branches management table for CSNK Admin
-- Created: 2026-03-11

-- ============================================================
-- UP MIGRATION (Apply)
-- ============================================================

-- Create branches table if not exists
CREATE TABLE IF NOT EXISTS csnk_branches (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique branch code (e.g., CSNK-MNL)',
    name            VARCHAR(255) NOT NULL COMMENT 'Branch name',
    status          VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE or INACTIVE',
    is_default      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if this is the default branch',
    sort_order      INT NOT NULL DEFAULT 0 COMMENT 'Display order',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      VARCHAR(100) NULL COMMENT 'Admin username who created',
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by      VARCHAR(100) NULL COMMENT 'Admin username who updated',
    
    INDEX idx_csnk_branches_status (status),
    INDEX idx_csnk_branches_sort (sort_order),
    INDEX idx_csnk_branches_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default branch if table is empty
INSERT INTO csnk_branches (code, name, status, is_default, sort_order, created_by)
SELECT 'CSNK-PH', 'CSNK Philippines', 'ACTIVE', 1, 0, 'system'
WHERE NOT EXISTS (SELECT 1 FROM csnk_branches LIMIT 1);

-- ============================================================
-- DOWN MIGRATION (Rollback)
-- ============================================================

-- DROP TABLE IF EXISTS csnk_branches;

-- ============================================================
-- NOTES
-- ============================================================
-- This table stores CSNK branch locations for the agency.
-- Fields:
--   - id: Auto-increment primary key
--   - code: Unique branch code (e.g., CSNK-MNL, CSNK-CEBU)
--   - name: Branch display name
--   - status: ACTIVE or INACTIVE
--   - is_default: Set to 1 for default branch
--   - sort_order: For manual ordering in UI
--   - created_at/updated_at: Audit timestamps
--   - created_by/updated_by: Admin username
--
-- Usage in application:
--   SELECT * FROM csnk_branches WHERE status = 'ACTIVE' ORDER BY sort_order, name;
--   UPDATE csnk_branches SET is_default = 0; -- before setting new default
-- ============================================================

