ALTER TABLE settings
    ADD COLUMN menu_background_color VARCHAR(16) NOT NULL DEFAULT '#ffffff' AFTER menu_font_size;
