-- Accident Detection and Nearest Hospital Alert System Database Schema
-- MySQL 8.0+ compatible

-- Create database
CREATE DATABASE IF NOT EXISTS accident_detection_system;
USE accident_detection_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'hospital', 'admin') DEFAULT 'user',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Cars table
CREATE TABLE cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    sensor_status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    model VARCHAR(100),
    year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner_id (owner_id),
    INDEX idx_plate_number (plate_number),
    INDEX idx_sensor_status (sensor_status)
);

-- Hospitals table
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    ambulance_available BOOLEAN DEFAULT TRUE,
    address TEXT,
    phone VARCHAR(20),
    emergency_contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location (latitude, longitude),
    INDEX idx_ambulance_available (ambulance_available),
    SPATIAL INDEX idx_spatial_location (latitude, longitude)
);

-- Accidents table
CREATE TABLE accidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_id INT NOT NULL,
    location_lat DECIMAL(10, 8) NOT NULL,
    location_lng DECIMAL(11, 8) NOT NULL,
    accident_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
    description TEXT,
    nearest_hospital_id INT,
    estimated_distance DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (nearest_hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
    INDEX idx_car_id (car_id),
    INDEX idx_accident_time (accident_time),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_location (location_lat, location_lng),
    INDEX idx_nearest_hospital (nearest_hospital_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accident_id INT NOT NULL,
    hospital_id INT NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('alert', 'update', 'status_change') DEFAULT 'alert',
    sent_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'delivered', 'read', 'failed') DEFAULT 'sent',
    read_at TIMESTAMP NULL,
    FOREIGN KEY (accident_id) REFERENCES accidents(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    INDEX idx_accident_id (accident_id),
    INDEX idx_hospital_id (hospital_id),
    INDEX idx_sent_time (sent_time),
    INDEX idx_status (status)
);

-- Accident status history table for tracking changes
CREATE TABLE accident_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accident_id INT NOT NULL,
    old_status ENUM('pending', 'in_progress', 'resolved', 'cancelled'),
    new_status ENUM('pending', 'in_progress', 'resolved', 'cancelled') NOT NULL,
    changed_by INT NOT NULL,
    change_reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accident_id) REFERENCES accidents(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_accident_id (accident_id),
    INDEX idx_changed_at (changed_at)
);

-- Sample data insertion

-- Insert sample users
INSERT INTO users (name, email, password_hash, role, phone) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '+1234567890'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '+1234567891'),
('City General Hospital', 'hospital@citygeneral.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hospital', '+1234567892'),
('Emergency Medical Center', 'contact@emc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hospital', '+1234567893'),
('Admin User', 'admin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+1234567894');

-- Insert sample hospitals
INSERT INTO hospitals (name, latitude, longitude, ambulance_available, address, phone, emergency_contact) VALUES
('City General Hospital', 40.7128, -74.0060, TRUE, '123 Main St, New York, NY', '+1234567892', '+1234567892'),
('Emergency Medical Center', 40.7589, -73.9851, TRUE, '456 Broadway, New York, NY', '+1234567893', '+1234567893'),
('Downtown Medical Center', 40.7505, -73.9934, FALSE, '789 5th Ave, New York, NY', '+1234567895', '+1234567895'),
('Brooklyn Emergency Hospital', 40.6782, -73.9442, TRUE, '321 Atlantic Ave, Brooklyn, NY', '+1234567896', '+1234567896'),
('Queens Medical Center', 40.7282, -73.7949, TRUE, '654 Queens Blvd, Queens, NY', '+1234567897', '+1234567897');

-- Insert sample cars
INSERT INTO cars (owner_id, plate_number, sensor_status, model, year) VALUES
(1, 'ABC123', 'active', 'Toyota Camry', 2020),
(1, 'XYZ789', 'active', 'Honda Civic', 2019),
(2, 'DEF456', 'active', 'Ford Focus', 2021),
(2, 'GHI789', 'maintenance', 'Nissan Altima', 2018);

-- Insert sample accidents
INSERT INTO accidents (car_id, location_lat, location_lng, accident_time, severity, status, description, nearest_hospital_id, estimated_distance) VALUES
(1, 40.7128, -74.0060, '2024-01-15 10:30:00', 'medium', 'resolved', 'Minor collision at intersection', 1, 0.5),
(2, 40.7589, -73.9851, '2024-01-16 14:20:00', 'high', 'in_progress', 'Multi-vehicle accident on highway', 2, 1.2),
(3, 40.7505, -73.9934, '2024-01-17 08:45:00', 'low', 'pending', 'Fender bender in parking lot', 3, 0.8),
(4, 40.6782, -73.9442, '2024-01-18 16:15:00', 'critical', 'pending', 'Serious accident requiring immediate attention', 4, 2.1);

-- Insert sample notifications
INSERT INTO notifications (accident_id, hospital_id, message, notification_type, status) VALUES
(1, 1, 'New accident reported near your location. Severity: Medium', 'alert', 'delivered'),
(2, 2, 'Critical accident reported. Ambulance required immediately.', 'alert', 'sent'),
(3, 3, 'Minor accident reported. No immediate action required.', 'alert', 'sent'),
(4, 4, 'Critical accident reported. Multiple ambulances may be needed.', 'alert', 'sent');

-- Insert sample status history
INSERT INTO accident_status_history (accident_id, old_status, new_status, changed_by, change_reason) VALUES
(1, 'pending', 'in_progress', 3, 'Ambulance dispatched'),
(1, 'in_progress', 'resolved', 3, 'Patient treated and discharged'),
(2, 'pending', 'in_progress', 4, 'Emergency response team deployed');

-- Create views for common queries
CREATE VIEW accident_summary AS
SELECT 
    a.id,
    a.accident_time,
    a.severity,
    a.status,
    c.plate_number,
    u.name as owner_name,
    h.name as hospital_name,
    a.estimated_distance
FROM accidents a
JOIN cars c ON a.car_id = c.id
JOIN users u ON c.owner_id = u.id
LEFT JOIN hospitals h ON a.nearest_hospital_id = h.id;

CREATE VIEW hospital_activity AS
SELECT 
    h.id,
    h.name,
    h.ambulance_available,
    COUNT(a.id) as total_accidents,
    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_accidents,
    COUNT(CASE WHEN a.status = 'in_progress' THEN 1 END) as active_accidents
FROM hospitals h
LEFT JOIN accidents a ON h.id = a.nearest_hospital_id
GROUP BY h.id, h.name, h.ambulance_available;

-- Create stored procedure for finding nearest hospital
DELIMITER //
CREATE PROCEDURE FindNearestHospital(
    IN accident_lat DECIMAL(10, 8),
    IN accident_lng DECIMAL(11, 8)
)
BEGIN
    SELECT 
        id,
        name,
        latitude,
        longitude,
        ambulance_available,
        (
            6371 * acos(
                cos(radians(accident_lat)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(accident_lng)) + 
                sin(radians(accident_lat)) * 
                sin(radians(latitude))
            )
        ) AS distance_km
    FROM hospitals
    WHERE ambulance_available = TRUE
    ORDER BY distance_km ASC
    LIMIT 1;
END //
DELIMITER ;

-- Create stored procedure for daily accident statistics
DELIMITER //
CREATE PROCEDURE GetDailyAccidentStats(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
    SELECT 
        DATE(accident_time) as accident_date,
        COUNT(*) as total_accidents,
        COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity,
        COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
        COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
        COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_severity,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
    FROM accidents
    WHERE DATE(accident_time) BETWEEN start_date AND end_date
    GROUP BY DATE(accident_time)
    ORDER BY accident_date;
END //
DELIMITER ;
