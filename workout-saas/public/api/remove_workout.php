<?php
// public/api/remove_workout.php
require "../../src/db.php";
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not auth']); exit(); }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$id = isset($data['id']) ? intval($data['id']) : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }

try {
    $del = $pdo->prepare("DELETE FROM workout WHERE id = ? AND user_id = ?");
    $del->execute([$id, $user_id]);
    if ($del->rowCount() === 0) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Workout not found']); exit(); }

    $ac = $pdo->prepare("SELECT COUNT(*) FROM workout WHERE user_id = ?"); $ac->execute([$user_id]); $active=(int)$ac->fetchColumn();
    echo json_encode(['success'=>true,'id'=>$id,'active'=>$active]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
