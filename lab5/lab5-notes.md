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
