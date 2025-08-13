<?php
require_once "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Not authorized']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = $_POST ?: []; }
$id = isset($data['id']) ? (int)$data['id'] : 0;
$duration = isset($data['duration']) && $data['duration'] !== '' ? (int)$data['duration'] : null;
$notes = trim($data['notes'] ?? '');
$sets = $data['sets'] ?? null; // array of {reps, weight}

if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

try {
  $pdo->beginTransaction();
  $fetch = $pdo->prepare("SELECT * FROM workout WHERE id=? AND user_id=? FOR UPDATE");
  $fetch->execute([$id, $user_id]);
  $row = $fetch->fetch();
  if (!$row) { $pdo->rollBack(); http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

  $sets_json = null;
  if (is_array($sets)) {
    $clean = [];
    foreach ($sets as $s) {
      $clean[] = [ 'reps' => isset($s['reps'])? (int)$s['reps'] : 0, 'weight' => isset($s['weight']) ? trim($s['weight']) : '' ];
    }
    $sets_json = json_encode($clean);
  }

  // Update current workout with latest sets/duration/notes
  $upd = $pdo->prepare("UPDATE workout SET sets = ?, duration = ?, notes = ? WHERE id=? AND user_id=?");
  $upd->execute([$sets_json, $duration, $notes !== '' ? $notes : null, $id, $user_id]);

  // Re-fetch updated row
  $fetch->execute([$id, $user_id]);
  $row = $fetch->fetch();

  // Insert into history with routine context if columns exist
  $hasCol = function($table, $col) use ($pdo){
    try { $s=$pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?"); $s->execute([$col]); return (bool)$s->fetch(); } catch(Exception $e){ return false; }
  };
  $hasRoutineCols = $hasCol('workout_history','routine_id') && $hasCol('workout_history','routine_day_index') && $hasCol('workout_history','week_start');
  if ($hasRoutineCols) {
    $ins = $pdo->prepare("INSERT INTO workout_history (user_id, title, type, notes, date, duration, sets, routine_id, routine_day_index, week_start) VALUES (?,?,?,?,?,?,?,?,?,?)");
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
  } else {
    $ins = $pdo->prepare("INSERT INTO workout_history (user_id, title, type, notes, date, duration, sets) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([
      $user_id,
      $row['title'],
      $row['type'],
      $row['notes'],
      $row['date'],
      $row['duration'],
      $row['sets'] ?? null
    ]);
  }

  // Delete from active
  $del = $pdo->prepare("DELETE FROM workout WHERE id=? AND user_id=?");
  $del->execute([$id, $user_id]);

  $pdo->commit();

  $ac = $pdo->prepare("SELECT COUNT(*) FROM workout WHERE user_id=?");
  $ac->execute([$user_id]); $active = (int)$ac->fetchColumn();
  $hc = $pdo->prepare("SELECT COUNT(*) FROM workout_history WHERE user_id=?");
  $hc->execute([$user_id]); $history = (int)$hc->fetchColumn();

  echo json_encode(['success'=>true,'active'=>$active,'history'=>$history]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}


