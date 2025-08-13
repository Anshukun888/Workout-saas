<?php
// public/api/routine_get.php
require_once "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'Not authorized']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
  // Find latest routine for the user
  $r = $pdo->prepare("SELECT * FROM routine WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
  $r->execute([$user_id]);
  $routine = $r->fetch();

  if (!$routine) {
    // Provide a sensible default scaffold: 7 days with PPL then rest/blank
    echo json_encode([
      'success'=>true,
      'routine'=>[
        'id'=>null,
        'name'=>'My Routine',
        'days_per_week'=>7,
        'pattern_length'=>3,
        'start_weekday'=>1,
        'days'=>[
          ['day_index'=>1,'name'=>'Push','exercises'=>[]],
          ['day_index'=>2,'name'=>'Pull','exercises'=>[]],
          ['day_index'=>3,'name'=>'Legs','exercises'=>[]],
          ['day_index'=>4,'name'=>'Day 4 (Rest)','exercises'=>[]],
          ['day_index'=>5,'name'=>'Day 5 (Rest)','exercises'=>[]],
          ['day_index'=>6,'name'=>'Day 6 (Rest)','exercises'=>[]],
          ['day_index'=>7,'name'=>'Day 7 (Rest)','exercises'=>[]],
        ]
      ]
    ]);
    exit;
  }

  $routine_id = (int)$routine['id'];
  $dstmt = $pdo->prepare("SELECT * FROM routine_day WHERE routine_id = ? ORDER BY day_index ASC");
  $dstmt->execute([$routine_id]);
  $days = $dstmt->fetchAll();

  $outDays = [];
  foreach ($days as $d) {
    $ed = $pdo->prepare("SELECT id,title,type,default_sets,notes,sort_order FROM routine_exercise WHERE routine_day_id = ? ORDER BY sort_order ASC, id ASC");
    $ed->execute([$d['id']]);
    $ex = $ed->fetchAll();
    $exercises = [];
    foreach ($ex as $e) {
      $exercises[] = [
        'id'=>(int)$e['id'],
        'title'=>$e['title'],
        'type'=>$e['type'],
        'default_sets'=>$e['default_sets'] ? json_decode($e['default_sets'], true) : null,
        'notes'=>$e['notes'],
        'sort_order'=>(int)$e['sort_order']
      ];
    }
    $outDays[] = [
      'day_index'=>(int)$d['day_index'],
      'name'=>$d['name'],
      'exercises'=>$exercises
    ];
  }

  // also compute today's effective day index per pattern
  $pattern_length = isset($routine['pattern_length']) ? (int)$routine['pattern_length'] : (int)$routine['days_per_week'];
  $start_weekday = (int)$routine['start_weekday']; // 1..7
  $today = (int)date('N'); // 1..7
  $delta = ($today - $start_weekday + 7) % 7; // 0..6
  $effective_day_index = ($pattern_length > 0) ? (($delta % $pattern_length) + 1) : 1;

  echo json_encode(['success'=>true,'routine'=>[
    'id'=>$routine_id,
    'name'=>$routine['name'],
    'days_per_week'=>(int)$routine['days_per_week'],
    'pattern_length'=> isset($routine['pattern_length']) ? (int)$routine['pattern_length'] : null,
    'start_weekday'=>(int)$routine['start_weekday'],
    'days'=>$outDays,
    'today_index'=>$effective_day_index
  ]]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Server error']);
}


