ALTER TABLE settings
    ADD COLUMN footer_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER hero_media_id,
    ADD COLUMN footer_bar_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER footer_enabled,
    ADD COLUMN footer_bar_text TEXT NULL AFTER footer_bar_enabled;
