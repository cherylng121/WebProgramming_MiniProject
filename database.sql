-- Event Management System Database Schema
-- 7 Tables: users, login, events, event_registrations, user_profiles, event_categories, event_category_mapping

CREATE DATABASE IF NOT EXISTS event_management;
USE event_management;

-- 1. Users table - Basic user information
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    address TEXT,
    age INT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Login table - Authentication and user levels
CREATE TABLE login (
    login_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_level ENUM('1', '2', '3') NOT NULL, -- 1=Admin, 2=Event Organizer, 3=Student
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. User profiles table - Extended user information
CREATE TABLE user_profiles (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    matric_number VARCHAR(20) UNIQUE,
    department VARCHAR(100),
    year_of_study INT,
    bio TEXT,
    profile_picture VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Event categories table
CREATE TABLE event_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff'
);

-- 5. Events table - Event details
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(200),
    capacity INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 6. Event category mapping table - Many-to-many relationship
CREATE TABLE event_category_mapping (
    mapping_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    category_id INT,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES event_categories(category_id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_category (event_id, category_id)
);

-- 7. Event registrations table - Student registrations
CREATE TABLE event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    user_id INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    attendance_notes TEXT,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id)
);

-- Insert sample data
INSERT INTO event_categories (category_name, description, color) VALUES
('Academic', 'Educational and learning events', '#28a745'),
('Social', 'Social gatherings and networking', '#ffc107'),
('Sports', 'Sports and fitness activities', '#dc3545'),
('Cultural', 'Cultural and artistic events', '#6f42c1'),
('Technology', 'Tech workshops and seminars', '#17a2b8');


-- USERS
INSERT INTO users (user_id, name, email, phone, address, age, created_at)
VALUES
(1, 'Jee Min', 'jminn@university.edu', '+1234567890', '123, Jalan Meru, 57100 Kuala Lumpur, Malaysia', 35, '2025-06-01 09:00:00'),
(2, 'Jin En', 'jinen@university.edu', '+1234567891', '223, Jalan Gasing, 81300 Skudai, Malaysia', 29, '2025-06-15 10:30:00'),
(3, 'En Dhong', 'led@university.edu', '+1234567892', '972, Jalan Kenari, 43000 Klang, Malaysia', 32, '2025-05-10 14:00:00'),
(4, 'Wern Min', 'wmin@student.edu', '+1234567893', '678, Jalan Molek, 81100 Johor Bahru, Malaysia', 20, '2025-06-05 08:00:00'),
(5, 'Jackson', 'jackson@student.edu', '+1234567894', '312, Jalan Kota, 81100 Johor Bahru, Malaysia', 21, '2025-05-12 11:00:00'),
(6, 'Ahmad', 'ahmad@student.edu', '+1234567895', '553, Jalan Matahari, 10200 Penang, Malaysia', 22, '2025-05-20 13:00:00');

-- LOGIN
INSERT INTO login (user_id, username, password, user_level, is_active, last_login)
VALUES
(1, 'jminn', '$2a$12$dufvpsFjKLdNPrv/OVA5TebzBNVG8rZR9XShI93odcEg.xsds1pK.', 1, 1, '2025-06-14 09:00:00'), //pw:jminn@123
(2, 'jinen', '$2a$12$y34n5z46MScQQuGCzkreOumMNffzduthIco7hWd2z4P93bKlFmIWC', 2, 1, '2025-06-16 10:00:00'), //pw:jinen@123
(3, 'led', '$2a$12$iuJVVgYi9BkBAl0kwe7wdOcTJZz2.cZb.v5kjJx3b9VK1bDWE2c3O', 2, 1, '2025-06-15 11:00:00'), //pw:led@123
(4, 'wmin', '$2a$12$unuHW6wLPTkQl0jV7/Las.bmQ18slJJ.HxvLjWjl5nOwFXNGZvp4G', 3, 1, '2025-06-17 12:00:00'), //pw:wmin@123
(5, 'jackson', '$2a$12$/pj4g8l7tNyl9TaKcw25HenUPY9BhIp95fB95OxeUTkusQ99SjZZG', 3, 1, '2025-06-18 13:00:00'), //pw:jackson@123
(6, 'ahmad', '$2a$12$kJsudj1HrByT6RQZPW0sbecBAvomm.gqM1zAEGeqQY77r0pFiedbi', 3, 1, '2025-06-19 14:00:00'); //pw:ahmad@123

-- USER PROFILES
INSERT INTO user_profiles (user_id, matric_number, department, year_of_study)
VALUES
(2, 'A1001', 'Event Management', 2),
(3, 'A1002', 'Event Management', 3),
(4, 'A1003', 'Computer Science', 2),
(5, 'A1004', 'Engineering', 3),
(6, 'A1005', 'Business', 1);

-- EVENTS
INSERT INTO events (event_id, title, description, event_date, event_time, location, capacity, status, created_by, created_at)
VALUES
(1, 'Tech Talk', 'A talk on latest tech trends.', '2025-06-10', '10:00:00', 'Auditorium', 100, 'approved', 2, '2025-05-01 09:00:00'),
(2, 'Art Workshop', 'Hands-on art workshop.', '2025-06-12', '14:00:00', 'Art Room', 30, 'approved', 2, '2025-05-02 10:00:00'),
(3, 'Business Seminar', 'Seminar on business skills.', '2025-07-15', '09:00:00', 'Conference Hall', 50, 'pending', 3, '2025-06-03 11:00:00'),
(4, 'Coding Bootcamp', 'Intensive coding bootcamp.', '2025-07-20', '08:00:00', 'Lab 1', 40, 'approved', 2, '2025-06-04 12:00:00'),
(5, 'Robotics Expo', 'Showcase of student robots.', '2025-07-22', '13:00:00', 'Exhibition Center', 200, 'approved', 3, '2025-06-05 13:00:00'),
(6, 'Music Night', 'Live music performances.', '2025-07-25', '18:00:00', 'Open Grounds', 150, 'approved', 2, '2025-06-06 14:00:00'),
(7, 'Sports Meet', 'Annual sports event.', '2025-06-28', '07:00:00', 'Sports Complex', 300, 'pending', 3, '2025-06-07 15:00:00'),
(8, 'Career Fair', 'Meet top recruiters.', '2025-06-01', '10:00:00', 'Main Hall', 250, 'approved', 2, '2025-05-08 16:00:00'),
(9, 'Science Quiz', 'Quiz competition.', '2025-07-05', '11:00:00', 'Lecture Hall', 60, 'approved', 3, '2025-06-09 17:00:00'),
(10, 'Drama Fest', 'Drama performances.', '2025-05-10', '17:00:00', 'Auditorium', 120, 'approved', 2, '2025-03-10 18:00:00');

-- EVENT REGISTRATIONS
INSERT INTO event_registrations (registration_id, event_id, user_id, registration_date, status)
VALUES
(1, 1, 4, '2025-06-15 10:00:00', 'registered'),
(2, 2, 5, '2025-06-16 11:00:00', 'registered'),
(3, 3, 6, '2025-06-17 12:00:00', 'registered'),
(4, 4, 4, '2025-06-18 13:00:00', 'attended'),
(5, 5, 5, '2025-06-19 14:00:00', 'registered'),
(6, 6, 6, '2025-06-20 15:00:00', 'registered'),
(7, 7, 4, '2025-06-21 16:00:00', 'registered'),
(8, 8, 5, '2025-06-22 17:00:00', 'registered'),
(9, 9, 6, '2025-06-23 18:00:00', 'registered'),
(10, 10, 4, '2025-06-24 19:00:00', 'registered');
