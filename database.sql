-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','nurse','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_no VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(50),
    course VARCHAR(100),
    age INT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create health_records table
CREATE TABLE IF NOT EXISTS health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    allergies TEXT,
    conditions TEXT,
    blood_pressure VARCHAR(20),
    temperature DECIMAL(5,2),
    weight DECIMAL(6,2),
    check_date DATETIME,
    last_check_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Create clinic_visits table
CREATE TABLE IF NOT EXISTS clinic_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    visit_date DATETIME NOT NULL,
    complaint TEXT NOT NULL,
    treatment TEXT NOT NULL,
    status ENUM('ongoing','completed') DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert demo admin user
INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@schord.com', '$2y$10$slYQmyNdGzin7olVN3/p2OPST9/PgBkqquzi.Ge3SEUgVF3GA9H4m', 'admin')
ON DUPLICATE KEY UPDATE name=name;