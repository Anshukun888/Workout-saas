-- sql/schema_and_seed.sql
CREATE DATABASE IF NOT EXISTS workout_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE workout_saas;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE workout (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) DEFAULT NULL,
  notes TEXT,
  date DATE NOT NULL,
  duration INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE workout_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  type VARCHAR(50) DEFAULT NULL,
  notes TEXT,
  date DATE NOT NULL,
  duration INT,
    sets INT NOT NULL,
    reps INT NOT NULL,
    weight DECIMAL(5,2) DEFAULT 0,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE workout_names (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type VARCHAR(50) NOT NULL
);

-- Seed some common workouts for autocomplete
INSERT INTO workout_names (name, type) VALUES
('Bench Press','Chest'),
('Incline Dumbbell Press','Chest'),
('Push Up','Push'),
('Overhead Press','Shoulders'),
('Pull Up','Pull'),
('Barbell Row','Back'),
('Deadlift','Back'),
('Squat','Legs'),
('Lunge','Legs'),
('Leg Press','Legs'),
('Bicep Curl','Pull'),
('Tricep Dip','Push'),
('Running','Cardio'),
('Cycling','Cardio'),
('Jump Rope','Cardio'),
('Chest Fly','Chest'),
('Lat Pulldown','Back');

ALTER TABLE workout ADD COLUMN sets TEXT DEFAULT NULL;
ALTER TABLE workout ADD COLUMN title VARCHAR(150) NOT NULL AFTER user_id;

-- Routines: high-level plan a user can customize (3-7 days/week)
CREATE TABLE IF NOT EXISTS routine (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(150) NOT NULL DEFAULT 'My Routine',
  days_per_week TINYINT NOT NULL DEFAULT 3,
  pattern_length TINYINT NULL,
  start_weekday TINYINT NOT NULL DEFAULT 1, -- 1=Mon .. 7=Sun
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS routine_day (
  id INT AUTO_INCREMENT PRIMARY KEY,
  routine_id INT NOT NULL,
  day_index TINYINT NOT NULL, -- 1..days_per_week
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY (routine_id) REFERENCES routine(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_routine_day (routine_id, day_index)
);

CREATE TABLE IF NOT EXISTS routine_exercise (
  id INT AUTO_INCREMENT PRIMARY KEY,
  routine_day_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  type VARCHAR(50) DEFAULT NULL,
  default_sets JSON NULL, -- e.g., [{"reps":10,"weight":""}]
  notes TEXT,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (routine_day_id) REFERENCES routine_day(id) ON DELETE CASCADE
);

-- Link individual workouts and history entries to routine context (optional)
-- These may fail if columns already exist; run manually if needed
ALTER TABLE workout ADD COLUMN IF NOT EXISTS routine_id INT NULL;
ALTER TABLE workout ADD COLUMN IF NOT EXISTS routine_day_index TINYINT NULL;
ALTER TABLE workout ADD COLUMN IF NOT EXISTS week_start DATE NULL;
ALTER TABLE workout ADD CONSTRAINT fk_workout_routine FOREIGN KEY (routine_id) REFERENCES routine(id) ON DELETE SET NULL;

ALTER TABLE workout_history ADD COLUMN IF NOT EXISTS routine_id INT NULL;
ALTER TABLE workout_history ADD COLUMN IF NOT EXISTS routine_day_index TINYINT NULL;
ALTER TABLE workout_history ADD COLUMN IF NOT EXISTS week_start DATE NULL;
ALTER TABLE workout_history ADD CONSTRAINT fk_history_routine FOREIGN KEY (routine_id) REFERENCES routine(id) ON DELETE SET NULL;

-- Backfill for existing installations
ALTER TABLE routine ADD COLUMN IF NOT EXISTS pattern_length TINYINT NULL;
-- Ensure workout_history stores JSON sets
ALTER TABLE workout_history MODIFY COLUMN sets TEXT NULL;


