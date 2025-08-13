<?php
require_once "../src/db.php";
require_once "../src/auth.php";

// Simple CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(400);
    exit('Invalid request');
  }
  $user_id = (int)$_SESSION['user_id'];
  try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
  } catch (Exception $e) {
    http_response_code(500);
    exit('Server error');
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Delete Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="card shadow-sm p-4">
          <h3 class="mb-2">Delete Account</h3>
          <p class="text-muted mb-4">This will permanently remove your account and all data. This action cannot be undone.</p>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
            <div class="d-grid gap-2">
              <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
              <button type="submit" class="btn btn-danger">Delete my account</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


