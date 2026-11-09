-- Part 1: Create the database tables
CREATE TABLE IF NOT EXISTS students (
  student_id INTEGER PRIMARY KEY,
  student_name TEXT NOT NULL,
  student_email TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS tutoring_requests (
  request_id INTEGER PRIMARY KEY,
  student_id INTEGER NOT NULL,
  course TEXT NOT NULL,
  topic TEXT NOT NULL,
  preferred_date TEXT NOT NULL,
  preferred_time TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Open',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- Part 2: Insert students and tutoring requests
INSERT OR IGNORE INTO students (student_name, student_email)
VALUES ('Maria Lopez', 'maria.lopez@example.com');

INSERT OR IGNORE INTO students (student_name, student_email)
VALUES ('Jordan Lee', 'jordan.lee@example.edu');

INSERT OR IGNORE INTO students (student_name, student_email)
VALUES ('Avery Patel', 'avery.patel@example.edu');

INSERT OR IGNORE INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
VALUES (1, 'IS 6465', 'PHP arrays and forms', '2026-10-12', '15:00');

INSERT OR IGNORE INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
VALUES (2, 'IS 6465', 'Forms', '2026-11-09', '15:00');

INSERT OR IGNORE INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
VALUES (2, 'IS 6465', 'SQL SELECT', '2026-11-10', '11:00');

INSERT OR IGNORE INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
VALUES (3, 'IS 6465', 'SQLite CRUD', '2026-11-12', '09:00');

-- Part 3: Read the stored data
SELECT * FROM students;

SELECT * FROM tutoring_requests;

SELECT * FROM tutoring_requests
WHERE status = 'Open';

SELECT * FROM tutoring_requests
WHERE request_id = 2;

SELECT t.request_id, s.student_name, s.student_email, t.course, t.topic, t.preferred_date, t.preferred_time, t.status
FROM tutoring_requests AS t
JOIN students AS s
ON t.student_id = s.student_id
ORDER BY t.preferred_date DESC;

-- Part 4: Update one request
UPDATE tutoring_requests
SET topic = 'Forms and Updated Info'
WHERE request_id = 2;

UPDATE tutoring_requests
SET status = 'Cancelled'
WHERE request_id = 4;

-- Part 5: Delete one test request
DELETE FROM tutoring_requests
WHERE request_id = 1;

SELECT t.request_id, s.student_name, t.course, t.topic, t.status
FROM tutoring_requests AS t
JOIN students AS s
ON t.student_id = s.student_id
ORDER BY t.preferred_date DESC;
