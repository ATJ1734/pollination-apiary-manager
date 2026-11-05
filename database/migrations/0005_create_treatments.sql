CREATE TABLE treatments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hive_id INT UNSIGNED NOT NULL,
    product VARCHAR(80) NOT NULL,
    applied_on DATE NOT NULL,
    dose VARCHAR(80) NULL,
    remove_by DATE NULL,
    removed_on DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_treatments_hive (hive_id),
    KEY idx_treatments_applied (applied_on),
    KEY idx_treatments_remove_by (remove_by),
    CONSTRAINT fk_treatments_hive FOREIGN KEY (hive_id) REFERENCES hives (id),
    CONSTRAINT chk_treatments_remove_by CHECK (remove_by IS NULL OR remove_by >= applied_on),
    CONSTRAINT chk_treatments_removed_on CHECK (removed_on IS NULL OR removed_on >= applied_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
