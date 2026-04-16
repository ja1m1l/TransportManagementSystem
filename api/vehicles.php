<?php
// api/vehicles.php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC");
        sendJsonResponse(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['make'], $data['model'], $data['year'], $data['license_plate'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicles (make, model, year, license_plate, status) VALUES (?, ?, ?, ?, ?)");
                $status = isset($data['status']) ? $data['status'] : 'Active';
                $stmt->execute([$data['make'], $data['model'], $data['year'], $data['license_plate'], $status]);
                sendJsonResponse(["status" => "success", "message" => "Vehicle added successfully", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                sendJsonResponse(["status" => "error", "message" => "Error adding vehicle: " . $e->getMessage()], 400);
            }
        } else {
            sendJsonResponse(["status" => "error", "message" => "Missing required fields"], 400);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$data['id']]);
            sendJsonResponse(["status" => "success", "message" => "Vehicle deleted"]);
        } else {
            sendJsonResponse(["status" => "error", "message" => "ID required"], 400);
        }
        break;

    default:
        sendJsonResponse(["status" => "error", "message" => "Method not allowed"], 405);
}
