ALTER TABLE settings
    ADD COLUMN heading_font_capitalize TINYINT(1) NOT NULL DEFAULT 0 AFTER heading_font_size,
    ADD COLUMN menu_font_capitalize TINYINT(1) NOT NULL DEFAULT 0 AFTER menu_font_size,
    ADD COLUMN submenu_font_capitalize TINYINT(1) NOT NULL DEFAULT 0 AFTER submenu_font_size;
