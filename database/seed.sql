-- =============================================================================
-- StudentHub Portal - Seed Data (Practicals 8, 9, 11)
-- Seed dataset: 21 Students, 16 Events, Users with Bcrypt Hashing, Registrations, Audit Logs
-- =============================================================================

USE `studenthub_db`;

-- 1. Insert Initial Users
-- Password for admin: Admin@123
-- Password for student: Password@123
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'admin', 'admin@charusat.edu.in', '$2y$10$HT4XrFD5yFEpJWOfiR0JG.4DfbVETBR8l8axmg5feLzw0ZcFH7Pb2', 'admin', 'active'),
(2, 'aarav_sharma', 'student@charusat.edu.in', '$2y$10$icDdvIgM9fAT9u5Oh6Z/puHcUGMVKYf36Lz9wP//6YA7DS.P5Xz6K', 'student', 'active'),
(3, 'diya_patel', 'diya.patel@charusat.edu.in', '$2y$10$icDdvIgM9fAT9u5Oh6Z/puHcUGMVKYf36Lz9wP//6YA7DS.P5Xz6K', 'student', 'active'),
(4, 'rohan_verma', 'rohan.verma@charusat.edu.in', '$2y$10$icDdvIgM9fAT9u5Oh6Z/puHcUGMVKYf36Lz9wP//6YA7DS.P5Xz6K', 'student', 'active');

-- 2. Insert 21 Students (Practical 11 Requirement: minimum 20 seeded records)
INSERT INTO `students` (`id`, `user_id`, `student_id`, `name`, `email`, `phone`, `department`, `course`, `year`, `semester`, `gender`, `gpa`, `status`) VALUES
(1, 2, '24IT001', 'Aarav Sharma', 'student@charusat.edu.in', '9825012345', 'Information Technology', 'B.Tech IT', '3rd Year', '5th', 'Male', 8.75, 'active'),
(2, 3, '24CE014', 'Diya Patel', 'diya.patel@charusat.edu.in', '9898123456', 'Computer Engineering', 'B.Tech CE', '3rd Year', '5th', 'Female', 9.20, 'active'),
(3, 4, '24AI008', 'Rohan Verma', 'rohan.verma@charusat.edu.in', '9724123456', 'Artificial Intelligence', 'B.Tech AIML', '2nd Year', '3rd', 'Male', 8.40, 'active'),
(4, NULL, '24IT022', 'Ananya Joshi', 'ananya.joshi@charusat.edu.in', '9662123456', 'Information Technology', 'B.Tech IT', '3rd Year', '5th', 'Female', 9.45, 'active'),
(5, NULL, '24CS035', 'Devansh Mehta', 'devansh.mehta@charusat.edu.in', '9426123456', 'Computer Science & Engineering', 'B.Tech CSE', '4th Year', '7th', 'Male', 8.10, 'active'),
(6, NULL, '24IT049', 'Khushi Shah', 'khushi.shah@charusat.edu.in', '9879123456', 'Information Technology', 'B.Tech IT', '2nd Year', '3rd', 'Female', 8.90, 'active'),
(7, NULL, '24CE055', 'Aditya Rao', 'aditya.rao@charusat.edu.in', '9824123456', 'Computer Engineering', 'B.Tech CE', '3rd Year', '5th', 'Male', 7.85, 'active'),
(8, NULL, '24AI019', 'Pooja Trivedi', 'pooja.trivedi@charusat.edu.in', '9909123456', 'Artificial Intelligence', 'B.Tech AIML', '1st Year', '1st', 'Female', 8.65, 'active'),
(9, NULL, '24CA012', 'Karan Prajapati', 'karan.prajapati@charusat.edu.in', '9712123456', 'Computer Applications', 'BCA', '2nd Year', '3rd', 'Male', 8.30, 'active'),
(10, NULL, '24IT063', 'Sneha Nair', 'sneha.nair@charusat.edu.in', '9890123456', 'Information Technology', 'B.Tech IT', '4th Year', '7th', 'Female', 9.15, 'active'),
(11, NULL, '24CS078', 'Manav Desai', 'manav.desai@charusat.edu.in', '9408123456', 'Computer Science & Engineering', 'B.Tech CSE', '3rd Year', '5th', 'Male', 8.00, 'active'),
(12, NULL, '24CE089', 'Tanvi Bhatia', 'tanvi.bhatia@charusat.edu.in', '9825987654', 'Computer Engineering', 'B.Tech CE', '2nd Year', '3rd', 'Female', 9.35, 'active'),
(13, NULL, '24CA034', 'Yash Solanki', 'yash.solanki@charusat.edu.in', '9724987654', 'Computer Applications', 'MCA', '2nd Year', '3rd', 'Male', 8.50, 'active'),
(14, NULL, '24AI041', 'Ishita Choudhary', 'ishita.choudhary@charusat.edu.in', '9662987654', 'Artificial Intelligence', 'B.Tech AIML', '3rd Year', '5th', 'Female', 8.80, 'active'),
(15, NULL, '24IT077', 'Varun Sengupta', 'varun.sengupta@charusat.edu.in', '9426987654', 'Information Technology', 'B.Tech IT', '1st Year', '1st', 'Male', 8.25, 'active'),
(16, NULL, '24CS092', 'Riya Singhania', 'riya.singhania@charusat.edu.in', '9879987654', 'Computer Science & Engineering', 'B.Tech CSE', '4th Year', '7th', 'Female', 9.60, 'active'),
(17, NULL, '24CE105', 'Harshil Vora', 'harshil.vora@charusat.edu.in', '9824987654', 'Computer Engineering', 'B.Tech CE', '3rd Year', '5th', 'Male', 7.95, 'active'),
(18, NULL, '24IT099', 'Meera Iyer', 'meera.iyer@charusat.edu.in', '9909987654', 'Information Technology', 'B.Tech IT', '3rd Year', '5th', 'Female', 9.05, 'active'),
(19, NULL, '24AI053', 'Siddharth Nambiar', 'siddharth.n@charusat.edu.in', '9712987654', 'Artificial Intelligence', 'B.Tech AIML', '2nd Year', '3rd', 'Male', 8.70, 'active'),
(20, NULL, '24CS118', 'Avani Kulkarni', 'avani.kulkarni@charusat.edu.in', '9890987654', 'Computer Science & Engineering', 'B.Tech CSE', '3rd Year', '5th', 'Female', 9.10, 'active'),
(21, NULL, '24IT110', 'Pranav Dave', 'pranav.dave@charusat.edu.in', '9408987654', 'Information Technology', 'B.Tech IT', '2nd Year', '3rd', 'Male', 8.15, 'active');

