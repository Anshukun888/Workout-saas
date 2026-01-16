<?php
require "../src/db.php";
session_start();

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Fitness Journey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .highlight {
            color: #ffd700;
        }
        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }
        .feature-item i {
            font-size: 1.5rem;
            color: #ffd700;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn-hero-primary {
            background: #ffd700;
            color: #333;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            border: none;
            transition: transform 0.2s;
        }
        .btn-hero-primary:hover {
            transform: translateY(-2px);
            background: #ffed4e;
            color: #333;
        }
        .btn-hero-secondary {
            background: transparent;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            border: 2px solid white;
            transition: all 0.2s;
        }
        .btn-hero-secondary:hover {
            background: white;
            color: #667eea;
        }
        .hero-image {
            position: relative;
            height: 400px;
        }
        
        .floating-card {
            position: absolute;
            background: white;
            color: #333;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            animation: float 4s ease-in-out infinite;
            text-align: center;
            min-width: 180px;
        }

        .floating-card.card-1 {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-card.card-2 {
            top: 50%;
            right: 5%;
            animation-delay: 1.3s;
        }

        .floating-card.card-3 {
            bottom: 10%;
            left: 20%;
            animation-delay: 2.6s;
        }
        .card-emoji {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 10px;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .section-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 3rem;
        }
        .feature-step {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
            transition: transform 0.3s;
        }
        .feature-step:hover {
            transform: translateY(-5px);
        }
        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
        }
        .feature-step h4 {
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 991.98px) {
            .hero-section {
                padding: 60px 0;
                min-height: auto;
            }
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 1.1rem;
            }
            .hero-image {
                height: 300px;
                margin-top: 2rem;
            }
            .floating-card {
                min-width: 150px;
                padding: 20px;
            }
            .card-emoji {
                font-size: 2rem;
            }
            .features-section {
                padding: 60px 0;
            }
            .section-title {
                font-size: 2rem;
            }
            .section-subtitle {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-section {
                padding: 40px 0;
            }
            .hero-title {
                font-size: 1.75rem;
                margin-bottom: 1rem;
            }
            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }
            .hero-features {
                gap: 0.75rem;
                margin-bottom: 1.5rem;
            }
            .feature-item {
                font-size: 1rem;
            }
            .feature-item i {
                font-size: 1.25rem;
            }
            .hero-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }
            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                padding: 12px 20px;
            }
            .hero-image {
                height: 250px;
                margin-top: 1.5rem;
            }
            .floating-card {
                min-width: 120px;
                padding: 15px;
                font-size: 0.9rem;
            }
            .card-emoji {
                font-size: 1.75rem;
            }
            .floating-card.card-1 {
                top: 5%;
                left: 2%;
            }
            .floating-card.card-2 {
                top: 45%;
                right: 2%;
            }
            .floating-card.card-3 {
                bottom: 5%;
                left: 15%;
            }
            .features-section {
                padding: 40px 0;
            }
            .section-title {
                font-size: 1.75rem;
            }
            .section-subtitle {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            .feature-step {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            .step-number {
                width: 50px;
                height: 50px;
                font-size: 1.75rem;
                margin-bottom: 1rem;
            }
            .navbar-brand {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 1.5rem;
            }
            .hero-subtitle {
                font-size: 0.95rem;
            }
            .feature-item {
                font-size: 0.95rem;
            }
            .hero-image {
                height: 200px;
            }
            .floating-card {
                min-width: 100px;
                padding: 12px;
                font-size: 0.85rem;
            }
            .card-emoji {
                font-size: 1.5rem;
            }
            .section-title {
                font-size: 1.5rem;
            }
            .section-subtitle {
                font-size: 0.95rem;
            }
            .feature-step {
                padding: 1.25rem;
            }
            .step-number {
                width: 45px;
                height: 45px;
                font-size: 1.5rem;
            }
            .feature-step h4 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo.svg" alt="Logo" height="36" class="me-2">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="login.php">Sign In</a>
                    <a class="nav-link" href="signup.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title">Track Your Fitness Journey 💪</h1>
                        <p class="hero-subtitle">Build custom workout routines, log your exercises, track your progress, and visualize your muscle development with our comprehensive fitness tracking platform.</p>
                        <div class="hero-features">
                            <div class="feature-item">
                                <i class="fas fa-dumbbell"></i>
                                <span>Custom workout routines</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-chart-line"></i>
                                <span>Progress tracking & analytics</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-fire"></i>
                                <span>Muscle heatmap visualization</span>
                            </div>
                        </div>
                        <div class="hero-buttons mt-4">
                            <a href="signup.php" class="btn btn-hero-primary">Get Started Free</a>
                            <a href="login.php" class="btn btn-hero-secondary">Sign In</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <div class="floating-card card-1">
                            <div class="card-emoji">🏋️</div>
                            <p class="mb-0 text-center"><strong>Routine Builder</strong></p>
                        </div>
                        <div class="floating-card card-2">
                            <div class="card-emoji">📊</div>
                            <p class="mb-0 text-center"><strong>Progress Tracking</strong></p>
                        </div>
                        <div class="floating-card card-3">
                            <div class="card-emoji">🔥</div>
                            <p class="mb-0 text-center"><strong>Muscle Heatmap</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtitle">Three simple steps to transform your fitness journey</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-step">
                        <div class="step-number">1</div>
                        <h4>Build Your Routine</h4>
                        <p>Create custom workout routines with multiple days per week. Add exercises, set schedules, and organize your training plan.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-step">
                        <div class="step-number">2</div>
                        <h4>Log Your Workouts</h4>
                        <p>Track sets, reps, weights, duration, and notes for each exercise. Keep detailed records of your training sessions.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-step">
                        <div class="step-number">3</div>
                        <h4>Track & Visualize</h4>
                        <p>View your workout history, analyze progress with charts, and see which muscle groups you've been training with the heatmap.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Anshu Kun. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
