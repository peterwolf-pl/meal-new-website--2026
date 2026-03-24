ALTER TABLE settings
    ADD COLUMN menu_content_background_color VARCHAR(16) NOT NULL DEFAULT '#ffffff' AFTER menu_active_background_color;
