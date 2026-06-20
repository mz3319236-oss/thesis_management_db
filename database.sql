CREATE DATABASE IF NOT EXISTS thesis_management_db;
USE thesis_management_db;

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `university_id` varchar(50) NOT NULL,
  `role` enum('student','supervisor','admin') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `university_id` (`university_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `theses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text NOT NULL,
  `domain` varchar(100) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `status` enum('pending','under_review','revision_required','approved','rejected') NOT NULL DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default departments
INSERT INTO `departments` (`name`) VALUES 
('Computer Science'), ('Information Technology'), ('Software Engineering'), ('Department of Business Administration');

-- Insert default admin (Password is 'password', is_approved=1 so admin can login directly)
INSERT INTO `users` (`full_name`, `email`, `password`, `university_id`, `role`, `is_approved`) VALUES 
('System Admin', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN001', 'admin', 1);
