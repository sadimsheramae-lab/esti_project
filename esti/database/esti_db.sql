-- ESTI College Grading Management System Database
CREATE DATABASE IF NOT EXISTS esti_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE esti_db;

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_code VARCHAR(20) NOT NULL UNIQUE,
    dept_name VARCHAR(150) NOT NULL,
    chairperson VARCHAR(100),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Faculty Table
CREATE TABLE IF NOT EXISTS faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    dept_code VARCHAR(20),
    position VARCHAR(80),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_code) REFERENCES departments(dept_code) ON UPDATE CASCADE
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_desc VARCHAR(150) NOT NULL,
    units INT DEFAULT 3,
    type ENUM('Major','Minor','GE','PE','Elective') DEFAULT 'Major',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(20) NOT NULL UNIQUE,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    course VARCHAR(20),
    year_level ENUM('1st Year','2nd Year','3rd Year','4th Year') DEFAULT '1st Year',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course) REFERENCES departments(dept_code) ON UPDATE CASCADE
);

-- Classes and Sections Table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(20) NOT NULL,
    section VARCHAR(10) NOT NULL,
    course VARCHAR(20),
    year_level ENUM('1st Year','2nd Year','3rd Year','4th Year'),
    adviser_id VARCHAR(20),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course) REFERENCES departments(dept_code) ON UPDATE CASCADE,
    FOREIGN KEY (adviser_id) REFERENCES faculty(faculty_id) ON UPDATE CASCADE
);

-- Grades Table
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    subject_code VARCHAR(20),
    class_id INT,
    prelim DECIMAL(5,2),
    midterm DECIMAL(5,2),
    finals DECIMAL(5,2),
    final_grade DECIMAL(5,2),
    remarks VARCHAR(20),
    school_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id_number) ON UPDATE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON UPDATE CASCADE
);

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('Admin','Faculty') DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO admin_users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Administrator', 'Admin');

-- Insert Sample Departments
INSERT INTO departments (dept_code, dept_name, chairperson, status) VALUES
('BSIT', 'Bachelor of Science in Information Technology', 'Prof. Alvin Santos', 'Active'),
('BSA', 'Bachelor of Science in Accountancy', 'Prof. Rochelle Tan', 'Active'),
('BEED', 'Bachelor of Elementary Education', 'Prof. Liza Hernandez', 'Active'),
('BSHM', 'Bachelor of Science in Hospitality Management', 'Prof. Mark Dionisio', 'Active'),
('BSCRIM', 'Bachelor of Science in Criminology', 'Prof. Michael Ramos', 'Active'),
('BSBA', 'Bachelor of Science in Business Administration', 'Prof. Carlo Reyes', 'Active');

-- Insert Sample Faculty
INSERT INTO faculty (faculty_id, first_name, last_name, email, dept_code, position, status) VALUES
('F0001', 'John Michael', 'Cruz', 'jmcruz@esti.edu.ph', 'BSIT', 'Instructor', 'Active'),
('F0002', 'Mary Ann', 'Dela Torre', 'madelatorre@esti.edu.ph', 'BSIT', 'Instructor', 'Active'),
('F0003', 'Alvin', 'Santos', 'asantos@esti.edu.ph', 'BSIT', 'Assistant Professor', 'Active'),
('F0004', 'Rochelle', 'Tan', 'rtan@esti.edu.ph', 'BSA', 'Assistant Professor', 'Active'),
('F0005', 'Liza', 'Hernandez', 'lhernandez@esti.edu.ph', 'BEED', 'Assistant Professor', 'Active'),
('F0006', 'Carlo', 'Reyes', 'creyes@esti.edu.ph', 'BSA', 'Instructor', 'Active'),
('F0007', 'Grace', 'Padilla', 'gpadilla@esti.edu.ph', 'BEED', 'Instructor', 'Active');

-- Insert Sample Subjects
INSERT INTO subjects (subject_code, subject_desc, units, type, status) VALUES
('IT201', 'Data Structures and Algorithms', 3, 'Major', 'Active'),
('IT202', 'Database Management Systems', 3, 'Major', 'Active'),
('IT203', 'Web Systems and Technologies', 3, 'Major', 'Active'),
('IT204', 'Information Assurance and Security', 3, 'Major', 'Active'),
('GE202', 'Purposive Communication', 3, 'GE', 'Active'),
('PE204', 'Physical Fitness and Health', 2, 'PE', 'Active'),
('MATH201', 'Discrete Mathematics', 3, 'Major', 'Active'),
('IT205', 'Object-Oriented Programming', 3, 'Major', 'Active');

-- Insert Sample Students
INSERT INTO students (id_number, last_name, first_name, course, year_level, status) VALUES
('20241001', 'Dela Cruz', 'Juan', 'BSIT', '2nd Year', 'Active'),
('20241002', 'Reyes', 'Maria', 'BSA', '1st Year', 'Active'),
('20241003', 'Santos', 'Ana', 'BEED', '3rd Year', 'Active'),
('20241004', 'Garcia', 'Paolo', 'BSIT', '2nd Year', 'Inactive'),
('20241005', 'Lopez', 'Kyle', 'BSA', '1st Year', 'Active'),
('20241006', 'Cruz', 'Danielle', 'BSIT', '2nd Year', 'Active'),
('20241007', 'Villanueva', 'James', 'BSA', '3rd Year', 'Active'),
('20241008', 'Torres', 'Andrea', 'BEED', '1st Year', 'Active');

-- Insert Sample Classes
INSERT INTO classes (class_name, section, course, year_level, adviser_id, status) VALUES
('BSIT', '2A', 'BSIT', '2nd Year', 'F0001', 'Active'),
('BSIT', '2B', 'BSIT', '2nd Year', 'F0002', 'Active'),
('BSA', '1A', 'BSA', '1st Year', 'F0006', 'Active'),
('BEED', '3A', 'BEED', '3rd Year', 'F0005', 'Active'),
('BSIT', '3A', 'BSIT', '3rd Year', 'F0003', 'Active'),
('BSA', '2A', 'BSA', '2nd Year', 'F0004', 'Active'),
('BEED', '1A', 'BEED', '1st Year', 'F0007', 'Active');
