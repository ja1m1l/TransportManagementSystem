<?php
// api/dashboard.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
    $stats['total_vehicles'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Active'");
    $stats['active_vehicles'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers");
    $stats['total_drivers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'Available'");
    $stats['available_drivers'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'Scheduled' OR status = 'In Progress'");
    $stats['active_trips'] = $stmt->fetchColumn();

    // Recent trips for widget
    $stmt = $pdo->query("SELECT trips.id, trips.destination, trips.status, vehicles.license_plate, drivers.name as driver_name 
                         FROM trips 
                         LEFT JOIN vehicles ON trips.vehicle_id = vehicles.id
                         LEFT JOIN drivers ON trips.driver_id = drivers.id
                         ORDER BY trips.start_date DESC LIMIT 5");
    $stats['recent_trips'] = $stmt->fetchAll();

    sendJsonResponse(["status" => "success", "data" => $stats]);
} else {
    sendJsonResponse(["status" => "error", "message" => "Method not allowed"], 405);
}
