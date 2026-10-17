-- ========================================================
-- StudentHub Portal Database Schema
-- Practicals 8, 9 & 10: 3NF Schema, RBAC, Sessions & Remember-Me
-- ========================================================

CREATE DATABASE IF NOT EXISTS `studenthub` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

-- --------------------------------------------------------
-- Table: students
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `roll_no` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(15) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `year` VARCHAR(20) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `status` ENUM('Active', 'Suspended', 'Pending') DEFAULT 'Active',
    `last_login` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_email` (`email`),
    INDEX `idx_students_roll` (`roll_no`),
    INDEX `idx_students_course` (`course`),
    INDEX `idx_students_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: remember_tokens (Practical 10 Advanced Extension)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tokens_student` FOREIGN KEY (`user_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_tokens_hash` (`token_hash`),
    INDEX `idx_tokens_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: events
-- --------------------------------------------------------
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` VARCHAR(50) NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `speaker` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `max_seats` INT NOT NULL DEFAULT 50,
    `badge` VARCHAR(30) DEFAULT 'Upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_events_date` (`event_date`),
    INDEX `idx_events_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: registrations (Many-to-Many Bridge)
-- --------------------------------------------------------
CREATE TABLE `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('Confirmed', 'Waitlisted', 'Cancelled') DEFAULT 'Confirmed',
    `notes` VARCHAR(255) NULL,
    CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) 
        REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `uk_student_event` (`student_id`, `event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: feedback
-- --------------------------------------------------------
CREATE TABLE `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_feedback_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: audit_logs (Practical 9 & 10 Security Extension)
-- --------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL,
    `user_email` VARCHAR(120) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NOT NULL,
    `status` ENUM('Success', 'Failed') NOT NULL,
    `details` TEXT NULL,
    `logged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- SEED DATA
-- Passwords below are verified bcrypt hashes for "Password@123"
-- ========================================================

INSERT INTO `students` (`roll_no`, `full_name`, `email`, `phone`, `password_hash`, `course`, `year`, `gender`, `role`, `status`) VALUES
('24ADMIN01', 'Prof. Vikram Mehta (Administrator)', 'admin@studentshub.edu', '9899001122', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'Faculty of Technology', 'Administration', 'Male', 'admin', 'Active'),
('24IT001', 'Aarav Sharma', 'aarav.sharma@studentshub.edu', '9876543210', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech IT', '3rd Year', 'Male', 'student', 'Active'),
('24IT015', 'Diya Patel', 'diya.patel@studentshub.edu', '9823456781', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech CSE', '3rd Year', 'Female', 'student', 'Active'),
('24IT028', 'Rohan Nair', 'rohan.nair@studentshub.edu', '9734567892', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech AI&DS', '2nd Year', 'Male', 'student', 'Active'),
('24IT034', 'Ananya Desai', 'ananya.desai@studentshub.edu', '9845612345', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech IT', '4th Year', 'Female', 'student', 'Active'),
('24IT042', 'Kabir Verma', 'kabir.verma@studentshub.edu', '9756123456', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'BCA', '3rd Year', 'Male', 'student', 'Active'),
('25IT053', 'Krishna Mangukiya', 'krishna.mangukiya@studentshub.edu', '9812345678', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech IT', '3rd Year', 'Male', 'student', 'Active'),
('24IT061', 'Pooja Trivedi', 'pooja.trivedi@studentshub.edu', '9898712345', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech CSE', '2nd Year', 'Female', 'student', 'Active'),
('24IT073', 'Yash Joshi', 'yash.joshi@studentshub.edu', '9723412389', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'MCA', '2nd Year', 'Male', 'student', 'Active'),
('24IT085', 'Tanvi Dave', 'tanvi.dave@studentshub.edu', '9812789456', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech AI&DS', '3rd Year', 'Female', 'student', 'Active'),
('24IT092', 'Harsh Mehta', 'harsh.mehta@studentshub.edu', '9612345678', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech IT', '1st Year', 'Male', 'student', 'Active'),
('24IT104', 'Rhea Merchant', 'rhea.merchant@studentshub.edu', '9822334455', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech CSE', '4th Year', 'Female', 'student', 'Active'),
('24IT115', 'Manish Shah', 'manish.shah@studentshub.edu', '9877112233', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'BCA', '2nd Year', 'Male', 'student', 'Active'),
('24IT121', 'Isha Kothari', 'isha.kothari@studentshub.edu', '9933445566', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech IT', '3rd Year', 'Female', 'student', 'Active'),
('24IT130', 'Aditya Rao', 'aditya.rao@studentshub.edu', '9944556677', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'M.Tech CSE', '1st Year', 'Male', 'student', 'Active'),
('24IT142', 'Sneha Sen', 'sneha.sen@studentshub.edu', '9955667788', '$2y$12$Imj8koXy3RBGvUe/VCuoAudwpneHVmYXT49LE9NqpcFaImVjITkse', 'B.Tech CSE', '3rd Year', 'Female', 'student', 'Active');

INSERT INTO `events` (`title`, `category`, `event_date`, `event_time`, `location`, `speaker`, `description`, `max_seats`, `badge`) VALUES
('Cloud Computing & DevOps Seminar', 'Seminars', '2026-10-15', '10:00 AM - 1:00 PM', 'Main Auditorium (Room 101)', 'Dr. Rajesh Sharma, Cloud Architect', 'Deep dive into AWS, Google Cloud, Docker, and CI/CD pipelines.', 120, 'Popular'),
('Full-Stack PERN Stack Workshop', 'Workshops', '2026-10-20', '02:00 PM - 5:30 PM', 'Computer Lab 3, IT Dept', 'Prof. Ananya Patel', 'Hands-on session building PERN stack applications with PostgreSQL and React.', 45, 'Fast Filling'),
('Bootstrap & Responsive UI Challenge', 'Competitions', '2026-10-25', '09:00 AM - 04:00 PM', 'Virtual / Hybrid (Lab 2)', 'Frontend Guild & UI/UX Club', 'Speed frontend layout challenge focusing on mobile-first responsive design.', 80, 'Prizes'),
('n8n Workflow Automation Masterclass', 'Masterclasses', '2026-11-02', '03:00 PM - 05:00 PM', 'Virtual Webinar', 'Mr. Kevin Mehta', 'Step-by-step masterclass connecting APIs and Webflow CMS collections via n8n.', 150, 'Online'),
('AI & Deep Learning Symposium', 'Seminars', '2026-11-08', '09:30 AM - 01:30 PM', 'Seminar Hall B', 'Dr. Sarah Lin', 'Transformers, Computer Vision with PyTorch, and Generative AI principles.', 100, 'Featured'),
('Git & GitHub for Open Source Contributors', 'Workshops', '2026-11-12', '11:00 AM - 01:00 PM', 'Lab 1, CS Block', 'Krishna Mangukiya', 'Branches, pull requests, rebase, and collaborative workflows on GitHub.', 50, 'Hands-on'),
('Annual 24-Hour Hackathon: HackTheCampus', 'Hackathons', '2026-11-20', '10:00 AM (24 Hours)', 'Innovation Hub', 'Student Council & Tech Sponsors', 'Build impactful web applications solving campus problems.', 200, 'High Stakes'),
('Cybersecurity & Ethical Hacking Bootcamp', 'Workshops', '2026-11-28', '01:30 PM - 05:00 PM', 'Lab 4, Security Wing', 'Vikram Desai', 'Penetration testing, OWASP Top 10 vulnerabilities defense, and packet inspection.', 40, 'Sold Out'),
('Database Optimization with MySQL & PostgreSQL', 'Masterclasses', '2026-12-04', '10:00 AM - 12:30 PM', 'Room 204, Tech Wing', 'Pooja Varma', 'Index tuning, query execution plan analysis, and connection pooling.', 60, 'Intermediate'),
('Career Prep: Resume & Tech Mock Interviews', 'Seminars', '2026-12-10', '02:00 PM - 05:00 PM', 'Auditorium Annex', 'Alumni Placement Panel', 'DSA walkthroughs, resume critiques, and live mock technical interviews.', 90, 'Must Attend');

INSERT INTO `registrations` (`student_id`, `event_id`, `status`) VALUES
(1, 1, 'Confirmed'),
(1, 3, 'Confirmed'),
(2, 2, 'Confirmed'),
(2, 5, 'Confirmed'),
(3, 4, 'Confirmed'),
(4, 3, 'Confirmed'),
(5, 2, 'Confirmed'),
(6, 1, 'Confirmed'),
(6, 2, 'Confirmed'),
(6, 6, 'Confirmed'),
(7, 5, 'Confirmed'),
(8, 8, 'Confirmed');

INSERT INTO `feedback` (`student_id`, `full_name`, `email`, `category`, `rating`, `message`) VALUES
(6, 'Krishna Mangukiya', 'krishna.mangukiya@studentshub.edu', 'Website UI', 5, 'The light cream palette and clean typography make navigation effortless across phone and desktop!'),
(2, 'Diya Patel', 'diya.patel@studentshub.edu', 'Events', 5, 'The PERN workshop was fantastic and hands-on. Loved the immediate confirmation modal.'),
(1, 'Aarav Sharma', 'aarav.sharma@studentshub.edu', 'Academic Resources', 4, 'Great study resources! Would love to see additional lab manuals for cloud computing.');
