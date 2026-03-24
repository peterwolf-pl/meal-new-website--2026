ALTER TABLE settings
    ADD COLUMN gtm_container_id TEXT NULL;

ALTER TABLE settings
    ADD COLUMN ga4_measurement_id TEXT NULL;

ALTER TABLE settings
    ADD COLUMN ga4_property_id TEXT NULL;

ALTER TABLE settings
    ADD COLUMN search_console_property_url TEXT NULL;

ALTER TABLE settings
    ADD COLUMN google_service_account_json_path TEXT NULL;
