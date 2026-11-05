CREATE TABLE hives (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hive_code VARCHAR(20) NOT NULL,
    apiary_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'queenless', 'dead', 'sold', 'combined') NOT NULL DEFAULT 'active',
    hive_type VARCHAR(40) NULL,
    queen_installed_on DATE NULL,
    queen_source VARCHAR(80) NULL,
    queen_mark_color VARCHAR(30) NULL,
    established_on DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hives_code (hive_code),
    KEY idx_hives_apiary (apiary_id),
    KEY idx_hives_status (status),
    CONSTRAINT fk_hives_apiary FOREIGN KEY (apiary_id) REFERENCES apiaries (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
