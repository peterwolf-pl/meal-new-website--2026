ALTER TABLE settings
    ADD COLUMN body_font_weight SMALLINT NOT NULL DEFAULT 400 AFTER body_font_size,
    ADD COLUMN heading_font_weight SMALLINT NOT NULL DEFAULT 600 AFTER heading_font_size,
    ADD COLUMN menu_font_weight SMALLINT NOT NULL DEFAULT 500 AFTER menu_font_size,
    ADD COLUMN submenu_font_weight SMALLINT NOT NULL DEFAULT 500 AFTER submenu_font_size;
