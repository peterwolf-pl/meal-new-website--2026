CREATE TABLE navigation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    locale VARCHAR(5) NOT NULL DEFAULT 'pl',
    label VARCHAR(255) NOT NULL,
    title VARCHAR(255) NULL,
    slug VARCHAR(190) NULL,
    href VARCHAR(500) NULL,
    item_kind VARCHAR(32) NOT NULL DEFAULT 'link',
    section_key VARCHAR(32) NULL,
    content_type VARCHAR(32) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_navigation_parent FOREIGN KEY (parent_id) REFERENCES navigation_items (id) ON DELETE CASCADE,
    INDEX idx_navigation_locale_parent (locale, parent_id, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
