ALTER TABLE settings
    ADD COLUMN body_font_preset VARCHAR(64) NOT NULL DEFAULT 'editorial-sans' AFTER hero_media_id,
    ADD COLUMN body_font_media_id BIGINT UNSIGNED NULL AFTER body_font_preset,
    ADD COLUMN heading_font_preset VARCHAR(64) NOT NULL DEFAULT 'editorial-sans' AFTER body_font_media_id,
    ADD COLUMN heading_font_media_id BIGINT UNSIGNED NULL AFTER heading_font_preset,
    ADD COLUMN menu_font_preset VARCHAR(64) NOT NULL DEFAULT 'editorial-sans' AFTER heading_font_media_id,
    ADD COLUMN menu_font_media_id BIGINT UNSIGNED NULL AFTER menu_font_preset,
    ADD CONSTRAINT fk_settings_body_font_media FOREIGN KEY (body_font_media_id) REFERENCES media_assets (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_settings_heading_font_media FOREIGN KEY (heading_font_media_id) REFERENCES media_assets (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_settings_menu_font_media FOREIGN KEY (menu_font_media_id) REFERENCES media_assets (id) ON DELETE SET NULL;
