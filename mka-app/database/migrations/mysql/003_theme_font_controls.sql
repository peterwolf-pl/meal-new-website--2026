ALTER TABLE settings
    ADD COLUMN body_font_size VARCHAR(64) NOT NULL DEFAULT '1rem' AFTER body_font_media_id,
    ADD COLUMN body_font_uppercase TINYINT(1) NOT NULL DEFAULT 0 AFTER body_font_size,
    ADD COLUMN body_font_letter_spacing VARCHAR(64) NOT NULL DEFAULT '0' AFTER body_font_uppercase,
    ADD COLUMN heading_font_size VARCHAR(64) NOT NULL DEFAULT 'clamp(1.8rem, 3vw, 2.8rem)' AFTER heading_font_media_id,
    ADD COLUMN heading_font_uppercase TINYINT(1) NOT NULL DEFAULT 0 AFTER heading_font_size,
    ADD COLUMN heading_font_letter_spacing VARCHAR(64) NOT NULL DEFAULT '-0.04em' AFTER heading_font_uppercase,
    ADD COLUMN menu_font_size VARCHAR(64) NOT NULL DEFAULT 'clamp(2rem, 4vw, 3.6rem)' AFTER menu_font_media_id,
    ADD COLUMN menu_font_uppercase TINYINT(1) NOT NULL DEFAULT 1 AFTER menu_font_size,
    ADD COLUMN menu_font_letter_spacing VARCHAR(64) NOT NULL DEFAULT '-0.04em' AFTER menu_font_uppercase,
    ADD COLUMN submenu_font_size VARCHAR(64) NOT NULL DEFAULT 'clamp(1.35rem, 2.7vw, 2.3rem)' AFTER menu_font_letter_spacing,
    ADD COLUMN submenu_font_uppercase TINYINT(1) NOT NULL DEFAULT 0 AFTER submenu_font_size,
    ADD COLUMN submenu_font_letter_spacing VARCHAR(64) NOT NULL DEFAULT '-0.04em' AFTER submenu_font_uppercase;
