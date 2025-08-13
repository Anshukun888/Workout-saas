<?php
require_once "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
$user_id = (int)$_SESSION['user_id'];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = $_POST ?: []; }

$title = trim($data['title'] ?? '');
$type = trim($data['type'] ?? '');
$notes = trim($data['notes'] ?? '');
$duration = isset($data['duration']) && $data['duration'] !== '' ? (int)$data['duration'] : null;
$sets = $data['sets'] ?? null; // array
$routine_day_index = isset($data['routine_day_index']) ? (int)$data['routine_day_index'] : null;
$routine_id = isset($data['routine_id']) ? (int)$data['routine_id'] : null;
$date = $data['date'] ?? date('Y-m-d');

if ($title === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Title required']); exit; }

// compute week_start using user's latest routine start_weekday if possible
$week_start = null;
try {
  $r = $pdo->prepare("SELECT start_weekday FROM routine WHERE user_id=? ORDER BY updated_at DESC LIMIT 1");
  $r->execute([$user_id]);
  $row = $r->fetch();
  $start_weekday = $row ? (int)$row['start_weekday'] : 1; // 1=Mon
  $todayN = (int)date('N');
  $diff = $start_weekday - $todayN;
  $dt = new DateTime();
  if ($diff > 0) { $diff -= 7; }
  $dt->modify($diff.' day');
  $week_start = $dt->format('Y-m-d');
} catch (Exception $e) { $week_start = null; }

$sets_json = null;
if (is_array($sets)) {
  $clean = [];
  foreach ($sets as $s) {
    $clean[] = [ 'reps' => isset($s['reps'])? (int)$s['reps'] : 0, 'weight' => isset($s['weight']) ? trim($s['weight']) : '' ];
  }
  $sets_json = json_encode($clean);
}

// Insert directly into history
$hasCol = function($table, $col) use ($pdo){
  try { $s=$pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?"); $s->execute([$col]); return (bool)$s->fetch(); } catch(Exception $e){ return false; }
};
$hasRoutineCols = $hasCol('workout_history','routine_id') && $hasCol('workout_history','routine_day_index') && $hasCol('workout_history','week_start');

try {
  if ($hasRoutineCols) {
    $ins = $pdo->prepare("INSERT INTO workout_history (user_id, title, type, notes, date, duration, sets, routine_id, routine_day_index, week_start) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $ins->execute([$user_id, $title, $type ?: null, $notes ?: null, $date, $duration, $sets_json, $routine_id, $routine_day_index, $week_start]);
  } else {
    $ins = $pdo->prepare("INSERT INTO workout_history (user_id, title, type, notes, date, duration, sets) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([$user_id, $title, $type ?: null, $notes ?: null, $date, $duration, $sets_json]);
  }

  // Return updated counters
  $hc = $pdo->prepare("SELECT COUNT(*) FROM workout_history WHERE user_id=?");
  $hc->execute([$user_id]); $history = (int)$hc->fetchColumn();
  echo json_encode(['success'=>true,'history'=>$history]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}


