<?php
require_once "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
$user_id = (int)$_SESSION['user_id'];

$day_index = isset($_POST['day_index']) ? (int)$_POST['day_index'] : 0;
if ($day_index <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid day']); exit; }

try {
  // Find latest routine
  $r = $pdo->prepare("SELECT id, days_per_week, pattern_length, start_weekday FROM routine WHERE user_id=? ORDER BY updated_at DESC LIMIT 1");
  $r->execute([$user_id]);
  $routine = $r->fetch();
  if (!$routine) { echo json_encode(['success'=>false,'message'=>'No routine']); exit; }
  $routine_id = (int)$routine['id'];

  // Collect exercises for the given day_index
  $d = $pdo->prepare("SELECT id, name FROM routine_day WHERE routine_id=? AND day_index=? LIMIT 1");
  $d->execute([$routine_id, $day_index]);
  $day = $d->fetch();
  if (!$day) { echo json_encode(['success'=>false,'message'=>'Day not found']); exit; }
  $rdid = (int)$day['id'];

  $exq = $pdo->prepare("SELECT title, type, default_sets, notes FROM routine_exercise WHERE routine_day_id=? ORDER BY sort_order ASC, id ASC");
  $exq->execute([$rdid]);
  $exercises = $exq->fetchAll();

  $today = date('Y-m-d');
  // Compute week_start based on start_weekday
  $start_weekday = (int)$routine['start_weekday'];
  $todayN = (int)date('N');
  $diff = $start_weekday - $todayN; if ($diff > 0) $diff -= 7; $ws = new DateTime(); $ws->modify($diff.' day');
  $week_start = $ws->format('Y-m-d');

  $created = 0;
  foreach ($exercises as $ex) {
    $title = $ex['title'];
    // Skip if already exists today
    $ck = $pdo->prepare("SELECT id FROM workout WHERE user_id=? AND date=? AND title=? LIMIT 1");
    $ck->execute([$user_id, $today, $title]);
    if ($ck->fetch()) continue;
    $ins = $pdo->prepare("INSERT INTO workout (user_id, title, type, notes, date, duration, sets, routine_id, routine_day_index, week_start) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $ins->execute([$user_id, $title, $ex['type'], $ex['notes'] ?: null, $today, null, $ex['default_sets'], $routine_id, $day_index, $week_start]);
    $created++;
  }

  // counts for UI
  $ac = $pdo->prepare("SELECT COUNT(*) FROM workout WHERE user_id=?");
  $ac->execute([$user_id]);
  $active = (int)$ac->fetchColumn();
  echo json_encode(['success'=>true,'created'=>$created,'active'=>$active]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}


