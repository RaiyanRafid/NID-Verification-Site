<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'id_verification');

try {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if ($conn->query($sql) === TRUE) {
        $conn->select_db(DB_NAME);
        
        // Create customers table
        $sql = "CREATE TABLE IF NOT EXISTS customers (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50),
            last_name VARCHAR(50) NOT NULL,
            address TEXT NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL,
            document_type ENUM('nid', 'passport', 'birth') NOT NULL,
            document_path VARCHAR(255) NOT NULL,
            document_path_back VARCHAR(255),
            status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
            rejection_reason TEXT,
            admin_comments TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);

        // Create admin table
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);

        // Create audit_log table with correct structure
        $sql = "CREATE TABLE IF NOT EXISTS audit_log (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11),
            action VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE SET NULL
        )";
        $conn->query($sql);

        // Create smtp_settings table
        $sql = "CREATE TABLE IF NOT EXISTS smtp_settings (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            host VARCHAR(255) NOT NULL,
            port INT NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            from_email VARCHAR(255) NOT NULL,
            from_name VARCHAR(255) NOT NULL,
            encryption ENUM('none', 'tls', 'ssl') NOT NULL DEFAULT 'tls',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);

        // Add missing columns to audit_log if they don't exist
        $columns_to_add_audit = [
            'ip_address' => "ALTER TABLE audit_log ADD COLUMN ip_address VARCHAR(45)",
            'user_agent' => "ALTER TABLE audit_log ADD COLUMN user_agent TEXT",
            'description' => "ALTER TABLE audit_log ADD COLUMN description TEXT NOT NULL",
            'created_at' => "ALTER TABLE audit_log ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];

        foreach ($columns_to_add_audit as $column => $sql) {
            $check_column = $conn->query("SHOW COLUMNS FROM audit_log LIKE '$column'");
            if ($check_column->num_rows === 0) {
                $conn->query($sql);
            }
        }

        // Add missing columns to admins table if they don't exist
        $columns_to_add_admins = [
            'first_name' => "ALTER TABLE admins ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT ''",
            'last_name' => "ALTER TABLE admins ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT ''",
            'email' => "ALTER TABLE admins ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT ''",
            'phone' => "ALTER TABLE admins ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT ''",
            'created_at' => "ALTER TABLE admins ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE admins ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];

        foreach ($columns_to_add_admins as $column => $sql) {
            $check_column = $conn->query("SHOW COLUMNS FROM admins LIKE '$column'");
            if ($check_column->num_rows === 0) {
                $conn->query($sql);
            }
        }

        // Add unique constraints if they don't exist
        $check_username_index = $conn->query("SHOW INDEX FROM admins WHERE Column_name = 'username' AND Non_unique = 0");
        if ($check_username_index->num_rows === 0) {
            $conn->query("ALTER TABLE admins ADD UNIQUE (username)");
        }

        $check_email_index = $conn->query("SHOW INDEX FROM admins WHERE Column_name = 'email' AND Non_unique = 0");
        if ($check_email_index->num_rows === 0) {
            $conn->query("ALTER TABLE admins ADD UNIQUE (email)");
        }

        // Add document_type column if it doesn't exist
        $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'document_type'");
        if ($check_column->num_rows === 0) {
            $conn->query("ALTER TABLE customers ADD COLUMN document_type ENUM('nid', 'passport', 'birth') NOT NULL AFTER email");
        }

        // Add document_path_back column if it doesn't exist
        $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'document_path_back'");
        if ($check_column->num_rows === 0) {
            $conn->query("ALTER TABLE customers ADD COLUMN document_path_back VARCHAR(255) AFTER document_path");
        }

        // Add rejection_reason column if it doesn't exist
        $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'rejection_reason'");
        if ($check_column->num_rows === 0) {
            $conn->query("ALTER TABLE customers ADD COLUMN rejection_reason TEXT AFTER status");
        }
    }
} catch (Exception $e) {
    // Display user-friendly error message
    die("Database Connection Error: Please make sure MySQL is running and try again. If the problem persists, contact your system administrator.");
}
?> 