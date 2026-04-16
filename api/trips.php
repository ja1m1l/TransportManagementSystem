<?php
// api/trips.php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("
            SELECT trips.*, vehicles.license_plate, drivers.name as driver_name 
            FROM trips 
            LEFT JOIN vehicles ON trips.vehicle_id = vehicles.id
            LEFT JOIN drivers ON trips.driver_id = drivers.id
            ORDER BY trips.start_date DESC
        ");
        sendJsonResponse(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['vehicle_id'], $data['driver_id'], $data['destination'], $data['start_date'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO trips (vehicle_id, driver_id, destination, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $end_date = isset($data['end_date']) ? $data['end_date'] : null;
                $status = isset($data['status']) ? $data['status'] : 'Scheduled';
                $stmt->execute([$data['vehicle_id'], $data['driver_id'], $data['destination'], $data['start_date'], $end_date, $status]);
                sendJsonResponse(["status" => "success", "message" => "Trip scheduled successfully", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                sendJsonResponse(["status" => "error", "message" => "Error adding trip: " . $e->getMessage()], 400);
            }
        } else {
            sendJsonResponse(["status" => "error", "message" => "Missing required fields"], 400);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM trips WHERE id = ?");
            $stmt->execute([$data['id']]);
            sendJsonResponse(["status" => "success", "message" => "Trip deleted"]);
        } else {
            sendJsonResponse(["status" => "error", "message" => "ID required"], 400);
        }
        break;

    default:
        sendJsonResponse(["status" => "error", "message" => "Method not allowed"], 405);
}
