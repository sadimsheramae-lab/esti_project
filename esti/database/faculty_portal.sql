-- ============================================================
--  ESTI CGMS — Faculty Portal SQL additions
--  Run this AFTER student_portal.sql
-- ============================================================
USE esti_db;

-- Add extra columns to faculty table
ALTER TABLE faculty
    ADD COLUMN IF NOT EXISTS password    VARCHAR(255) DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL AFTER password,
    ADD COLUMN IF NOT EXISTS contact_no  VARCHAR(20)  DEFAULT NULL AFTER profile_pic,
    ADD COLUMN IF NOT EXISTS address     TEXT         DEFAULT NULL AFTER contact_no,
    ADD COLUMN IF NOT EXISTS birthday    DATE         DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS gender      ENUM('Male','Female','Other') DEFAULT NULL AFTER birthday,
    ADD COLUMN IF NOT EXISTS specialization VARCHAR(120) DEFAULT NULL AFTER gender;

-- Default passwords = 'password' for all faculty
UPDATE faculty SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password IS NULL;

-- Faculty assignments: which faculty teaches which subject in which class
CREATE TABLE IF NOT EXISTS faculty_assignments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id  VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    class_id    INT NOT NULL,
    school_year VARCHAR(20) DEFAULT '2023-2024',
    semester    ENUM('1st Semester','2nd Semester','Summer') DEFAULT '1st Semester',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_assign (faculty_id, subject_code, class_id, school_year, semester),
    FOREIGN KEY (faculty_id)   REFERENCES faculty(faculty_id)   ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON UPDATE CASCADE,
    FOREIGN KEY (class_id)     REFERENCES classes(id)
);

-- Sample assignments (F0001 teaches IT201, IT203 in class 1 BSIT 2A)
INSERT IGNORE INTO faculty_assignments (faculty_id, subject_code, class_id, school_year, semester) VALUES
('F0001','IT201',1,'2023-2024','1st Semester'),
('F0001','IT203',1,'2023-2024','1st Semester'),
('F0002','IT202',1,'2023-2024','1st Semester'),
('F0003','MATH201',1,'2023-2024','1st Semester'),
('F0005','GE202',1,'2023-2024','1st Semester'),
('F0007','PE204',1,'2023-2024','1st Semester');

-- Faculty notifications
CREATE TABLE IF NOT EXISTS faculty_notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id VARCHAR(20) NOT NULL,
    title      VARCHAR(120) NOT NULL,
    message    TEXT,
    is_read    TINYINT(1) DEFAULT 0,
    type       ENUM('grade','announcement','general','system') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON UPDATE CASCADE
);

-- Sample faculty notifications
INSERT IGNORE INTO faculty_notifications (faculty_id, title, message, type) VALUES
('F0001','Grade Submission Deadline','Please submit all grades for 1st Semester by December 15, 2024.','announcement'),
('F0001','New Student Enrolled','A new student has been enrolled in your BSIT 2A - IT201 class.','general'),
('F0001','System Maintenance','The system will undergo maintenance on Sunday, Dec 10, 12:00 AM - 4:00 AM.','system');

-- Update schedules to link faculty properly (already has faculty_id column)
-- Make sure F0001 is linked to IT201 and IT203 schedules
UPDATE schedules SET faculty_id = 'F0001' WHERE subject_code IN ('IT201','IT203') AND class_id = 1;
UPDATE schedules SET faculty_id = 'F0002' WHERE subject_code = 'IT202' AND class_id = 1;
UPDATE schedules SET faculty_id = 'F0003' WHERE subject_code = 'MATH201' AND class_id = 1;
UPDATE schedules SET faculty_id = 'F0005' WHERE subject_code = 'GE202' AND class_id = 1;
UPDATE schedules SET faculty_id = 'F0007' WHERE subject_code = 'PE204' AND class_id = 1;

-- Update grades to link faculty
UPDATE grades SET faculty_id = 'F0001' WHERE subject_code IN ('IT201','IT203');
UPDATE grades SET faculty_id = 'F0002' WHERE subject_code = 'IT202';
UPDATE grades SET faculty_id = 'F0003' WHERE subject_code = 'MATH201';
UPDATE grades SET faculty_id = 'F0005' WHERE subject_code = 'GE202';
UPDATE grades SET faculty_id = 'F0007' WHERE subject_code = 'PE204';
