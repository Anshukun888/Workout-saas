<?php
require "../src/db.php";
require "../src/auth.php";

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$name = $_SESSION['name'] ?? '';

if ($user_id <= 0) {
    // Redirect to login if no user ID in session
    header('Location: login.php');
    exit;
}

// fetch user's active workouts
$wstmt = $pdo->prepare("SELECT * FROM workout WHERE user_id = ? ORDER BY created_at DESC");
$wstmt->execute([$user_id]);
$workouts = $wstmt->fetchAll();

// fetch recent history
$hstmt = $pdo->prepare("SELECT * FROM workout_history WHERE user_id = ? ORDER BY completed_at DESC LIMIT 50");
$hstmt->execute([$user_id]);
$history = $hstmt->fetchAll();

// counts
$activeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM workout WHERE user_id = ?");
$activeCountStmt->execute([$user_id]);
$activeCount = (int)$activeCountStmt->fetchColumn();

$historyCountStmt = $pdo->prepare("SELECT COUNT(*) FROM workout_history WHERE user_id = ?");
$historyCountStmt->execute([$user_id]);
$historyCount = (int)$historyCountStmt->fetchColumn();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Dashboard - WorkoutTracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProfile">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <img src="assets/img/logo.svg" alt="MuscleMap" height="36"/>
    </a>
    <div class="collapse navbar-collapse"></div>
    <div class="d-flex align-items-center ms-auto">
      <span class="me-3 text-white">Hi, <?=htmlspecialchars($name)?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Offcanvas profile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasProfile">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Account</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <p><strong>User ID:</strong> <?=htmlspecialchars($user_id)?></p>
    <form id="profileForm">
      <div class="mb-2">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" value="<?=htmlspecialchars($name)?>">
      </div>
      <div class="mb-2">
        <label class="form-label">New password</label>
        <input name="password" type="password" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input name="password2" type="password" class="form-control">
      </div>
      <div class="d-grid gap-2">
        <button type="button" id="saveProfileBtn" class="btn btn-primary">Save changes</button>
        <button type="button" id="deleteAccountBtn" class="btn btn-danger">Delete account</button>
      </div>
    </form>
    <hr>
    <button class="btn btn-outline-secondary w-100 mt-2" data-bs-dismiss="offcanvas">Close</button>
  </div>
</div>

