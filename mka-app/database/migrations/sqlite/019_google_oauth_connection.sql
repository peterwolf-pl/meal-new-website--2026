ALTER TABLE settings
    ADD COLUMN google_oauth_refresh_token TEXT NULL;

ALTER TABLE settings
    ADD COLUMN google_oauth_email TEXT NULL;

ALTER TABLE settings
    ADD COLUMN google_oauth_connected_at TEXT NULL;
