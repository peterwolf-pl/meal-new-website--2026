CREATE TABLE media_assets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind TEXT NOT NULL,
    disk_path TEXT NULL,
    original_name TEXT NULL,
    external_url TEXT NULL,
    mime_type TEXT NULL,
    is_decorative INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE media_asset_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    media_asset_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    title TEXT NOT NULL,
    alt_text TEXT NULL,
    caption TEXT NULL,
    FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    UNIQUE (media_asset_id, locale)
);

CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_email TEXT NOT NULL,
    phone TEXT NOT NULL,
    street_address TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    city TEXT NOT NULL,
    map_url TEXT NULL,
    facebook_url TEXT NULL,
    instagram_url TEXT NULL,
    youtube_url TEXT NULL,
    hero_media_id INTEGER NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hero_media_id) REFERENCES media_assets (id) ON DELETE SET NULL
);

CREATE TABLE settings_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    settings_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    museum_name TEXT NOT NULL,
    organization_description TEXT NOT NULL,
    opening_hours TEXT NOT NULL,
    homepage_title TEXT NOT NULL,
    homepage_lead TEXT NOT NULL,
    homepage_intro TEXT NOT NULL,
    visit_note TEXT NULL,
    default_seo_title TEXT NOT NULL,
    default_meta_description TEXT NOT NULL,
    default_og_title TEXT NOT NULL,
    default_og_description TEXT NOT NULL,
    FOREIGN KEY (settings_id) REFERENCES settings (id) ON DELETE CASCADE,
    UNIQUE (settings_id, locale)
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE content_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content_type TEXT NOT NULL,
    section_key TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    sort_order INTEGER NOT NULL DEFAULT 0,
    featured INTEGER NOT NULL DEFAULT 0,
    published_at TEXT NULL,
    event_start TEXT NULL,
    event_end TEXT NULL,
    event_location TEXT NULL,
    registration_url TEXT NULL,
    collection_group TEXT NULL,
    creator_name TEXT NULL,
    item_year TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_content_visibility ON content_entries (status, content_type, section_key, featured, sort_order);
CREATE INDEX idx_content_event_dates ON content_entries (event_start, event_end);

CREATE TABLE content_entry_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content_entry_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    summary TEXT NULL,
    body TEXT NOT NULL,
    seo_title TEXT NULL,
    meta_description TEXT NULL,
    og_title TEXT NULL,
    og_description TEXT NULL,
    FOREIGN KEY (content_entry_id) REFERENCES content_entries (id) ON DELETE CASCADE,
    UNIQUE (content_entry_id, locale),
    UNIQUE (locale, slug)
);

CREATE TABLE content_entry_media (
    content_entry_id INTEGER NOT NULL,
    media_asset_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (content_entry_id, media_asset_id),
    FOREIGN KEY (content_entry_id) REFERENCES content_entries (id) ON DELETE CASCADE,
    FOREIGN KEY (media_asset_id) REFERENCES media_assets (id) ON DELETE CASCADE
);
