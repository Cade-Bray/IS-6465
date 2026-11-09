# Lab 5 Notes

### Part 1: Create the database tables
1. An image of both tables and their schemas 
![Created Tables and Schemas](Created%20tables%20and%20schemas.png)

2. The Primary key for the Students table is 'student_id' whereas the primary key for the Tutoring Requests table is 'request_id'.

3. The foreign key in the Tutoring Requests table is the 'student_id' column. This represents a relationship between the Students table and the Tutoring Requests table by referencing a primary key in the Students table recorded as the foreign key in the Tutoring requests.

4. student_email is marked unique because it's how we're ensuring that only one account is created per user. If your email is already registered you wouldn't be abled to create another account.

### Part 2: Insert Students and tutoring requests

An image of the insert statements and the terminal output
![Insert Statements and Output](Insert%20statements%20and%20output.png)

The student Jordan in the screenshot has one entry in the students table because he only has one account. That one account is tied to multiple requests in the tutoring requests table. They're associated over the shared student_id columns also refered to here as the foreign key.

### Part 3: Read the stored data

Commands ran
```SQL
SELECT t.request_id, s.student_name, s.student_email, t.course, t.topic, t.preferred_date, t.preferred_time, t.status
FROM tutoring_requests AS t
JOIN students AS s
ON t.student_id = s.student_id
ORDER BY t.preferred_date DESC;
```

The corresponding output to this command
```text
╭────────────┬──────────────┬─────────────────────────┬─────────┬────────────────────────────────┬────────────────┬────────────────┬───────────╮
│ request_id │ student_name │      student_email      │ course  │             topic              │ preferred_date │ preferred_time │  status   │
╞════════════╪══════════════╪═════════════════════════╪═════════╪════════════════════════════════╪════════════════╪════════════════╪═══════════╡
│          4 │ Avery Patel  │ avery.patel@example.edu │ IS 6465 │ SQLite CRUD                    │ 2026-11-12     │ 09:00          │ Open      │
│          3 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ SQL SELECT                     │ 2026-11-10     │ 11:00          │ Open      │
│          2 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ Forms                          │ 2026-11-09     │ 15:00          │ Open      │
│          1 │ Maria Lopez  │ maria.lopez@example.com │ IS 6465 │ PHP arrays and forms - updated │ 2026-10-12     │ 15:00          │ Cancelled │
╰────────────┴──────────────┴─────────────────────────┴─────────┴────────────────────────────────┴────────────────┴────────────────┴───────────╯
```

1. The join condition connects upon the student_id in both tables. Even though it's not displayed it's being utilized.

2. The difference between a JOIN and a WHERE is that a JOIN combines tables to create new views where as the WHERE statement filters the view.

3. By retrieving by request_id instead of topic we're ensuring we're getting exactly the request we're looking for whereas if we grab by the topic we could be grabbing many requests with the same topic value.

### Part 4: Update one request

1. The two UPDATE statements used
Update Statement One - Updating Jordan's topic
```SQL
UPDATE tutoring_requests
SET topic = 'Forms and Updated Info'
WHERE request_id = 2;
```

Update Statement Two - Updating Avery's Status
```SQL
UPDATE tutoring_requests
SET status = 'Cancelled'
WHERE request_id = 4;
```

2. Before SELECT output

```text
Request List
╭────────────┬──────────────┬─────────────────────────┬─────────┬────────────────────────────────┬────────────────┬────────────────┬───────────╮
│ request_id │ student_name │      student_email      │ course  │             topic              │ preferred_date │ preferred_time │  status   │
╞════════════╪══════════════╪═════════════════════════╪═════════╪════════════════════════════════╪════════════════╪════════════════╪═══════════╡
│          4 │ Avery Patel  │ avery.patel@example.edu │ IS 6465 │ SQLite CRUD                    │ 2026-11-12     │ 09:00          │ Open      │
│          3 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ SQL SELECT                     │ 2026-11-10     │ 11:00          │ Open      │
│          2 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ Forms and Updated Info         │ 2026-11-09     │ 15:00          │ Open      │
│          1 │ Maria Lopez  │ maria.lopez@example.com │ IS 6465 │ PHP arrays and forms - updated │ 2026-10-12     │ 15:00          │ Cancelled │
╰────────────┴──────────────┴─────────────────────────┴─────────┴────────────────────────────────┴────────────────┴────────────────┴───────────╯
```

3. Avery's Cancelled Status

```text
Request List
╭────────────┬──────────────┬─────────────────────────┬─────────┬────────────────────────────────┬────────────────┬────────────────┬───────────╮
│ request_id │ student_name │      student_email      │ course  │             topic              │ preferred_date │ preferred_time │  status   │
╞════════════╪══════════════╪═════════════════════════╪═════════╪════════════════════════════════╪════════════════╪════════════════╪═══════════╡
│          4 │ Avery Patel  │ avery.patel@example.edu │ IS 6465 │ SQLite CRUD                    │ 2026-11-12     │ 09:00          │ Cancelled │
│          3 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ SQL SELECT                     │ 2026-11-10     │ 11:00          │ Open      │
│          2 │ Jordan Lee   │ jordan.lee@example.edu  │ IS 6465 │ Forms and Updated Info         │ 2026-11-09     │ 15:00          │ Open      │
│          1 │ Maria Lopez  │ maria.lopez@example.com │ IS 6465 │ PHP arrays and forms - updated │ 2026-10-12     │ 15:00          │ Cancelled │
╰────────────┴──────────────┴─────────────────────────┴─────────┴────────────────────────────────┴────────────────┴────────────────┴───────────╯
```

4. The WHERE clause is essential to the UPDATE statement because without it the entire table would have been updated. The WHERE clause ensures on what're looking for gets updated.

5. The Peer Tutoring Hub uses a Cancelled status instead of deleting the request for data retention. The administrators may want to pull statistics on how many users are cancelling their appointments and if the system deletes them they'll never know.

### Part 5: Delete on test request

```text
Request List
╭────────────┬──────────────┬─────────┬────────────────────────────────┬───────────╮
│ request_id │ student_name │ course  │             topic              │  status   │
╞════════════╪══════════════╪═════════╪════════════════════════════════╪═══════════╡
│          4 │ Avery Patel  │ IS 6465 │ SQLite CRUD                    │ Cancelled │
│          3 │ Jordan Lee   │ IS 6465 │ SQL SELECT                     │ Open      │
│          2 │ Jordan Lee   │ IS 6465 │ Forms and Updated Info         │ Open      │
│          1 │ Maria Lopez  │ IS 6465 │ PHP arrays and forms - updated │ Cancelled │
╰────────────┴──────────────┴─────────┴────────────────────────────────┴───────────╯
```

```text
Request List
╭────────────┬──────────────┬─────────┬────────────────────────┬───────────╮
│ request_id │ student_name │ course  │         topic          │  status   │
╞════════════╪══════════════╪═════════╪════════════════════════╪═══════════╡
│          4 │ Avery Patel  │ IS 6465 │ SQLite CRUD            │ Cancelled │
│          3 │ Jordan Lee   │ IS 6465 │ SQL SELECT             │ Open      │
│          2 │ Jordan Lee   │ IS 6465 │ Forms and Updated Info │ Open      │
╰────────────┴──────────────┴─────────┴────────────────────────┴───────────╯
```

The WHERE clause is crucial to get right in a DELETE statement otherwise you'll delete unintentional data and have no way to retrieve it.

### Part 6: Reflect on the CRUD Workflow