CREATE DATABASE IF NOT EXISTS msantha_pigs;
USE msantha_pigs;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    phone VARCHAR(20),
    full_name VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS pigs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_no VARCHAR(50) UNIQUE NOT NULL,
    sex VARCHAR(10) NOT NULL,
    breed VARCHAR(50),
    dob DATE NOT NULL,
    sire VARCHAR(50),
    dam VARCHAR(50),
    status VARCHAR(20) DEFAULT 'active',
    stage VARCHAR(20) DEFAULT 'adult',
    source VARCHAR(50) DEFAULT 'Born on Farm',
    castrated TINYINT(1) DEFAULT 0,
    castration_date DATE NULL
);

CREATE TABLE IF NOT EXISTS growth_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pig_id INT NOT NULL,
    date DATE NOT NULL,
    weight DECIMAL(10,2),
    age_days INT,
    remarks TEXT,
    FOREIGN KEY(pig_id) REFERENCES pigs(id)
);

CREATE TABLE IF NOT EXISTS breeding_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pig_id INT NOT NULL,
    date_of_service DATE NOT NULL,
    sire_no VARCHAR(50),
    expected_farrowing DATE,
    actual_farrowing DATE,
    total_born INT,
    born_alive INT,
    stillborn INT,
    avg_weaning_wt DECIMAL(10,2),
    weaning_date DATE NULL,
    weaned_count INT NULL,
    status VARCHAR(20) DEFAULT 'pregnant',
    FOREIGN KEY(pig_id) REFERENCES pigs(id)
);

CREATE TABLE IF NOT EXISTS vaccination_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pig_id INT NOT NULL,
    date DATE NOT NULL,
    vaccine VARCHAR(100),
    dose VARCHAR(50),
    route VARCHAR(50),
    administered_by VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY(pig_id) REFERENCES pigs(id)
);

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    reference_id VARCHAR(50),
    weight DECIMAL(10,2),
    date DATE NOT NULL,
    amount DECIMAL(10,2),
    buyer_info VARCHAR(255),
    remarks TEXT
);

CREATE TABLE IF NOT EXISTS mortality (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pig_id INT NOT NULL,
    date DATE NOT NULL,
    cause VARCHAR(255),
    remarks TEXT,
    FOREIGN KEY(pig_id) REFERENCES pigs(id)
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NOT NULL,
    user_role VARCHAR(20) DEFAULT 'clerk',
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(30) NOT NULL DEFAULT 'info',
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    pig_id INT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default users (Password is 'admin123' and 'clerk123', hashed using bcrypt)
-- You can also run the application once to auto-seed these users if they don't exist.

