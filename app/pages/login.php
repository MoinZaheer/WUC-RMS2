<?php
require_once __DIR__ . '/../includes/db.php'; // Adjust path as needed
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $user_id = $_POST['user_id'] ?? '';
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("
      SELECT * FROM UserAccounts 
      WHERE linked_student_id = :student_id OR linked_staff_id = :staff_id
  ");
  $stmt->execute([
      'student_id' => $user_id,
      'staff_id' => $user_id
  ]);

  $user = $stmt->fetch();

  if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['role'] = $user['role'];

      //role based redirection
      if ($user['role'] === 'admin') {
          header('Location: /index.php?page=dashboard');
          exit();
      } elseif ($user['role'] === 'student') {
          header('Location: /index.php?page=dashboard');
          exit();
      } elseif ($user['role'] === 'staff') {
          header('Location: /index.php?page=dashboard');
          exit();
      }
  } else {
      $error = "Invalid ID or password.";
  }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - WUC RMS</title>

  <link rel="stylesheet" href="style.css" />
</head>
<body class="login-page">
  <div class="login-box">
    <img src="/images/Logo.jpg" alt="Logo" class="logo" />
    <h1>Woodlands University College</h1>

    <form action="/index.php?page=login" method="POST">
      <input type="text" name="user_id" placeholder="Email / ID" required />
      <input type="password" name="password" placeholder="Password" required />
      <div class="checkbox-container">
        <label><input type="checkbox" name="remember_me" /> Keep me Logged in</label>
        <a href="#">Forgot your password?</a>
      </div>
      <input type="submit" value="Login" />
    </form>
     <!--  -->
  </div>
</body>
</html>
