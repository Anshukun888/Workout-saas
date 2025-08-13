<?php
// public/api/routine_save.php
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
$data = json_decode($raw, true) ?: [];

$name = trim($data['name'] ?? 'My Routine');
$days_per_week = intval($data['days_per_week'] ?? 3);
if ($days_per_week < 3) $days_per_week = 3;
if ($days_per_week > 7) $days_per_week = 7;
$pattern_length = isset($data['pattern_length']) ? intval($data['pattern_length']) : null;
if ($pattern_length !== null) {
  if ($pattern_length < 1) $pattern_length = 1;
  if ($pattern_length > $days_per_week) $pattern_length = $days_per_week;
}
$start_weekday = intval($data['start_weekday'] ?? 1);
if ($start_weekday < 1 || $start_weekday > 7) $start_weekday = 1;
$days = $data['days'] ?? [];

try {
  $pdo->beginTransaction();

  $exists = $pdo->prepare("SELECT id FROM routine WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
  $exists->execute([$user_id]);
  $row = $exists->fetch();
  if ($row) {
    $routine_id = (int)$row['id'];
    $upd = $pdo->prepare("UPDATE routine SET name=?, days_per_week=?, pattern_length=?, start_weekday=? WHERE id=? AND user_id=?");
    $upd->execute([$name, $days_per_week, $pattern_length, $start_weekday, $routine_id, $user_id]);
  } else {
    $ins = $pdo->prepare("INSERT INTO routine (user_id, name, days_per_week, pattern_length, start_weekday) VALUES (?,?,?,?,?)");
    $ins->execute([$user_id, $name, $days_per_week, $pattern_length, $start_weekday]);
    $routine_id = (int)$pdo->lastInsertId();
  }

  // Rebuild routine_day and routine_exercise (use compatible two-step deletes)
  $idsStmt = $pdo->prepare("SELECT id FROM routine_day WHERE routine_id = ?");
  $idsStmt->execute([$routine_id]);
  $dayIds = array_map(function($r){ return (int)$r['id']; }, $idsStmt->fetchAll());
  if (!empty($dayIds)) {
    $in = implode(',', array_fill(0, count($dayIds), '?'));
    $delEx = $pdo->prepare("DELETE FROM routine_exercise WHERE routine_day_id IN ($in)");
    $delEx->execute($dayIds);
  }
  $pdo->prepare("DELETE FROM routine_day WHERE routine_id = ?")->execute([$routine_id]);

  foreach ($days as $d) {
    $day_index = intval($d['day_index'] ?? 0);
    if ($day_index < 1 || $day_index > $days_per_week) continue;
    $day_name = trim($d['name'] ?? ('Day '.$day_index));
    $insDay = $pdo->prepare("INSERT INTO routine_day (routine_id, day_index, name) VALUES (?,?,?)");
    $insDay->execute([$routine_id, $day_index, $day_name]);
    $rdid = (int)$pdo->lastInsertId();
    $exs = $d['exercises'] ?? [];
    $sort = 0;
    foreach ($exs as $ex) {
      $title = trim($ex['title'] ?? ''); if ($title === '') continue;
      $type = trim($ex['type'] ?? ''); if ($type === '') $type = null;
      $default_sets = $ex['default_sets'] ?? null;
      $notes = trim($ex['notes'] ?? '');
      $sort++;
      $insEx = $pdo->prepare("INSERT INTO routine_exercise (routine_day_id, title, type, default_sets, notes, sort_order) VALUES (?,?,?,?,?,?)");
      $insEx->execute([$rdid, $title, $type, $default_sets ? json_encode($default_sets) : null, $notes, $sort]);
    }
  }

  $pdo->commit();
  echo json_encode(['success'=>true,'routine_id'=>$routine_id]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}


