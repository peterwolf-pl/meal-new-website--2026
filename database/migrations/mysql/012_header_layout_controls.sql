ALTER TABLE settings
    ADD COLUMN header_height VARCHAR(64) NOT NULL DEFAULT '50px' AFTER header_background_color,
    ADD COLUMN header_logo_padding VARCHAR(64) NOT NULL DEFAULT '0' AFTER header_height,
    ADD COLUMN header_logo_margin VARCHAR(64) NOT NULL DEFAULT '0' AFTER header_logo_padding;
