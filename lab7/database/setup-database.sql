PRAGMA foreign_keys = ON;

-- Users table supporting Student, Tutor, and Administrator roles
CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('Student', 'Tutor', 'Administrator')),
    status TEXT NOT NULL DEFAULT 'Active' CHECK(status IN ('Active', 'Inactive')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Students table linking to user account via user_id foreign key
CREATE TABLE IF NOT EXISTS students (
    student_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_name TEXT NOT NULL,
    student_email TEXT NOT NULL UNIQUE,
    user_id INTEGER UNIQUE,
    FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Tutoring requests table with foreign key to students
CREATE TABLE IF NOT EXISTS tutoring_requests (
    request_id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course TEXT NOT NULL,
    topic TEXT NOT NULL,
    preferred_date TEXT NOT NULL,
    preferred_time TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Open' CHECK(status IN ('Open', 'Cancelled', 'Assigned', 'Completed')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(student_id) REFERENCES students(student_id)
);

-- Seed Accounts (Pass: AdminPass123!, TutorPass123!, StudentPass123!):
-- 1. Administrator: admin@example.edu
INSERT OR IGNORE INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (1, 'Admin Alice', 'admin@example.edu', '$2y$12$9iwAw31WnMUxg4yrMhlsJOqkVh3PuTw7GPjJwnD6oh.hAUr.dWQjm', 'Administrator', 'Active');

-- 2. Tutor: tutor@example.edu
INSERT OR IGNORE INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (2, 'Taylor Lee', 'tutor@example.edu', '$2y$12$DYmv.H4L2Lzd8rSGAoZVieWF7N4sv7Yz53pLvN5QdZIIRK2wVLn2m', 'Tutor', 'Active');

-- 3. Student Sam: student@example.edu
INSERT OR IGNORE INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (3, 'Student Sam', 'student@example.edu', '$2y$12$i6LJ8r.AWV8DoFz3okuToen2fxDOmcGEIXTNt63IR6owHccYQD.za', 'Student', 'Active');

-- 4. Maria Lopez: maria.lopez@example.com
INSERT OR IGNORE INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (4, 'Maria Lopez', 'maria.lopez@example.com', '$2y$12$i6LJ8r.AWV8DoFz3okuToen2fxDOmcGEIXTNt63IR6owHccYQD.za', 'Student', 'Active');

-- 5. Inactive Student: inactive@example.edu
INSERT OR IGNORE INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (5, 'Inactive Ian', 'inactive@example.edu', '$2y$12$i6LJ8r.AWV8DoFz3okuToen2fxDOmcGEIXTNt63IR6owHccYQD.za', 'Student', 'Inactive');

-- Seed Students:
INSERT OR IGNORE INTO students (student_id, student_name, student_email, user_id)
VALUES (1, 'Maria Lopez', 'maria.lopez@example.com', 4);

INSERT OR IGNORE INTO students (student_id, student_name, student_email, user_id)
VALUES (2, 'Student Sam', 'student@example.edu', 3);

-- Seed Initial Tutoring Request for Maria Lopez:
INSERT OR IGNORE INTO tutoring_requests (request_id, student_id, course, topic, preferred_date, preferred_time, status)
VALUES (1, 1, 'IS 6465', '{"reason":"PHP arrays and forms","topics":["Practice problems"]}', '2026-11-25', 'Morning', 'Open');
