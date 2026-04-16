<?php
// api/auth.php
require_once 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($method === 'POST' && $action === 'signup') {
        if (isset($data['name'], $data['username'], $data['password'])) {
            $dob = isset($data['dob']) ? $data['dob'] : null;
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, dob, username, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data['name'], $dob, $data['username'], $hash]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $data['username'];
                $_SESSION['name'] = $data['name'];
                sendJsonResponse(["status" => "success", "message" => "Signed up!"]);
            } catch (PDOException $e) {
                sendJsonResponse(["status" => "error", "message" => "Error: Username might be taken."], 400);
            }
        }
    } else if ($method === 'POST' && $action === 'login') {
        if (isset($data['username'], $data['password'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            $user = $stmt->fetch();
            if ($user && password_verify($data['password'], $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                sendJsonResponse(["status" => "success", "message" => "Logged in!"]);
            } else {
                sendJsonResponse(["status" => "error", "message" => "Invalid credentials"], 401);
            }
        }
    } else if ($method === 'POST' && $action === 'logout') {
        session_destroy();
        sendJsonResponse(["status" => "success"]);
    } else if ($method === 'GET' && $action === 'profile') {
        if (!isset($_SESSION['user_id'])) sendJsonResponse(["status" => "error"], 401);
        $stmt = $pdo->prepare("SELECT id, name, dob, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        sendJsonResponse(["status" => "success", "data" => $stmt->fetch()]);
    } else if ($method === 'PUT' && $action === 'profile') {
        if (!isset($_SESSION['user_id'])) sendJsonResponse(["status" => "error"], 401);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, dob = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['dob'], $_SESSION['user_id']]);
        if (!empty($data['password'])) {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($data['password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
        }
        sendJsonResponse(["status" => "success"]);
    } else if ($method === 'GET' && $action === 'check') {
        if (isset($_SESSION['user_id'])) {
            sendJsonResponse(["status" => "success", "name" => $_SESSION['name']]);
        } else {
            sendJsonResponse(["status" => "error"], 401);
        }
    }
}
