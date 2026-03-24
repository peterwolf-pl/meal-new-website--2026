ALTER TABLE settings
    ADD COLUMN header_font_color VARCHAR(16) NOT NULL DEFAULT '#181614' AFTER header_background_color;
