<?php
// api/setup.php
require_once 'config.php';

try {
    // Vehicles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        make TEXT NOT NULL,
        model TEXT NOT NULL,
        year INTEGER NOT NULL,
        license_plate TEXT UNIQUE NOT NULL,
        status TEXT DEFAULT 'Active'
    )");

    // Drivers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS drivers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        license_number TEXT UNIQUE NOT NULL,
        phone TEXT,
        status TEXT DEFAULT 'Available'
    )");

    // Trips table
    $pdo->exec("CREATE TABLE IF NOT EXISTS trips (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehicle_id INTEGER,
        driver_id INTEGER,
        destination TEXT NOT NULL,
        start_date TEXT NOT NULL,
        end_date TEXT,
        status TEXT DEFAULT 'Scheduled',
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
        FOREIGN KEY (driver_id) REFERENCES drivers(id)
    )");

    // Optional: insert some dummy data if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO vehicles (make, model, year, license_plate, status) VALUES 
            ('Volvo', 'FH16', 2022, 'TRK-001', 'Active'),
            ('Mercedes-Benz', 'Actros', 2021, 'TRK-002', 'Active')");

        $pdo->exec("INSERT INTO drivers (name, license_number, phone, status) VALUES 
            ('Jaimil Patel', 'DL-1001', '+1234567890', 'Available'),
            ('Atharva Gholap', 'DL-1002', '+0987654321', 'Available')");

        $pdo->exec("INSERT INTO trips (vehicle_id, driver_id, destination, start_date, status) VALUES 
            (1, 1, 'New York City', '2026-04-20', 'Scheduled')");
    }

    sendJsonResponse(["status" => "success", "message" => "Database setup complete."]);

} catch (PDOException $e) {
    sendJsonResponse(["status" => "error", "message" => $e->getMessage()], 500);
}
