<?php

declare(strict_types=1);

$criteriaSeed = "
    ('Teaching Effectiveness', 'Subject Mastery', 'Demonstrates comprehensive knowledge of the subject matter', 1.00, 1, 5, 1),
    ('Teaching Effectiveness', 'Communication Skills', 'Communicates concepts clearly and effectively', 1.00, 1, 5, 2),
    ('Teaching Effectiveness', 'Teaching Methods', 'Uses appropriate and varied teaching methodologies', 1.00, 1, 5, 3),
    ('Teaching Effectiveness', 'Student Engagement', 'Encourages active participation and critical thinking', 1.00, 1, 5, 4),
    ('Teaching Effectiveness', 'Assessment & Feedback', 'Provides fair assessments and constructive feedback', 1.00, 1, 5, 5),
    ('Professional Development', 'Research & Publications', 'Engages in scholarly activities and publications', 1.00, 1, 5, 6),
    ('Professional Development', 'Continuing Education', 'Participates in professional development activities', 1.00, 1, 5, 7),
    ('Professional Development', 'Community Extension', 'Contributes to community service and extension programs', 1.00, 1, 5, 8),
    ('Administrative Functions', 'Committee Participation', 'Actively participates in department/college committees', 1.00, 1, 5, 9),
    ('Administrative Functions', 'Policy Compliance', 'Adheres to university policies and procedures', 1.00, 1, 5, 10),
    ('Administrative Functions', 'Student Advising', 'Provides effective academic guidance to students', 1.00, 1, 5, 11)
";

$settingsSeed = "
    ('site_name', 'DORSU Faculty Evaluation System', 'string', 'System name displayed in UI', 1),
    ('site_tagline', 'Empowering Excellence in Education', 'string', 'System tagline', 1),
    ('evaluation_scale_min', '1', 'integer', 'Minimum evaluation score', 1),
    ('evaluation_scale_max', '5', 'integer', 'Maximum evaluation score', 1),
    ('max_evaluations_per_period', '3', 'integer', 'Maximum evaluations a faculty can receive per period', 1),
    ('require_self_evaluation', '1', 'boolean', 'Whether self-evaluation is required', 1),
    ('allow_anonymous_feedback', '1', 'boolean', 'Allow anonymous student feedback', 1)
";

