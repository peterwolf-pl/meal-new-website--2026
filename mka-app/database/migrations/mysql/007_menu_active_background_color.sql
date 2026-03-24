ALTER TABLE settings
    ADD COLUMN menu_active_background_color VARCHAR(16) NOT NULL DEFAULT '#ffffff' AFTER menu_background_color;
