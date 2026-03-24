ALTER TABLE settings
    ADD COLUMN header_logo_media_id BIGINT UNSIGNED NULL AFTER hero_media_id,
    ADD COLUMN header_links_html TEXT NULL AFTER header_logo_media_id,
    ADD COLUMN header_show_cms_link TINYINT(1) NOT NULL DEFAULT 1 AFTER header_links_html,
    ADD COLUMN header_background_color VARCHAR(16) NOT NULL DEFAULT '#ffffff' AFTER header_show_cms_link,
    ADD CONSTRAINT fk_settings_header_logo_media FOREIGN KEY (header_logo_media_id) REFERENCES media_assets (id) ON DELETE SET NULL;
