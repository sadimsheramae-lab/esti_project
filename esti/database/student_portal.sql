-- ============================================================
--  ESTI CGMS — Student Portal SQL additions
--  Run this AFTER the main esti_db.sql
-- ============================================================
USE esti_db;

-- Add password column to students (default = id_number hashed)
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL AFTER password,
    ADD COLUMN IF NOT EXISTS contact_no VARCHAR(20) DEFAULT NULL AFTER profile_pic,
    ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL AFTER contact_no,
    ADD COLUMN IF NOT EXISTS birthday DATE DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other') DEFAULT NULL AFTER birthday;

-- Set default passwords = id_number (bcrypt of '20241001' etc.)
-- For demo we just set a known hash for 'password' for all students
UPDATE students SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password IS NULL;

-- Enrollments table (student ↔ subject ↔ class)
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    class_id INT NOT NULL,
    school_year VARCHAR(20) DEFAULT '2023-2024',
    semester ENUM('1st Semester','2nd Semester','Summer') DEFAULT '1st Semester',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id_number) ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON UPDATE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Grades table (update existing to add semester)
ALTER TABLE grades
    ADD COLUMN IF NOT EXISTS semester ENUM('1st Semester','2nd Semester','Summer') DEFAULT '1st Semester' AFTER school_year,
    ADD COLUMN IF NOT EXISTS faculty_id VARCHAR(20) DEFAULT NULL AFTER semester,
    ADD COLUMN IF NOT EXISTS enrollment_id INT DEFAULT NULL AFTER faculty_id;

-- Schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    faculty_id VARCHAR(20),
    day_of_week SET('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    room VARCHAR(30),
    school_year VARCHAR(20) DEFAULT '2023-2024',
    semester ENUM('1st Semester','2nd Semester','Summer') DEFAULT '1st Semester',
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON UPDATE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON UPDATE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    type ENUM('grade','announcement','general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id_number) ON UPDATE CASCADE
);

-- ── Sample enrollments (student 20241001 in BSIT 2A = class id 1)
INSERT IGNORE INTO enrollments (student_id, subject_code, class_id, school_year, semester) VALUES
('20241001','IT201',1,'2023-2024','1st Semester'),
('20241001','IT202',1,'2023-2024','1st Semester'),
('20241001','IT203',1,'2023-2024','1st Semester'),
('20241001','GE202',1,'2023-2024','1st Semester'),
('20241001','PE204',1,'2023-2024','1st Semester'),
('20241001','MATH201',1,'2023-2024','1st Semester');

-- ── Sample grades
INSERT IGNORE INTO grades (student_id, subject_code, class_id, prelim, midterm, finals, final_grade, remarks, school_year, semester) VALUES
('20241001','IT201',1, 88,85,90, 88.0,'Passed','2023-2024','1st Semester'),
('20241001','IT202',1, 92,88,95, 91.7,'Passed','2023-2024','1st Semester'),
('20241001','IT203',1, 78,80,82, 80.0,'Passed','2023-2024','1st Semester'),
('20241001','GE202',1, 85,87,89, 87.0,'Passed','2023-2024','1st Semester'),
('20241001','PE204',1, 90,92,94, 92.0,'Passed','2023-2024','1st Semester'),
('20241001','MATH201',1,75,78,80, 77.7,'Passed','2023-2024','1st Semester');

-- ── Sample schedules for class 1 (BSIT 2A)
INSERT IGNORE INTO schedules (class_id, subject_code, faculty_id, day_of_week, time_start, time_end, room, school_year, semester) VALUES
(1,'IT201','F0001','Monday,Wednesday','07:30:00','09:00:00','Room 201','2023-2024','1st Semester'),
(1,'IT202','F0002','Tuesday,Thursday','09:00:00','10:30:00','Room 202','2023-2024','1st Semester'),
(1,'IT203','F0001','Monday,Wednesday','10:30:00','12:00:00','Lab 1','2023-2024','1st Semester'),
(1,'GE202','F0005','Friday','07:30:00','10:30:00','Room 301','2023-2024','1st Semester'),
(1,'PE204','F0007','Tuesday','13:00:00','15:00:00','Gym','2023-2024','1st Semester'),
(1,'MATH201','F0003','Thursday,Saturday','07:30:00','09:00:00','Room 105','2023-2024','1st Semester');

-- ── Sample notifications
INSERT IGNORE INTO notifications (student_id, title, message, type) VALUES
('20241001','Grades Posted','Your grades for IT201 (Data Structures) have been posted.','grade'),
('20241001','Enrollment Open','Enrollment for 2nd Semester is now open. Please proceed to the registrar.','announcement'),
('20241001','Holiday Notice','Classes are suspended on November 2 in observance of All Souls Day.','general');