<div class="container my-4">
  <div class="row">
    <div class="col-12 mb-3">
      <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="routine-tab" data-bs-toggle="tab" data-bs-target="#routine" type="button" role="tab" aria-controls="routine" aria-selected="true">Routine</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button" role="tab" aria-controls="log" aria-selected="false">Log</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="track-tab" data-bs-toggle="tab" data-bs-target="#track" type="button" role="tab" aria-controls="track" aria-selected="false">Track</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">History</button>
        </li>
      </ul>
    </div>

    <div class="col-12">
      <div class="tab-content">
        <!-- ROUTINE TAB (active) -->
        <div class="tab-pane fade show active" id="routine" role="tabpanel" aria-labelledby="routine-tab">
          <div class="row g-3">
            <div class="col-lg-4">
              <div class="card p-3 shadow-sm">
                <h5>Routine Settings</h5>
                <div class="mb-2">
                  <label class="form-label">Routine name</label>
                  <input id="routineName" class="form-control" placeholder="My Routine">
                </div>
                <div class="mb-2">
                  <label class="form-label">Today</label>
                  <select id="todayRoutineSelect" class="form-select"></select>
                </div>
                <div class="mb-2">
                  <label class="form-label">Days per week</label>
                  <select id="routineDays" class="form-select">
                    <option value="3">3 days</option>
                    <option value="4">4 days</option>
                    <option value="5">5 days</option>
                    <option value="6">6 days</option>
                    <option value="7">7 days</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Start weekday</label>
                  <select id="routineStart" class="form-select">
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="7">Sunday</option>
                  </select>
                </div>
                <div class="d-grid gap-2">
                  <button class="btn btn-primary" id="saveRoutineBtn">Save Routine</button>
                </div>
              </div>
              
            </div>
            <div class="col-lg-8">
              <div class="card p-3 shadow-sm">
                <h5>Routine Builder</h5>
                <div class="text-muted small mb-2">Click a day to select it for logging.</div>
                <div id="routineBuilder" class="routine-grid"></div>
                <div class="d-flex justify-content-end mt-2">
                  <button class="btn btn-outline-success" id="startDayBtn2">Start Selected Day</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- LOG TAB (sets/reps/weight) -->
        <div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
          <div class="card p-3 shadow-sm">
            <h6>Log Active Workouts</h6>
            <div id="logList">
              <?php if (empty($workouts)): ?>
                <div class="text-muted">No active workouts to log.</div>
              <?php else: ?>
                <div class="list-group">
                  <?php foreach ($workouts as $w): ?>
                    <div class="list-group-item" id="log-<?= $w['id'] ?>" data-rdi="<?= isset($w['routine_day_index']) ? intval($w['routine_day_index']) : 0 ?>" data-sets='<?= htmlspecialchars($w['sets'] ?? "", ENT_QUOTES) ?>'>
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 pe-3">
                          <strong><?=htmlspecialchars($w['title'])?> <span class="badge bg-secondary"><?=htmlspecialchars($w['type'])?></span></strong>
                          <div class="small text-muted">Scheduled: <?=htmlspecialchars($w['date'])?></div>
                        </div>
                      </div>
                      <div class="mt-2 border rounded p-2">
                        <div class="row g-2 align-items-end">
                          <div class="col-12 col-md-3">
                            <label class="form-label small">Duration (min)</label>
                            <input type="number" class="form-control form-control-sm" id="dur-<?= $w['id'] ?>" min="0" value="<?= intval($w['duration']) ?>">
                          </div>
                          <div class="col-12 col-md-9">
                            <label class="form-label small">Notes</label>
                            <input type="text" class="form-control form-control-sm" id="notes-<?= $w['id'] ?>" value="<?= htmlspecialchars($w['notes']) ?>">
                          </div>
                        </div>
                        <div class="mt-2">
                          <div id="setsContainer-<?= $w['id'] ?>" class="sets-container"></div>
                          <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSetRowFor(<?= $w['id'] ?>)">Add Set</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSetsFor(<?= $w['id'] ?>)">Clear</button>
                          </div>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                          <button class="btn btn-sm btn-success" onclick="completeWorkout(<?= $w['id'] ?>)">Save & Complete</button>
                          <button class="btn btn-sm btn-outline-danger" onclick="removeWorkout(<?= $w['id'] ?>)">Remove</button>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- TRACK TAB: mark complete or remove only -->
        <div class="tab-pane fade" id="track" role="tabpanel" aria-labelledby="track-tab">
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card p-3 shadow-sm">
                <h6>Active Workouts</h6>
                <div id="workoutList">
                  <?php if (empty($workouts)): ?>
                    <div class="text-muted">No active workouts.</div>
                  <?php else: ?>
                    <div class="list-group">
                  <?php foreach ($workouts as $w): ?>
                    <div class="list-group-item" id="workout-<?= $w['id'] ?>" data-rdi="<?= isset($w['routine_day_index']) ? intval($w['routine_day_index']) : 0 ?>">
                          <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 pe-3">
                              <strong><?=htmlspecialchars($w['title'])?> <span class="badge bg-secondary"><?=htmlspecialchars($w['type'])?></span></strong>
                              <div class="small text-muted">Scheduled: <?=htmlspecialchars($w['date'])?></div>
                            </div>
                            <div class="text-end small text-muted"></div>
                          </div>
                          <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-success" onclick="completeWorkout(<?= $w['id'] ?>)">Complete</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeWorkout(<?= $w['id'] ?>)">Remove</button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card p-3 shadow-sm">
                <h6>Muscle heatmap</h6>
                <p class="text-muted small">Darker red = worked more often</p>
                <div id="muscleHeatmapWrapper" class="d-flex justify-content-center gap-4" style="max-width: 400px; margin: 0 auto;">
  <!-- Front View SVG -->
  <div class="text-center">
    <h6>Front</h6>
    <svg id="muscleMapFront" viewBox="0 0 180 520" width="180" role="img" aria-label="Muscle heatmap front" xmlns="http://www.w3.org/2000/svg" style="shape-rendering: geometricPrecision;">
      <!-- Head -->
      <circle id="Head" cx="90" cy="40" r="35" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Front Neck / Traps -->
      <rect id="Neck_Front" x="70" y="70" width="40" height="30" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="8" ry="8"/>
      <!-- Shoulders (Front Delts) -->
      <path id="Shoulders_Front" d="M30 90 Q90 120 150 90 Q140 130 130 150 Q90 170 50 150 Q40 130 30 90 Z" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Chest -->
      <ellipse id="Chest" cx="90" cy="160" rx="55" ry="45" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Biceps (Front Arms) -->
      <ellipse id="Biceps_Front_L" cx="20" cy="130" rx="18" ry="38" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <ellipse id="Biceps_Front_R" cx="160" cy="130" rx="18" ry="38" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Forearms -->
      <rect id="Forearms_Front_L" x="10" y="170" width="25" height="90" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="10" ry="10"/>
      <rect id="Forearms_Front_R" x="145" y="170" width="25" height="90" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="10" ry="10"/>
      <!-- Abs / Obliques -->
      <rect id="Abs" x="45" y="210" width="90" height="120" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="20" ry="20"/>
      <!-- Quads -->
      <rect id="Quads_L" x="40" y="340" width="40" height="130" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="20" ry="20"/>
      <rect id="Quads_R" x="100" y="340" width="40" height="130" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="20" ry="20"/>
    </svg>
  </div>

  <!-- Back View SVG -->
  <div class="text-center">
    <h6>Back</h6>
    <svg id="muscleMapBack" viewBox="0 0 180 520" width="180" role="img" aria-label="Muscle heatmap back" xmlns="http://www.w3.org/2000/svg" style="shape-rendering: geometricPrecision;">
      <!-- Head Back -->
      <circle id="Head_Back" cx="90" cy="40" r="35" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Back Neck / Traps -->
      <rect id="Neck_Back" x="70" y="70" width="40" height="30" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="8" ry="8"/>
      <!-- Shoulders -->
      <path id="Shoulders_Back" d="M30 90 Q90 120 150 90 Q140 130 130 150 Q90 170 50 150 Q40 130 30 90 Z" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Full Back -->
      <ellipse id="Back" cx="90" cy="170" rx="55" ry="90" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Glutes -->
      <path id="Glutes" fill="#f2d6b3" stroke="#444" stroke-width="2" d="
        M35,230
        Q90,280 145,230
        L145,300
        Q90,340 35,300
        Z
      "/>
      <!-- Hamstrings -->
      <rect id="Hamstrings_L" x="40" y="300" width="40" height="110" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="20" ry="20"/>
      <rect id="Hamstrings_R" x="100" y="300" width="40" height="110" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="20" ry="20"/>
      <!-- Calves -->
      <rect id="Calves_L" x="40" y="410" width="40" height="80" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="15" ry="15"/>
      <rect id="Calves_R" x="100" y="410" width="40" height="80" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="15" ry="15"/>
      <!-- Triceps -->
      <ellipse id="Triceps_Back_L" cx="20" cy="130" rx="18" ry="38" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <ellipse id="Triceps_Back_R" cx="160" cy="130" rx="18" ry="38" fill="#f2d6b3" stroke="#444" stroke-width="2"/>
      <!-- Forearms -->
      <rect id="Forearms_Back_L" x="10" y="170" width="25" height="90" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="10" ry="10"/>
      <rect id="Forearms_Back_R" x="145" y="170" width="25" height="90" fill="#f2d6b3" stroke="#444" stroke-width="2" rx="10" ry="10"/>
    </svg>
  </div>
