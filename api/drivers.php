<?php
// api/drivers.php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM drivers ORDER BY id DESC");
        sendJsonResponse(["status" => "success", "data" => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['name'], $data['license_number'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO drivers (name, license_number, phone, status) VALUES (?, ?, ?, ?)");
                $phone = isset($data['phone']) ? $data['phone'] : null;
                $status = isset($data['status']) ? $data['status'] : 'Available';
                $stmt->execute([$data['name'], $data['license_number'], $phone, $status]);
                sendJsonResponse(["status" => "success", "message" => "Driver added successfully", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                sendJsonResponse(["status" => "error", "message" => "Error adding driver: " . $e->getMessage()], 400);
            }
        } else {
            sendJsonResponse(["status" => "error", "message" => "Missing required fields"], 400);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$data['id']]);
            sendJsonResponse(["status" => "success", "message" => "Driver deleted"]);
        } else {
            sendJsonResponse(["status" => "error", "message" => "ID required"], 400);
        }
        break;

    default:
        sendJsonResponse(["status" => "error", "message" => "Method not allowed"], 405);
}
