-- Add banner column to phim table
ALTER TABLE `phim` ADD COLUMN `banner` VARCHAR(255) DEFAULT NULL AFTER `poster`;

