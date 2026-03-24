ALTER TABLE settings
    ADD COLUMN gtm_container_id VARCHAR(32) NULL AFTER youtube_url,
    ADD COLUMN ga4_measurement_id VARCHAR(32) NULL AFTER gtm_container_id,
    ADD COLUMN ga4_property_id VARCHAR(32) NULL AFTER ga4_measurement_id,
    ADD COLUMN search_console_property_url VARCHAR(255) NULL AFTER ga4_property_id,
    ADD COLUMN google_service_account_json_path VARCHAR(500) NULL AFTER search_console_property_url;
