<?php
require "../src/db.php";
session_start();
if (isset($_SESSION['user_id'])) header("Location: dashboard.php");

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($name === '' || $email === '' || $password === '') $error = "All fields required.";
    if ($password !== $password2) $error = "Passwords do not match.";

    if ($error === '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hash]);
            // Get the newly created user ID
            $userId = $pdo->lastInsertId();
            // Set session variables
            $_SESSION['user_id'] = (int)$userId;
            $_SESSION['name'] = $name;
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $error = "Email already registered.";
            else $error = "Database error.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Sign up - WorkoutTracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    .btn-success {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      border: none;
      border-radius: 8px;
      transition: transform 0.2s;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #0d7a72 0%, #2dd169 100%);
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
      .col-md-6,
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
      .btn-success {
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
    <div class="col-md-6 col-lg-4">
      <div class="card shadow">
        <div class="card-body p-4">
          <h4 class="mb-3 text-center"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
          <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
          <form method="post" novalidate>
            <div class="mb-2">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" id="name" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Email</label>
              <input class="form-control" name="email" type="email" id="email" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Password</label>
              <input class="form-control" name="password" type="password" id="password" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm password</label>
              <input class="form-control" name="password2" type="password" id="password2" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="showPasswordCheck">
              <label class="form-check-label" for="showPasswordCheck">
                Show password
              </label>
            </div>
            <button class="btn btn-success w-100"><i class="fas fa-user-check me-2"></i>Sign Up</button>
            <div class="text-center mt-2"><a href="login.php">Already have account?</a></div>
          </form>
        </div>
      </div>
      <p class="text-muted text-center mt-3 small">After signup use dashboard to add workouts.</p>
    </div>
  </div>
</div>

<script>
  const showPasswordCheckbox = document.getElementById('showPasswordCheck');
  const passwordInput = document.getElementById('password');
  const password2Input = document.getElementById('password2');

  showPasswordCheckbox.addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    passwordInput.type = type;
    password2Input.type = type;
  });
</script>
</body>
</html>
