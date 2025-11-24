<?php
require "../src/db.php";
session_start();
if (isset($_SESSION['user_id'])) header("Location: dashboard.php");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['login'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($login === '' || $password === '') $error = "Fill both fields.";

  if ($error === '') {
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? OR name = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['name'] = $user['name'];
      header("Location: dashboard.php");
      exit();
    } else {
      $error = "Invalid credentials.";
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Login - WorkoutTracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center vh-100">
  <div class="row w-100 justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow">
        <div class="card-body p-4">
          <h4 class="mb-3 text-center">Login</h4>
          <?php if ($error): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
          <?php endif; ?>
          <form method="post" novalidate>
            <div class="mb-2">
              <label class="form-label">Email or Username</label>
              <input class="form-control" name="login" type="text" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input id="passwordInput" class="form-control" name="password" type="password" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="showPasswordCheck">
              <label class="form-check-label" for="showPasswordCheck">
                Show password
              </label>
            </div>
            <button class="btn btn-primary w-100 mb-2">Login</button>
            <div class="text-center">
              <a href="signup.php">Create an account</a>
            </div>
          </form>
        </div>
      </div>
      <p class="text-muted text-center mt-3 small">Use signup to create a account.</p>
    </div>
  </div>
</div>

<script>
  const showPasswordCheckbox = document.getElementById('showPasswordCheck');
  const passwordInput = document.getElementById('passwordInput');

  showPasswordCheckbox.addEventListener('change', function() {
    if (this.checked) {
      passwordInput.type = 'text';
    } else {
      passwordInput.type = 'password';
    }
  });
</script>
</body>
</html>