</div>


                <div class="mt-2 d-flex align-items-center justify-content-between gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Range:</small>
                    <select id="heatmapRange" class="form-select form-select-sm">
                      <option value="1">Today</option>
                      <option value="7">Last 7 days</option>
                      <option value="30" selected>Last 30 days</option>
                      <option value="365">All time</option>
                    </select>
                  </div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>

        


        <!-- HISTORY TAB -->
        <div class="tab-pane fade" id="history">
          <div class="row g-3">
            <div class="col-lg-4">
              <div class="card p-3 shadow-sm mb-3">
                <h6>Summary</h6>
                <div>Completed total: <strong id="historyTotal"><?= $historyCount ?></strong></div>
                <div class="mt-2">
                  <label class="form-label">Time range for chart</label>
                  <select id="rangeSelect" class="form-select">
                    <option value="day">Today</option>
                    <option value="week" selected>This Week</option>
                    <option value="month">This Month</option>
                    <option value="365">All Time</option>
                  </select>
                </div>
              </div>

              <div class="card p-3 shadow-sm">
                <h6>Counts by type (selected range)</h6>
                <div id="typeCounts" class="d-flex flex-column gap-2"></div>
              </div>
            </div>

         <div class="card p-3 shadow-sm" style="max-width: 600px; margin: 0 auto;">
  <h6>Visual</h6>
  <canvas id="historyChart" height="180" style="width: 100%; height: 380px;"></canvas>
