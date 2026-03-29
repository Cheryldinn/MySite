<?php
$host = "localhost";
$user = "root";
$pass = "";

// Connect to MySQL server
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database
$conn->query("CREATE DATABASE IF NOT EXISTS school_timetable");
$conn->select_db("school_timetable");

// USERS TABLE
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// CLASSES TABLE
$conn->query("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// SUBJECTS TABLE
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// TEACHERS TABLE
$conn->query("CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    subject_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// TIMETABLE TABLE
$conn->query("CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day VARCHAR(20) NOT NULL,
    time_slot VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
)");

// Insert default admin user if none exists
$result = $conn->query("SELECT * FROM users WHERE username = 'admin' LIMIT 1");
if ($result->num_rows == 0) {
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $adminUser, $password);
    $adminUser = "admin";
    $stmt->execute();
    $stmt->close();
}

// Insert sample data if tables are empty
$classCount = $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'];
if ($classCount == 0) {
    $conn->query("INSERT INTO classes (class_name, section) VALUES
        ('Form 1', 'A'), ('Form 1', 'B'),
        ('Form 2', 'A'), ('Form 2', 'B'),
        ('Form 3', 'A'), ('Form 4', 'A'),
        ('Form 5', 'A'), ('Form 6', 'A')");

    $conn->query("INSERT INTO subjects (subject_name, subject_code) VALUES
        ('Mathematics', 'MATH'),
        ('English Language', 'ENG'),
        ('Physics', 'PHY'),
        ('Chemistry', 'CHEM'),
        ('Biology', 'BIO'),
        ('History', 'HIST'),
        ('Geography', 'GEO'),
        ('Computer Science', 'CS'),
        ('French', 'FRE'),
        ('Physical Education', 'PE')");

    $conn->query("INSERT INTO teachers (teacher_name, email, subject_id) VALUES
        ('Mr. Nkomo Jean', 'jnkomo@school.cm', 1),
        ('Mrs. Biya Grace', 'gbiya@school.cm', 2),
        ('Dr. Tchoupo Paul', 'ptchoupo@school.cm', 3),
        ('Ms. Atanga Rose', 'ratanga@school.cm', 4),
        ('Mr. Fouda Eric', 'efouda@school.cm', 5),
        ('Mrs. Mvondo Claire', 'cmvondo@school.cm', 6),
        ('Mr. Essomba Alain', 'aessomba@school.cm', 7),
        ('Ms. Ngono Sandra', 'sngono@school.cm', 8),
        ('Mr. Mbarga Yves', 'ymbarga@school.cm', 9),
        ('Mrs. Owona Lydie', 'lowona@school.cm', 10)");
}
?>
