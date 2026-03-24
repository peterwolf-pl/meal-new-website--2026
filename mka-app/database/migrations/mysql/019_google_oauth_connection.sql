ALTER TABLE settings
    ADD COLUMN google_oauth_refresh_token TEXT NULL AFTER google_service_account_json_path,
    ADD COLUMN google_oauth_email VARCHAR(190) NULL AFTER google_oauth_refresh_token,
    ADD COLUMN google_oauth_connected_at VARCHAR(40) NULL AFTER google_oauth_email;
