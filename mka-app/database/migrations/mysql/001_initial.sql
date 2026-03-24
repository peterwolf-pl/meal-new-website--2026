CREATE TABLE media_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(16) NOT NULL,
    disk_path VARCHAR(500) NULL,
    original_name VARCHAR(255) NULL,
    external_url VARCHAR(500) NULL,
    mime_type VARCHAR(120) NULL,
    is_decorative TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_asset_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_asset_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    caption TEXT NULL,
    CONSTRAINT fk_media_translation_asset FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT ux_media_translation UNIQUE (media_asset_id, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_email VARCHAR(190) NOT NULL,
    phone VARCHAR(64) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(32) NOT NULL,
    city VARCHAR(190) NOT NULL,
    map_url VARCHAR(500) NULL,
    facebook_url VARCHAR(500) NULL,
    instagram_url VARCHAR(500) NULL,
    youtube_url VARCHAR(500) NULL,
    hero_media_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_hero_media FOREIGN KEY (hero_media_id) REFERENCES media_assets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    settings_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    museum_name VARCHAR(255) NOT NULL,
    organization_description TEXT NOT NULL,
    opening_hours TEXT NOT NULL,
    homepage_title VARCHAR(255) NOT NULL,
    homepage_lead TEXT NOT NULL,
    homepage_intro LONGTEXT NOT NULL,
    visit_note TEXT NULL,
    default_seo_title VARCHAR(255) NOT NULL,
    default_meta_description VARCHAR(320) NOT NULL,
    default_og_title VARCHAR(255) NOT NULL,
    default_og_description VARCHAR(320) NOT NULL,
    CONSTRAINT fk_settings_translation_settings FOREIGN KEY (settings_id) REFERENCES settings (id) ON DELETE CASCADE,
    CONSTRAINT ux_settings_translation UNIQUE (settings_id, locale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(190) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ux_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE content_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(32) NOT NULL,
    section_key VARCHAR(32) NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    event_start DATETIME NULL,
    event_end DATETIME NULL,
    event_location VARCHAR(255) NULL,
    registration_url VARCHAR(500) NULL,
    collection_group VARCHAR(32) NULL,
    creator_name VARCHAR(255) NULL,
    item_year VARCHAR(32) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_content_visibility (status, content_type, section_key, featured, sort_order),
    INDEX idx_content_event_dates (event_start, event_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE content_entry_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_entry_id BIGINT UNSIGNED NOT NULL,
    locale VARCHAR(5) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    body LONGTEXT NOT NULL,
    seo_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    og_title VARCHAR(255) NULL,
    og_description VARCHAR(320) NULL,
    CONSTRAINT fk_content_translation_entry FOREIGN KEY (content_entry_id) REFERENCES content_entries (id) ON DELETE CASCADE,
    CONSTRAINT ux_content_translation UNIQUE (content_entry_id, locale),
    CONSTRAINT ux_content_slug_locale UNIQUE (locale, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE content_entry_media (
    content_entry_id BIGINT UNSIGNED NOT NULL,
    media_asset_id BIGINT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (content_entry_id, media_asset_id),
    CONSTRAINT fk_content_media_entry FOREIGN KEY (content_entry_id) REFERENCES content_entries (id) ON DELETE CASCADE,
    CONSTRAINT fk_content_media_asset FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
