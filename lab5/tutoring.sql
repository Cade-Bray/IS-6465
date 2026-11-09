CREATE TABLE if not exists students (
  student_id INTEGER PRIMARY KEY,
  student_name TEXT NOT NULL,
  student_email TEXT NOT NULL UNIQUE
);

CREATE TABLE if not exists tutoring_requests (
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

.print "\nRequest List"
SELECT t.request_id, s.student_name, t.course, t.topic, t.status
FROM tutoring_requests AS t
JOIN students AS s
ON t.student_id = s.student_id
ORDER BY t.preferred_date DESC;

DELETE FROM tutoring_requests
WHERE request_id = 1;

.print "\nRequest List"
SELECT t.request_id, s.student_name, t.course, t.topic, t.status
FROM tutoring_requests AS t
JOIN students AS s
ON t.student_id = s.student_id
ORDER BY t.preferred_date DESC;