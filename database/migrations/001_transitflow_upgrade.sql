-- Run once against an existing tms_db database before deploying TransitFlow.
ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Active';
ALTER TABLE trips ADD COLUMN fare_at_time DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE complaints ADD COLUMN resolution_notes TEXT NULL;
ALTER TABLE complaints ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
CREATE TABLE IF NOT EXISTS audit_logs (
 audit_id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id INT NULL,action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(60) NULL,entity_id BIGINT NULL,description VARCHAR(500) NULL,ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_audit_created(created_at),INDEX idx_audit_user(user_id),
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS system_settings (
 setting_key VARCHAR(100) PRIMARY KEY,setting_value TEXT NULL,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS complaint_updates (
 update_id BIGINT AUTO_INCREMENT PRIMARY KEY,complaint_id INT NOT NULL,user_id INT NULL,
 old_status VARCHAR(30) NULL,new_status VARCHAR(30) NOT NULL,note TEXT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_complaint_updates(complaint_id),
 FOREIGN KEY(complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
