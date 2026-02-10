-- Migration: add video columns for applicants
-- Run as: mysql -u root -p csnk < 20260205_add_video_columns.sql

ALTER TABLE applicants
  ADD COLUMN IF NOT EXISTS video_url VARCHAR(1024) NULL;

ALTER TABLE applicants
  ADD COLUMN IF NOT EXISTS video_provider ENUM('youtube','vimeo','file','other') NULL DEFAULT NULL;

ALTER TABLE applicants
  ADD COLUMN IF NOT EXISTS video_type ENUM('iframe','file') NOT NULL DEFAULT 'iframe';

-- Optional: set existing video paths to 'file' type if they point to uploads
UPDATE applicants
  SET video_type = 'file'
  WHERE video_url IS NOT NULL
    AND (video_type IS NULL OR video_type = '');
