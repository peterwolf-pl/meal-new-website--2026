ALTER TABLE settings ADD COLUMN body_font_preset TEXT NOT NULL DEFAULT 'editorial-sans';
ALTER TABLE settings ADD COLUMN body_font_media_id INTEGER NULL;
ALTER TABLE settings ADD COLUMN heading_font_preset TEXT NOT NULL DEFAULT 'editorial-sans';
ALTER TABLE settings ADD COLUMN heading_font_media_id INTEGER NULL;
ALTER TABLE settings ADD COLUMN menu_font_preset TEXT NOT NULL DEFAULT 'editorial-sans';
ALTER TABLE settings ADD COLUMN menu_font_media_id INTEGER NULL;
