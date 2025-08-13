<?php
// public/api/move_workout.php
require "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$id = isset($data['id']) ? intval($data['id']) : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid id']);
    exit;
}

try {
    $pdo->beginTransaction();

    $fetch = $pdo->prepare("SELECT * FROM workout WHERE id = ? AND user_id = ? FOR UPDATE");
    $fetch->execute([$id, $user_id]);
    $row = $fetch->fetch();
    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Workout not found']);
        exit;
    }

    // insert into history (include sets and routine context if present)
    $ins = $pdo->prepare("INSERT INTO workout_history (user_id, title, type, notes, date, duration, sets, routine_id, routine_day_index, week_start) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([
        $user_id,
        $row['title'],
        $row['type'],
        $row['notes'],
        $row['date'],
        $row['duration'],
        $row['sets'] ?? null,
        $row['routine_id'] ?? null,
        $row['routine_day_index'] ?? null,
        $row['week_start'] ?? null
    ]);

    // delete original
    $del = $pdo->prepare("DELETE FROM workout WHERE id = ? AND user_id = ?");
    $del->execute([$id, $user_id]);

    $pdo->commit();

    $ac = $pdo->prepare("SELECT COUNT(*) FROM workout WHERE user_id = ?");
    $ac->execute([$user_id]); $active=(int)$ac->fetchColumn();
    $hc = $pdo->prepare("SELECT COUNT(*) FROM workout_history WHERE user_id = ?");
    $hc->execute([$user_id]); $history=(int)$hc->fetchColumn();

    echo json_encode(['success'=>true,'id'=>$id,'active'=>$active,'history'=>$history]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}
