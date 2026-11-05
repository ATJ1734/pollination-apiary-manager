CREATE TABLE placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    hive_id INT UNSIGNED NOT NULL,
    placed_on DATE NOT NULL,
    removed_on DATE NULL,
    frames_at_placement TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_placements_contract (contract_id),
    KEY idx_placements_hive (hive_id),
    KEY idx_placements_placed_on (placed_on),
    CONSTRAINT fk_placements_contract FOREIGN KEY (contract_id) REFERENCES contracts (id),
    CONSTRAINT fk_placements_hive FOREIGN KEY (hive_id) REFERENCES hives (id),
    CONSTRAINT chk_placements_dates CHECK (removed_on IS NULL OR removed_on >= placed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