</div>


              <div class="card p-3 mt-3 shadow-sm">
                <h6>Recent History</h6>
                <?php if (empty($history)): ?>
                  <div class="text-muted">No history yet.</div>
                <?php else: ?>
                  <div class="list-group">
                    <?php foreach ($history as $h): ?>
                      <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                          <div>
                            <strong><?=htmlspecialchars($h['title'])?></strong>
                            <div class="small text-muted"><?=htmlspecialchars($h['type'])?> — <?=htmlspecialchars($h['notes'])?></div>
                            <?php
                              $sets_display = '';
                              if (!empty($h['sets'])) {
                                $sets = json_decode($h['sets'], true);
                                if (is_array($sets)) {
                                  $parts = [];
                                  foreach ($sets as $i=>$s) {
                                    $parts[] = 'S'.($i+1).':'.intval($s['reps']).'r×'.htmlspecialchars($s['weight']);
                                  }
                                  $sets_display = implode(' | ', $parts);
                                }
                              }
                            ?>
                            <?php if ($sets_display): ?>
                              <div class="small text-muted mt-1">Sets: <?=$sets_display?></div>
                            <?php endif; ?>
                          </div>
                          <div class="text-end small text-muted"><?=htmlspecialchars($h['completed_at'])?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>

      </div> <!-- tab-content -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/app.js"></script>
