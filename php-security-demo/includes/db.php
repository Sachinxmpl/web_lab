<?php
/**
 * Database helper - creates a SQLite database for demo purposes.
 * In a real app, use MySQL/PostgreSQL with proper credentials management.
 */

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $db_path = __DIR__ . '/../data/demo.sqlite';

    // Ensure data directory exists
    if (!is_dir(dirname($db_path))) {
        mkdir(dirname($db_path), 0755, true);
    }

    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create demo tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            email TEXT,
            balance REAL DEFAULT 1000.00
        );

        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS transfers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_user TEXT NOT NULL,
            to_user TEXT NOT NULL,
            amount REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed demo users if empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO users (username, password, email, balance) VALUES
            ('admin',    'admin123',  'admin@demo.local',  5000.00),
            ('alice',    'password1', 'alice@demo.local',  1000.00),
            ('bob',      'password2', 'bob@demo.local',    750.00),
            ('charlie',  'qwerty',    'charlie@demo.local', 250.00);
        ");
    }

    return $pdo;
}

function seed_comments(): void {
    $pdo = get_db();
    $count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO comments (username, comment) VALUES
            ('alice', 'Great website! Really useful content.'),
            ('bob',   'Looking forward to more tutorials.');
        ");
    }
}
