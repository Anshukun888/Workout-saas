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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="assets/css/styles.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }
    .card {
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      border-radius: 15px;
    }
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 8px;
      transition: transform 0.2s;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
    }
    .form-control {
      border-radius: 8px;
      border: 1px solid #dee2e6;
    }
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    h4 {
      color: #667eea;
      font-weight: 600;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 575.98px) {
      .container {
        padding: 1rem;
      }
      .col-md-5,
      .col-lg-4 {
        padding: 0;
      }
      .card {
        border-radius: 10px;
      }
      .card-body {
        padding: 1.5rem !important;
      }
      h4 {
        font-size: 1.25rem;
      }
      .form-label {
        font-size: 0.9rem;
      }
      .form-control {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
      }
      .btn-primary {
        padding: 0.625rem 1rem;
        font-size: 0.95rem;
      }
      .text-muted.small {
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>
<div class="container d-flex align-items-center vh-100">
  <div class="row w-100 justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow">
        <div class="card-body p-4">
          <h4 class="mb-3 text-center"><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
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
            <button class="btn btn-primary w-100 mb-2"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
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