<script>
 window.workouts = [
  // Neck & Upper Traps
  "Neck Curl",
  "Neck Flexion",
  "Neck Extension",
  "Neck Lateral Flexion",
  "Neck Rotation",
  "Isometric Neck Hold",
  "Neck Harness Extension",
  "Neck Harness Flexion",
  "Shrugs with Dumbbells",
  "Barbell Shrugs",
  "Cable Shrugs",
  "Upright Row",
  "Farmer’s Walk with Shrugs",
  "Kettlebell Shrugs",

  // Shoulders
  "Overhead Press",
  "Dumbbell Shoulder Press",
  "Barbell Shoulder Press",
  "Arnold Press",
  "Lateral Raise",
  "Front Raise",
  "Rear Delt Fly",
  "Cable Face Pull",
  "Cuban Press",
  "Snatch Grip High Pull",
  "Handstand Push-ups",
  "Pike Push-ups",
  "Z-Press",
  "Dumbbell Reverse Fly",
  "Plate Raises",
  "Kettlebell Overhead Press",
  "Landmine Press",
  "Single-arm Dumbbell Press",
  "Cable Lateral Raise",
  "Incline Lateral Raise",
  "Reverse Pec Deck Fly",
  "Dumbbell Cuban Rotation",
  "Landmine 180 Press",
  "Dumbbell Y-Press",
  "Kettlebell Bottoms-Up Press",
  "Shoulder Tap Push-ups",
  "Wall Slides",

  // Chest
  "Push-ups",
  "Incline Push-ups",
  "Decline Push-ups",
  "Bench Press",
  "Incline Bench Press",
  "Decline Bench Press",
  "Dumbbell Press",
  "Chest Fly (Dumbbells)",
  "Cable Crossover",
  "Pec Deck Machine",
  "Dumbbell Pullover",
  "Close-Grip Bench Press",
  "Diamond Push-ups",
  "Medicine Ball Push-ups",
  "Floor Press",
  "Isometric Chest Squeeze",
  "Floor Dumbbell Fly",
  "Isometric Chest Squeeze with Ball",
  "Plyometric Push-ups",
  "Medicine Ball Chest Pass",
  "Cable Low to High Crossover",
  "Single-arm Dumbbell Press on Stability Ball",
  "Decline Dumbbell Press",
  "Chest Dips",
  "Standing Cable Chest Press",
  "Resistance Band Chest Fly",
  "Incline Dumbbell Press",

  // Back (Upper & Mid)
  "Pull-ups",
  "Chin-ups",
  "Inverted Row",
  "Bent Over Row (Barbell)",
  "Dumbbell Row",
  "T-Bar Row",
  "Cable Row",
  "Lat Pulldown",
  "Face Pulls",
  "Straight Arm Pulldown",
  "Renegade Row",
  "Meadows Row",
  "Seal Row",
  "Kettlebell Row",
  "Back Extension",
  "Scapular Pull-ups",
  "Single-arm Cable Row",
  "Resistance Band Pull-apart",
  "Lat Pullover with Dumbbell",
  "Kettlebell Renegade Row",
  "Cable High Row",
  "Standing T-Bar Row",
  "Meadows Row (Barbell)",
  "Chest Supported Row",
  "Wide-grip Pull-up",
  "Close-grip Pulldown",
  "Deadlift to Shrug",
  "Reverse Grip Bent Over Row",
  "Single-arm Inverted Row",
  "TRX Row",
  "Scapular Retraction Holds",
  "Deadlift with Dumbbells",

  // Lower Back
  "Deadlift (Conventional)",
  "Romanian Deadlift",
  "Sumo Deadlift",
  "Good Mornings",
  "Hyperextensions",
  "Kettlebell Swings",
  "Rack Pulls",
  "Single-Leg Deadlift",
  "Back Extensions on Stability Ball",
  "Suitcase Deadlift",
  "Kettlebell Deadlift",
  "Single-leg Back Extension",
  "Stability Ball Back Extension",
  "Glute Ham Raises",
  "Bird Dog",
  "Supermans",
  "Floor Bridges",

  // Biceps
  "Barbell Curl",
  "Dumbbell Curl",
  "Hammer Curl",
  "Concentration Curl",
  "Preacher Curl",
  "Zottman Curl",
  "Cable Curl",
  "Incline Dumbbell Curl",
  "Spider Curl",
  "Chin-up Hold",
  "Resistance Band Curl",
  "Cross-body Hammer Curl",
  "21s (Curl variations)",
  "Cross-body Dumbbell Curl",
  "Cable Rope Hammer Curl",
  "Dumbbell Drag Curl",
  "Barbell Reverse Curl",
  "Zottman Curl with Dumbbells",
  "Resistance Band Curl with Hold",
  "Incline Inner-biceps Curl",
  "Spider Curl on Incline Bench",
  "Concentration Curl with Pause",
  "Preacher Curl with EZ Bar",

  // Triceps
  "Tricep Dips",
  "Tricep Pushdown",
  "Overhead Tricep Extension",
  "Skull Crushers",
  "Close-Grip Bench Press",
  "Kickbacks",
  "Cable Tricep Extension",
  "One-Arm Overhead Extension",
  "Bench Dips",
  "Diamond Push-ups",
  "Dumbbell Floor Press",
  "Rope Pushdown",
  "Dumbbell Tate Press",
  "One-arm Cable Overhead Extension",
  "Close-Grip Push-ups",
  "Dumbbell Kickbacks with Twist",
  "EZ Bar Skull Crushers",
  "Dumbbell Tate Press",
  "Resistance Band Tricep Pushdown",
  "Dips on Parallel Bars",
  "Overhead Rope Extension",
  "Dumbbell Overhead Extension",
  "Bodyweight Tricep Extension (inverted push-up)",

  // Forearms & Grip
  "Wrist Curls",
  "Reverse Wrist Curls",
  "Farmer’s Walk",
  "Plate Pinch",
  "Towel Pull-ups",
  "Dead Hangs",
  "Rice Bucket Training",
  "Fingertip Push-ups",
  "Wrist Roller Exercise",
  "Hammer Curls",
  "Kettlebell Holds",
  "Plate Wrist Rotations",
  "Reverse Dumbbell Curl",
  "Fingertip Dead Hang",
  "Towel Grip Rows",
  "Rice Finger Pinches",
  "Fat Grip Farmer’s Carry",
  "Wrist Roller with Weight",
  "Plate Pinch Walk",
  "Kettlebell Finger Holds",
  "Wrist Flexion Stretch",

  // Core (Abs & Obliques)
  "Crunches",
  "Sit-ups",
  "Plank",
  "Side Plank",
  "Russian Twist",
  "Leg Raises",
  "Hanging Leg Raises",
  "Bicycle Crunches",
  "Toe Touches",
  "Flutter Kicks",
  "V-Ups",
  "Ab Wheel Rollout",
  "Mountain Climbers",
  "Cable Woodchopper",
  "Medicine Ball Slams",
  "Side Bends",
  "Dead Bug",
  "Windshield Wipers",
  "L-Sit Hold",
  "Hanging Knee Tucks",
  "Dragon Flag",
  "Hanging Windshield Wipers",
  "Cable Russian Twist",
  "Weighted Plank",
  "Medicine Ball Russian Twist",
  "Stability Ball Pike",
  "TRX Mountain Climbers",
  "Side Plank with Leg Raise",
  "Lying Leg Circles",
  "Hollow Body Hold",
  "Hanging Oblique Knee Raises",
  "V-Sit Hold",
  "Cable Anti-Rotation Press",
  "Plank to Push-up",
  "Spiderman Plank",
  "Plank Jacks",

  // Quadriceps
  "Squats (Back)",
  "Front Squat",
  "Bulgarian Split Squat",
  "Lunges (Forward)",
  "Walking Lunges",
  "Reverse Lunges",
  "Step-ups",
  "Leg Press",
  "Leg Extension",
  "Wall Sit",
  "Jump Squats",
  "Box Jumps",
  "Pistol Squat",
  "Sled Push",
  "Hack Squat Machine",
  "Goblet Squat",
  "Step-back Lunges",
  "Jumping Lunges",
  "Curtsy Lunges",
  "Kettlebell Goblet Squat",
  "Sled Drag",
  "Barbell Walking Lunges",
  "Box Step-down",
  "Reverse Nordic Curl",
  "Wall Ball Squats",
  "Dumbbell Split Squat",
  "Front Rack Squat",
  "Step-up to Reverse Lunge",
  "Jumping Step-ups",
  "Sled Sprints",
  "Band Resisted Squats",

  // Hamstrings
  "Romanian Deadlift",
  "Good Mornings",
  "Leg Curl Machine (Seated)",
  "Leg Curl Machine (Lying)",
  "Glute-Ham Raises",
  "Single-Leg Deadlift",
  "Kettlebell Swings",
  "Nordic Hamstring Curl",
  "Elevated Glute Bridge",
  "Cable Pull-Through",
  "Dumbbell Glute Kickback",
  "Frog Pumps with Resistance Band",
  "Glute Bridge March",
  "Side-lying Hip Abduction",
  "Banded Clamshell",
  "Cable Glute Kickback",
  "Barbell Hip Thrust",
  "Sled Backward Drag",

  // Glutes
  "Hip Thrust",
  "Glute Bridge",
  "Cable Kickbacks",
  "Step-ups",
  "Bulgarian Split Squat",
  "Sumo Deadlift",
  "Kettlebell Swing",
  "Frog Pumps",
  "Clamshells",
  "Fire Hydrants",
  "Donkey Kicks",
  "Lateral Band Walks",

  // Calves
  "Standing Calf Raises",
  "Seated Calf Raises",
  "Donkey Calf Raises",
  "Jump Rope",
  "Box Jumps",
  "Sprinting",
  "Farmer’s Walk on Toes",
  "Single-leg Calf Raise",
  "Jump Rope Double Unders",
  "Farmer’s Walk on Toes",
  "Box Jump Calf Focus",
  "Seated Dumbbell Calf Raise",
  "Explosive Calf Jumps",
  "Jump Squat Calf Raise",
  "Weighted Standing Calf Raise",
  "Donkey Calf Raise on Machine",

  // Cardio & Conditioning
  "Running (Steady State)",
  "Sprinting",
  "Jogging",
  "Jump Rope",
  "Stair Climber",
  "Rowing Machine",
  "Battle Ropes",
  "Medicine Ball Slams",
  "Agility Ladder Drills",
  "Cone Drills",
  "Sandbag Carry",
  "Tire Flip",
  "Bear Hug Carry",
  "Shadowboxing",
  "Swimming",
  "Cycling",
  "Elliptical Machine",
  "Burpees",
  "Mountain Climbers",
  "Box Jumps",
  "Hill Sprints",
  "Shuttle Runs",
  "Sled Push Sprint",
  "Jump Rope Intervals",
  "Tabata Burpees",
  "Battle Rope Waves",
  "Medicine Ball Chest Pass",
  "Agility Ladder Quick Feet",
  "Box Drill Sprints",
  "Rowing Intervals",
  "Swimming Sprints",
  "Kettlebell Complex",
  "Shadowboxing with Weights",
  "Jump Rope Criss-Cross",
  "Sprint Intervals on Treadmill",

  // Gymnastics & Bodyweight Strength
  "Muscle-ups",
  "Front Lever",
  "Back Lever",
  "Planche",
  "Human Flag",
  "Wall Walk",
  "Handstand Push-ups",
  "L-Sit",
  "Wall Sit",
  "Jump Tuck",
  "Skin the Cat",
  "Ring Rows",
  "Ring Dips",
  "Rope Climbing",
  "Ring Muscle-ups",
  "Assisted Front Lever",
  "Advanced Planche Progressions",
  "Wall Handstand Hold",
  "Skin the Cat on Rings",
  "One-arm Push-up",
  "L-Sit to Handstand",
  "Back Lever Progression",
  "Human Flag Holds",
  "Handstand Walk",
  "Ring Support Hold",
  "Planche Lean",

  // Yoga & Mobility
  "Yoga Sun Salutation",
  "Downward Dog",
  "Cat-Cow Stretch",
  "Cobra Stretch",
  "Pigeon Pose",
  "Child’s Pose",
  "Hip Flexor Stretch",
  "Hamstring Stretch",
  "Quad Stretch",
  "Shoulder Dislocates",
  "Thoracic Spine Rotations",
  "Wrist Mobility Drills",
  "Standing Forward Fold",
  "Half Pigeon Pose",
  "Downward Dog to Cobra Flow",
  "Seated Spinal Twist",
  "Shoulder Opener with Strap",
  "Hip Circles",
  "Cat-Cow with Breath",
  "Dynamic Hamstring Stretch",
  "Ankle Mobility Drills",
  "Thoracic Bridge",
  "Foam Rolling Quads",
  "Foam Rolling IT Band",
  "Foam Rolling Calves",
  "Foam Rolling Glutes",
  "Wrist Extension Stretch",
  "Prone Thoracic Rotation",

  // More Functional & Complex Exercises
  "CrossFit Wall Balls",
  "Dumbbell Snatch",
  "Barbell Clean and Jerk",
  "Barbell Power Clean",
  "Barbell Push Press",
  "Sandbag Get-up",
  "Tire Drag",
  "Kettlebell Clean",
  "Kettlebell Snatch",
  "Turkish Get-up",
  "Dumbbell Thrusters",
  "Weighted Vest Push-ups",
  "Resistance Band Sprint",
  "Sled Drag with Harness",
  "Box Jumps with Weighted Vest",
  "Dumbbell Step-over",
  "Dumbbell Pullover",
  "Stability Ball Leg Curl",
  "TRX Hamstring Curl",
  "Band-resisted Side Steps",
  "Stability Ball Rollout",
  "Plank with Arm Reach",
  "Weighted Side Plank",
  "Bird Dog with Dumbbell",
  "Single-arm Farmer’s Carry",
  "Farmer’s Walk with Plate",
  "Kettlebell Farmer’s Carry",
  "Bear Crawl with Weight",
  "Crab Walk",
  "Donkey Kick with Resistance Band",
  "Frog Jumps",
  "Jump Lunges",
  "Medicine Ball Overhead Throw",
  "Box Step-ups with Dumbbells",
  "Cable Hip Abduction",
  "Cable Hip Adduction",
  "Glute Kickback on Cable",
  "Kettlebell Suitcase Deadlift",
  "Sandbag Bear Hug Carry",
  "Medicine Ball Side Throw",
  "Jump Rope Single Leg",
  "Agility Cone Drill with Ball",
  "Plyometric Push-ups on Medicine Ball",
  "TRX Chest Press",
  "Resistance Band Chest Fly",
  "Dumbbell Floor Press",
  "Dumbbell Chest Squeeze",
  "Cable Reverse Fly",
  "Kettlebell Halo",
  "Dumbbell Hammer Curl",
  "Barbell Curl 21s",
  "Cable Rope Overhead Extension",
  "Dumbbell Concentration Curl",
  "Rope Climb",
  "Weighted Dips",
  "Ring Support Hold",
  "Handstand Shoulder Taps",
  "Wall Walks",
  "Muscle-up Progressions",
  "L-Sit to Tuck Planche",
  "Hanging Leg Raise with Twist",
  "Weighted Plank",
  "Box Jump with Lateral Step",
  "Stability Ball Wall Squat",
  "Jump Tucks",
  "Reverse Hyperextension",
  "Side Lunge",
  "Step-up with Knee Raise",
  "Dumbbell Front Raise",
  "Dumbbell Lateral Raise with Hold",
  "Cable Upright Row",
  "Barbell High Pull",
  "Kettlebell Figure 8",
  "Dumbbell Renegade Row",
  "Sled Drag with Backward Walk",
  "Medicine Ball Slam with Jump",
  "Bear Crawl Drag",
  "Agility Ladder Icky Shuffle",
  "Sled Push Sprint",
  "Sprint Starts",
  "Broad Jumps",
  "Dumbbell Thrusters",
  "Kettlebell Dead Clean",
  "Sandbag Get-up",
  "Wall Ball Shots",
  "Rope Waves",
  "TRX Atomic Push-ups",
  "Resistance Band Good Morning",
  "Cable Hip Thrust",
  "Band-resisted Glute Bridge",
  "Kettlebell Swing with Pause",
  "Jump Rope Double Unders",
  "Weighted Step-ups",
  "Bulgarian Split Squat Jumps",
  "Dumbbell Snatch",
  "Barbell Snatch",
  "Kettlebell Overhead Carry",
  "TRX Chest Fly",
  "Box Jump Burpees",
  "Barbell Front Raise",
  "Dumbbell Rear Delt Row",
  "Cable Rear Delt Fly",
  "Plank Walkouts",
  "Spider Crawl",
  "Lunge with Rotation",
  "Medicine Ball Chest Pass",
  "Agility Ladder Side Shuffle",
  "Battle Rope Slams",
  "Bear Hug Carry",
  "Tire Flip",
  "Jump Rope Criss-cross",
  "Sprint Intervals",
  "Shadowboxing with Weights"
];

      function searchSuggestions() {
    const input = document.getElementById('searchWorkout').value.toLowerCase();
    const suggestionBox = document.getElementById('suggestions');
    suggestionBox.innerHTML = '';
    if (!input.length) return;
    const filtered = workouts.filter(w => w.toLowerCase().includes(input));
    filtered.forEach(w => {
      const div = document.createElement('div');
      div.textContent = w;
      div.style.padding = '5px 10px';
      div.style.cursor = 'pointer';
      div.onmouseover = () => div.style.backgroundColor = '#eee';
      div.onmouseout = () => div.style.backgroundColor = 'white';
      div.onclick = () => {
       document.getElementById('titleInput').value = w;
        document.getElementById('searchWorkout').value = '';
        suggestionBox.innerHTML = '';
      };
      suggestionBox.appendChild(div);
    });
  }

  </script>
</body>
</html>
