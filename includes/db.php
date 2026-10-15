<?php
/**
 * Database Connection & Initialization Layer
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Returns a singleton PDO database connection instance.
 
 * @return PDO
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $dbDir = dirname(SQLITE_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }

            $isNew = !file_exists(SQLITE_PATH) || filesize(SQLITE_PATH) === 0;
            $pdo = new PDO('sqlite:' . SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");

            // If database file was newly created or has no tables 
            if ($isNew || !table_exists($pdo, 'users')) {
                initialize_database($pdo);
            }
        } else {
            // MySQL Connection
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            initialize_mysql_database($pdo);
        }

        return $pdo;
    } catch (PDOException $e) {
        die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
    }
}

/*
 * Ensure the MySQL exists and seed a newly created database.
 */
function initialize_mysql_database(PDO $pdo): void {
    $schema = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'parent', 'hospital') NOT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS hospitals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            reg_number VARCHAR(50) DEFAULT NULL,
            city VARCHAR(80) NOT NULL,
            location VARCHAR(255) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            email VARCHAR(120) DEFAULT NULL,
            status ENUM('active', 'pending_approval', 'inactive') DEFAULT 'active',
            operating_hours VARCHAR(100) DEFAULT '08:00 AM - 05:00 PM',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_hospitals_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS vaccines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            disease_prevented VARCHAR(255) NOT NULL,
            recommended_age VARCHAR(100) NOT NULL,
            recommended_days_from_birth INT DEFAULT 0,
            doses_required INT DEFAULT 1,
            status ENUM('available', 'unavailable') DEFAULT 'available',
            stock_quantity INT DEFAULT 100,
            description TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS children (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            dob DATE NOT NULL,
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            blood_group VARCHAR(10) DEFAULT NULL,
            birth_hospital VARCHAR(150) DEFAULT NULL,
            allergies TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_children_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_reference VARCHAR(30) NOT NULL UNIQUE,
            child_id INT NOT NULL,
            vaccine_id INT NOT NULL,
            hospital_id INT NOT NULL,
            parent_id INT NOT NULL,
            requested_date DATE NOT NULL,
            requested_time_slot VARCHAR(30) NOT NULL,
            status ENUM('pending', 'approved', 'completed', 'missed', 'rejected') DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            rejection_reason TEXT DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            vaccine_batch_no VARCHAR(50) DEFAULT NULL,
            administered_by VARCHAR(100) DEFAULT NULL,
            hospital_remarks TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_appointments_child FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE,
            CONSTRAINT fk_appointments_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines (id) ON DELETE CASCADE,
            CONSTRAINT fk_appointments_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE,
            CONSTRAINT fk_appointments_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS vaccination_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            child_id INT NOT NULL,
            vaccine_id INT NOT NULL,
            hospital_id INT NOT NULL,
            appointment_id INT DEFAULT NULL,
            vaccination_date DATE NOT NULL,
            batch_number VARCHAR(50) DEFAULT NULL,
            doctor_nurse_name VARCHAR(100) DEFAULT NULL,
            dose_number INT DEFAULT 1,
            status ENUM('Vaccinated', 'Not Vaccinated') DEFAULT 'Vaccinated',
            remarks TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_vaccination_records_child FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE,
            CONSTRAINT fk_vaccination_records_vaccine FOREIGN KEY (vaccine_id) REFERENCES vaccines (id) ON DELETE CASCADE,
            CONSTRAINT fk_vaccination_records_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'reminder') DEFAULT 'info',
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($schema as $statement) {
        $pdo->exec($statement);
    }

    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount === 0) {
        seed_database_data($pdo);
    }
}

/*
 * Help to check if a table exists in the database
 */
function table_exists(PDO $pdo, string $tableName): bool {
    try {
        if (DB_DRIVER === 'sqlite') {
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :name");
            $stmt->execute([':name' => $tableName]);
            return (bool)$stmt->fetch();
        } else {
            $stmt = $pdo->prepare("SHOW TABLES LIKE :name");
            $stmt->execute([':name' => $tableName]);
            return (bool)$stmt->fetch();
        }
    } catch (Exception $e) {
        return false;
    }
}

/*
 * Initialize Tables 
 */