return [
    "up" => [
        "sqlite" => "
            CREATE TABLE IF NOT EXISTS evaluation_periods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                academic_year VARCHAR(20) NOT NULL,
                semester VARCHAR(20) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_eval_periods_active ON evaluation_periods(is_active);
            CREATE INDEX IF NOT EXISTS idx_eval_periods_dates ON evaluation_periods(start_date, end_date);

            CREATE TABLE IF NOT EXISTS evaluation_criteria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                min_score INTEGER NOT NULL DEFAULT 1,
                max_score INTEGER NOT NULL DEFAULT 5,
                is_active BOOLEAN NOT NULL DEFAULT 1,
                display_order INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_eval_criteria_active ON evaluation_criteria(is_active);
            CREATE INDEX IF NOT EXISTS idx_eval_criteria_order ON evaluation_criteria(display_order);

            CREATE TABLE IF NOT EXISTS evaluations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                period_id INTEGER NOT NULL,
                evaluatee_id INTEGER NOT NULL,
                evaluator_id INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                total_score DECIMAL(5,2),
                submitted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (period_id) REFERENCES evaluation_periods(id) ON DELETE RESTRICT,
                FOREIGN KEY (evaluatee_id) REFERENCES users(id) ON DELETE RESTRICT,
                FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE RESTRICT
            );
            CREATE INDEX IF NOT EXISTS idx_evaluations_period ON evaluations(period_id);
            CREATE INDEX IF NOT EXISTS idx_evaluations_evaluatee ON evaluations(evaluatee_id);
            CREATE INDEX IF NOT EXISTS idx_evaluations_evaluator ON evaluations(evaluator_id);
            CREATE INDEX IF NOT EXISTS idx_evaluations_status ON evaluations(status);
            CREATE UNIQUE INDEX IF NOT EXISTS uniq_evaluation_per_period ON evaluations(period_id, evaluatee_id, evaluator_id);

            CREATE TABLE IF NOT EXISTS evaluation_responses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                evaluation_id INTEGER NOT NULL,
                criterion_id INTEGER NOT NULL,
                score INTEGER NOT NULL,
                comments TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
                FOREIGN KEY (criterion_id) REFERENCES evaluation_criteria(id) ON DELETE RESTRICT
            );
            CREATE INDEX IF NOT EXISTS idx_eval_responses_eval ON evaluation_responses(evaluation_id);
            CREATE INDEX IF NOT EXISTS idx_eval_responses_criterion ON evaluation_responses(criterion_id);
            CREATE UNIQUE INDEX IF NOT EXISTS uniq_eval_response ON evaluation_responses(evaluation_id, criterion_id);

            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INTEGER NULL,
                old_values TEXT,
                new_values TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );
            CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs(user_id);
            CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_logs(action);
            CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_logs(entity_type, entity_id);
            CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_logs(created_at);

            CREATE TABLE IF NOT EXISTS system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
                description TEXT,
                is_public BOOLEAN NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_settings_public ON system_settings(is_public);

            INSERT OR IGNORE INTO evaluation_criteria (category, name, description, weight, min_score, max_score, display_order) VALUES
            $criteriaSeed;

            INSERT OR IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
            $settingsSeed;
        ",
        "mysql" => "
            CREATE TABLE IF NOT EXISTS evaluation_periods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                academic_year VARCHAR(20) NOT NULL,
                semester VARCHAR(20) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_eval_periods_active (is_active),
                KEY idx_eval_periods_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS evaluation_criteria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                min_score INT NOT NULL DEFAULT 1,
                max_score INT NOT NULL DEFAULT 5,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                display_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_eval_criteria_active (is_active),
                KEY idx_eval_criteria_order (display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS evaluations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                period_id INT NOT NULL,
                evaluatee_id INT NOT NULL,
                evaluator_id INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                total_score DECIMAL(5,2) NULL,
                submitted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_evaluations_period (period_id),
                KEY idx_evaluations_evaluatee (evaluatee_id),
                KEY idx_evaluations_evaluator (evaluator_id),
                KEY idx_evaluations_status (status),
                UNIQUE KEY uniq_evaluation_per_period (period_id, evaluatee_id, evaluator_id),
                CONSTRAINT fk_eval_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(id) ON DELETE RESTRICT,
                CONSTRAINT fk_eval_evaluatee FOREIGN KEY (evaluatee_id) REFERENCES users(id) ON DELETE RESTRICT,
                CONSTRAINT fk_eval_evaluator FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS evaluation_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evaluation_id INT NOT NULL,
                criterion_id INT NOT NULL,
                score INT NOT NULL,
                comments TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_eval_responses_eval (evaluation_id),
                KEY idx_eval_responses_criterion (criterion_id),
                UNIQUE KEY uniq_eval_response (evaluation_id, criterion_id),
                CONSTRAINT fk_resp_eval FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
                CONSTRAINT fk_resp_criterion FOREIGN KEY (criterion_id) REFERENCES evaluation_criteria(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT NULL,
                old_values TEXT,
                new_values TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_audit_user (user_id),
                KEY idx_audit_action (action),
                KEY idx_audit_entity (entity_type, entity_id),
                KEY idx_audit_created (created_at),
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT,
                setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
                description TEXT,
                is_public TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_settings_key (setting_key),
                KEY idx_settings_public (is_public)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            INSERT IGNORE INTO evaluation_criteria (category, name, description, weight, min_score, max_score, display_order) VALUES
            $criteriaSeed;

            INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
            $settingsSeed;
        ",
    ],
    "down" => [
        "sqlite" => "
            DROP TABLE IF EXISTS evaluation_responses;
            DROP TABLE IF EXISTS evaluations;
            DROP TABLE IF EXISTS evaluation_criteria;
            DROP TABLE IF EXISTS evaluation_periods;
            DROP TABLE IF EXISTS audit_logs;
            DROP TABLE IF EXISTS system_settings;
        ",
        "mysql" => "
            DROP TABLE IF EXISTS evaluation_responses;
            DROP TABLE IF EXISTS evaluations;
            DROP TABLE IF EXISTS evaluation_criteria;
            DROP TABLE IF EXISTS evaluation_periods;
            DROP TABLE IF EXISTS audit_logs;
            DROP TABLE IF EXISTS system_settings;
        ",
    ],
];