-- 3. Insert 16 Events (Practical 8 & 12)
INSERT INTO `events` (`id`, `title`, `description`, `category`, `event_date`, `event_time`, `venue`, `organizer`, `capacity`, `registered_count`, `poster_image`, `status`, `created_by`) VALUES
(1, 'CodeCraft National Hackathon 2026', 'A 24-hour national level hackathon fostering innovative solutions in AI, Web3, and Smart Campus technologies. Prizes worth ₹50,000.', 'Technical', '2026-10-25', '09:00 AM - 09:00 PM', 'Campus Auditorium & Labs', 'Department of IT', 120, 98, '../images/StudentHub.png', 'Open', 1),
(2, 'Full-Stack Web Development Workshop', 'Hands-on masterclass on modern frontend architectures, semantic HTML5, CSS Flexbox/Grid, and PHP REST APIs with MySQL.', 'Workshop', '2026-10-28', '10:00 AM - 04:00 PM', 'Lab 304, IT Block', 'Coding Club', 60, 55, '../images/StudentHub.png', 'Open', 1),
(3, 'AI & Deep Learning Symposium', 'Explore practical AI applications, LLM fine-tuning, and machine vision with industry leaders from top tech giants.', 'Technical', '2026-11-05', '11:00 AM - 03:00 PM', 'Seminar Hall A', 'AIML Research Group', 150, 142, '../images/StudentHub.png', 'Open', 1),
(4, 'Annual Inter-College Sports Meet', 'Athletics, cricket, badminton, basketball, and football tournaments with medals and certificates for all finalists.', 'Sports', '2026-11-12', '08:00 AM - 06:00 PM', 'University Sports Complex', 'Sports Council', 300, 210, '../images/StudentHub.png', 'Open', 1),
(5, 'Cybersecurity & Ethical Hacking Bootcamp', 'Learn network vulnerability assessment, OWASP Top 10 web vulnerabilities, SQL injection prevention, and secure coding.', 'Workshop', '2026-11-18', '01:00 PM - 05:00 PM', 'Cyber Defense Lab', 'CyberSec Society', 50, 50, '../images/StudentHub.png', 'Closed', 1),
(6, 'Spandan Cultural Fest 2026', 'An evening of live band performances, folk & modern dance competitions, fashion shows, and musical celebrity nights.', 'Cultural', '2026-11-24', '05:00 PM - 10:00 PM', 'Open Air Amphitheatre', 'Student Activity Council', 800, 730, '../images/StudentHub.png', 'Open', 1),
(7, 'Cloud Computing with AWS & Docker', 'Learn containerization with Docker, deploying web applications to AWS EC2, S3 bucket storage, and basic CI/CD pipelines.', 'Workshop', '2026-12-02', '09:30 AM - 01:30 PM', 'Cloud Lab 201', 'Cloud Club', 75, 60, '../images/StudentHub.png', 'Open', 1),
(8, 'Robotics & IoT Innovation Expo', 'Showcase of automated rovers, smart home sensors, drone tech, and embedded microcontrollers built by student teams.', 'Technical', '2026-12-08', '10:00 AM - 04:30 PM', 'Innovation Centre', 'Robotics Club', 100, 88, '../images/StudentHub.png', 'Open', 1),
(9, 'Resume Building & Mock Interview Clinic', 'Get your ATS resume reviewed by senior industry HR leaders and practice behavioral & technical mock interviews.', 'Career', '2026-12-14', '02:00 PM - 05:00 PM', 'Training & Placement Cell', 'Placement Cell', 100, 95, '../images/StudentHub.png', 'Open', 1),
(10, 'Campus Chess Championship', 'FIDE-rated swiss league format chess tournament open for all undergraduate and postgraduate students.', 'Sports', '2026-12-19', '09:00 AM - 02:00 PM', 'Student Recreation Centre', 'Chess Club', 64, 64, '../images/StudentHub.png', 'Closed', 1),
(11, 'UI/UX Design Sprint with Figma', 'Design user flows, wireframes, accessible color palettes, and clickable responsive prototypes in Figma.', 'Workshop', '2027-01-08', '10:00 AM - 03:00 PM', 'Design Studio 102', 'Creative Arts Club', 50, 42, '../images/StudentHub.png', 'Open', 1),
(12, 'Tech-Debate: Future of Autonomous AI', 'Parliamentary-style debate exploring ethics, regulation, job impact, and superintelligence of autonomous AI systems.', 'Cultural', '2027-01-15', '03:00 PM - 06:00 PM', 'Conference Hall B', 'Literary & Debating Society', 120, 80, '../images/StudentHub.png', 'Open', 1),
(13, 'Mobile App Development with Flutter', 'Build cross-platform Android and iOS applications with single codebase Dart and Flutter framework from scratch.', 'Workshop', '2027-01-22', '10:00 AM - 04:00 PM', 'Lab 105, Computer Wing', 'Google Developer Student Club', 80, 75, '../images/StudentHub.png', 'Open', 1),
(14, 'Campus Blood Donation & Health Camp', 'Annual voluntary blood donation drive organized in collaboration with Civil Hospital and Red Cross Society.', 'Social', '2027-02-02', '09:00 AM - 04:00 PM', 'Health Centre & Red Cross Tent', 'NSS Student Unit', 200, 145, '../images/StudentHub.png', 'Open', 1),
(15, 'Open Source Contribution Day', 'Celebrate open-source software by making your first GitHub pull requests to famous repositories with mentors.', 'Technical', '2027-02-10', '10:00 AM - 05:00 PM', 'Open Source Lab', 'FOSS Club', 70, 68, '../images/StudentHub.png', 'Open', 1),
(16, 'Alumni Mentorship & Startup Summit', 'Interact with unicorn startup founders and distinguished alumni. Pitch your student startup ideas for seed grants.', 'Career', '2027-02-20', '11:00 AM - 04:00 PM', 'Auditorium Main Hall', 'Alumni Relations & E-Cell', 250, 220, '../images/StudentHub.png', 'Open', 1);

-- 4. Insert Registrations
INSERT INTO `registrations` (`student_id`, `event_id`, `status`, `notes`) VALUES
(1, 1, 'confirmed', 'Team Leader for Web3 Track'),
(1, 2, 'confirmed', 'Attending both morning and afternoon sessions'),
(2, 1, 'confirmed', 'Frontend Developer'),
(2, 3, 'confirmed', 'Presenting poster on Vision Transformers'),
(3, 3, 'confirmed', 'Paper submission shortlisted'),
(4, 2, 'confirmed', 'Registered via StudentHub portal');

-- 5. Insert Initial Audit Logs
INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INIT', 'Database schema initialized and seed records created.', '127.0.0.1'),
(1, 'ADMIN_LOGIN', 'Superadmin logged in to console.', '127.0.0.1'),
(2, 'USER_REGISTER', 'Student account 24IT001 created.', '127.0.0.1'),
(2, 'EVENT_REGISTER', 'Registered for CodeCraft National Hackathon 2026.', '127.0.0.1');
