# StudentHub Portal - Entity Relationship (ER) Model
**Practical 8: Normalized Database Design (3NF)**

## 1. Relational Entities and Cardinality

The StudentHub schema is normalized to Third Normal Form (3NF) to eliminate transitive dependencies and data anomalies.

- **`students`**: Stores registered student credentials, contact info, and course details.
- **`events`**: Stores upcoming workshops, seminars, and campus tech events.
- **`registrations`**: Associative bridge entity resolving the **Many-to-Many ($M:N$)** relationship between `students` and `events`.
- **`feedback`**: Stores student ratings and system reviews (1-to-Many from `students`).
- **`audit_logs`**: Tracks authentication, registration security events, IP addresses, and timestamps.

## 2. Mermaid Entity-Relationship Diagram

```mermaid
erDiagram
    STUDENTS ||--o{ REGISTRATIONS : "places (1:N)"
    EVENTS ||--o{ REGISTRATIONS : "receives (1:N)"
    STUDENTS ||--o{ FEEDBACK : "submits (1:N)"

    STUDENTS {
        int id PK "Auto Increment"
        string roll_no UK "Unique Roll Number"
        string full_name "Student Full Name"
        string email UK "Unique Email Address"
        string phone "10-digit Phone Number"
        string password_hash "Bcrypt Hash (cost=12)"
        string course "Enrolled Course"
        string year "Academic Year"
        enum gender "Male, Female, Other"
        enum status "Active, Suspended, Pending"
        timestamp created_at "Registration Time"
        timestamp updated_at "Auto-updated Time"
    }

    EVENTS {
        int id PK "Auto Increment"
        string title "Event Title"
        string category "Workshops, Seminars, etc."
        date event_date "Scheduled Date"
        string event_time "Scheduled Time"
        string location "Venue or Online URL"
        string speaker "Keynote Speaker/Presenter"
        text description "Detailed Overview"
        int max_seats "Seat Capacity"
        string badge "Status Badge"
        timestamp created_at "Created Timestamp"
    }

    REGISTRATIONS {
        int id PK "Auto Increment"
        int student_id FK "References STUDENTS(id)"
        int event_id FK "References EVENTS(id)"
        timestamp registration_date "Date and Time"
        enum status "Confirmed, Waitlisted, Cancelled"
        string notes "Special Remarks"
    }

    FEEDBACK {
        int id PK "Auto Increment"
        int student_id FK "References STUDENTS(id) (Nullable)"
        string full_name "Submitter Name"
        string email "Submitter Email"
        string category "UI, Academics, Events, Bugs"
        int rating "1 to 5 Stars"
        text message "Feedback Content"
        timestamp created_at "Submission Time"
    }

    AUDIT_LOGS {
        int id PK "Auto Increment"
        string event_type "Registration, Login, Password Reset"
        string user_email "Target Email"
        string ip_address "Client IP Address"
        string user_agent "Browser Fingerprint"
        enum status "Success, Failed"
        text details "Error or Execution Details"
        timestamp logged_at "Event Timestamp"
    }
```

## 3. Relational Schema Attributes & Constraints

| Table | Primary Key | Foreign Keys | Constraints & Indexes |
|---|---|---|---|
| `students` | `id` | None | `roll_no` (UNIQUE), `email` (UNIQUE), INDEX(`course`) |
| `events` | `id` | None | INDEX(`event_date`), INDEX(`category`) |
| `registrations` | `id` | `student_id` $\rightarrow$ `students(id)`<br>`event_id` $\rightarrow$ `events(id)` | UNIQUE(`student_id`, `event_id`), `ON DELETE CASCADE` |
| `feedback` | `id` | `student_id` $\rightarrow$ `students(id)` | `rating BETWEEN 1 AND 5`, `ON DELETE SET NULL` |
| `audit_logs` | `id` | None | INDEX(`user_email`), INDEX(`logged_at`) |
