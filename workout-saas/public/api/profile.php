<?php
// public/api/profile.php
require "../../src/db.php";
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false]); exit(); }

$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$action = $data['action'] ?? '';

try {
    if ($action === 'update') {
        $name = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';
        $password2 = $data['password2'] ?? '';
        if ($name === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Name required']); exit(); }
        if ($password !== '' && $password !== $password2) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Passwords mismatch']); exit(); }

        if ($password === '') {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $user_id]);
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $hash, $user_id]);
        }
        $_SESSION['name'] = $name;
        echo json_encode(['success'=>true]);
        exit();
    } elseif ($action === 'delete') {
        // delete account and everything related via FK cascade
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        // destroy session
        $_SESSION = []; session_destroy();
        echo json_encode(['success'=>true]);
        exit();
    } else {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