function initialize_database(PDO $pdo): void {
    // Schema creation
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(120) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL,
        phone VARCHAR(30),
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS hospitals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        name VARCHAR(150) NOT NULL,
        reg_number VARCHAR(50),
        city VARCHAR(80) NOT NULL,
        location VARCHAR(255) NOT NULL,
        phone VARCHAR(40) NOT NULL,
        email VARCHAR(120),
        status VARCHAR(20) DEFAULT 'active',
        operating_hours VARCHAR(100) DEFAULT '08:00 AM - 05:00 PM',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS vaccines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        disease_prevented VARCHAR(255) NOT NULL,
        recommended_age VARCHAR(100) NOT NULL,
        recommended_days_from_birth INTEGER DEFAULT 0,
        doses_required INTEGER DEFAULT 1,
        status VARCHAR(20) DEFAULT 'available',
        stock_quantity INTEGER DEFAULT 100,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS children (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER NOT NULL,
        name VARCHAR(100) NOT NULL,
        dob DATE NOT NULL,
        gender VARCHAR(10) NOT NULL,
        blood_group VARCHAR(10),
        birth_hospital VARCHAR(150),
        allergies TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_reference VARCHAR(30) UNIQUE NOT NULL,
        child_id INTEGER NOT NULL,
        vaccine_id INTEGER NOT NULL,
        hospital_id INTEGER NOT NULL,
        parent_id INTEGER NOT NULL,
        requested_date DATE NOT NULL,
        requested_time_slot VARCHAR(30) NOT NULL,
        status VARCHAR(30) DEFAULT 'pending',
        admin_notes TEXT,
        rejection_reason TEXT,
        approved_at DATETIME NULL,
        completed_at DATETIME NULL,
        vaccine_batch_no VARCHAR(50) NULL,
        administered_by VARCHAR(100) NULL,
        hospital_remarks TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS vaccination_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        child_id INTEGER NOT NULL,
        vaccine_id INTEGER NOT NULL,
        hospital_id INTEGER NOT NULL,
        appointment_id INTEGER NULL,
        vaccination_date DATE NOT NULL,
        batch_number VARCHAR(50),
        doctor_nurse_name VARCHAR(100),
        dose_number INTEGER DEFAULT 1,
        status VARCHAR(20) DEFAULT 'Vaccinated',
        remarks TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
        FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(30) DEFAULT 'info',
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $pdo->exec($schema);

    // Seed Data
    seed_database_data($pdo);
}

/*
 * Seed initial administrative, hospital, parent, child, vaccine, and appointment data.
 */
function seed_database_data(PDO $pdo): void {
    // 1. Insert Users: Admin, Parents, and Hospital Accounts
    $defaultPasswordHash = password_hash('password123', PASSWORD_DEFAULT);
    $adminPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $hospitalPasswordHash = password_hash('hospital123', PASSWORD_DEFAULT);
    $parentPasswordHash = password_hash('parent123', PASSWORD_DEFAULT);

    $users = [
        // Admin
        [1, 'System Administrator', 'admin@evaccine.org', $adminPasswordHash, 'admin', '+1-800-555-0199', 'Central Health Department HQ'],
        // Parents
        [2, 'Sarah Jenkins', 'parent@evaccine.org', $parentPasswordHash, 'parent', '+1-555-014-8821', '42 Elm Street, Springfield'],
        [3, 'David Miller', 'david@evaccine.org', $parentPasswordHash, 'parent', '+1-555-019-3342', '789 Oakridge Ave, Springfield'],
        // Hospital User Accounts
        [4, 'City General Hospital Staff', 'cityhospital@evaccine.org', $hospitalPasswordHash, 'hospital', '+1-555-011-2000', '100 Medical Center Blvd, Springfield'],
        [5, 'Metro Pediatric Clinic Staff', 'metrocare@evaccine.org', $hospitalPasswordHash, 'hospital', '+1-555-012-3400', '45 Healthcare Plaza, Springfield'],
        [6, 'St. Mary\'s Health Center Staff', 'stmarys@evaccine.org', $hospitalPasswordHash, 'hospital', '+1-555-013-7800', '12 Hope Parkway, Springfield']
    ];

    $userStmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($users as $user) {
        $userStmt->execute($user);
    }

    // 2. Insert Hospitals
    $hospitals = [
        [1, 4, 'City General Children Hospital', 'HOSP-SP-101', 'Springfield', '100 Medical Center Blvd, Central Springfield', '+1-555-011-2000', 'cityhospital@evaccine.org', 'active', '08:00 AM - 06:00 PM'],
        [2, 5, 'Metro Pediatric Care & Maternity', 'HOSP-SP-102', 'Springfield', '45 Healthcare Plaza, North Springfield', '+1-555-012-3400', 'metrocare@evaccine.org', 'active', '08:30 AM - 05:00 PM'],
        [3, 6, 'St. Mary\'s Family Health Center', 'HOSP-SP-103', 'Springfield', '12 Hope Parkway, South Springfield', '+1-555-013-7800', 'stmarys@evaccine.org', 'active', '09:00 AM - 04:30 PM']
    ];

    $hospStmt = $pdo->prepare("INSERT INTO hospitals (id, user_id, name, reg_number, city, location, phone, email, status, operating_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($hospitals as $hosp) {
        $hospStmt->execute($hosp);
    }

}

/**
 * Execute a query with parameters and return PDOStatement
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $pdo = get_db();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/*
 * Fetch all matching rows
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Fetch a single row
 */
function db_fetch_one(string $sql, array $params = []) {
    return db_query($sql, $params)->fetch();
}

/*
 * Execute an INSERT, UPDATE, or DELETE query 
 */
function db_execute(string $sql, array $params = []): int {
    $stmt = db_query($sql, $params);
    return $stmt->rowCount();
}

/*
 * Get the ID of the last inserted record
 */
function db_last_id(): string {
    return get_db()->lastInsertId();
}
