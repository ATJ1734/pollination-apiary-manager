CREATE TABLE contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grower_id INT UNSIGNED NOT NULL,
    crop VARCHAR(60) NOT NULL,
    season_year SMALLINT UNSIGNED NOT NULL,
    hives_required INT UNSIGNED NOT NULL,
    min_frames_of_bees TINYINT UNSIGNED NOT NULL,
    rate_per_hive DECIMAL(8,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'signed', 'active', 'completed', 'invoiced') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_contracts_grower (grower_id),
    KEY idx_contracts_status (status),
    KEY idx_contracts_start (start_date),
    CONSTRAINT fk_contracts_grower FOREIGN KEY (grower_id) REFERENCES growers (id),
    CONSTRAINT chk_contracts_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
